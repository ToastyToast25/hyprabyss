<?php
/**
 * Server Monitoring Script (scripts/monitoring.php)
 * Monitors server health and sends alerts
 */

declare(strict_types=1);

require_once __DIR__ . '/../classes/Config.php';
require_once __DIR__ . '/../classes/EnhancedArkRcon.php';

use HyperAbyss\Config;
use HyperAbyss\Database;

class ServerMonitor
{
    private Config $config;
    private array $alerts = [];

    public function __construct()
    {
        $this->config = Config::getInstance();
    }

    public function run(): void
    {
        try {
            $this->logInfo("Starting server monitoring cycle");
            
            $servers = $this->config->getServers();
            foreach ($servers as $serverKey => $serverConfig) {
                $this->monitorServer($serverKey, $serverConfig);
            }
            
            $this->updateClusterHealth();
            $this->sendAlerts();
            
            $this->logInfo("Monitoring cycle completed successfully");
        } catch (Exception $e) {
            $this->logError("Monitoring failed: " . $e->getMessage());
        }
    }

    private function monitorServer(string $serverKey, array $config): void
    {
        $startTime = microtime(true);
        
        try {
            $rcon = new \EnhancedArkRcon(
                $config['ip'],
                (int)$config['rcon_port'],
                $config['rcon_password'],
                10
            );

            $playerData = $rcon->getPlayerList();
            $ping = $rcon->getPing();
            
            $this->updateServerStatus($serverKey, [
                'status' => 'online',
                'players_online' => $playerData['count'],
                'ping_ms' => $ping,
                'uptime_seconds' => $this->getServerUptime($serverKey),
                'last_updated' => date('Y-m-d H:i:s')
            ]);
            
            $this->checkServerAlerts($serverKey, $config, $playerData['count'], $ping);
            
        } catch (Exception $e) {
            $this->updateServerStatus($serverKey, [
                'status' => 'offline',
                'players_online' => 0,
                'ping_ms' => 0,
                'last_updated' => date('Y-m-d H:i:s')
            ]);
            
            $this->addAlert($serverKey, 'Server Offline', "Server {$config['name']} is not responding: " . $e->getMessage());
            $this->logError("Server $serverKey offline: " . $e->getMessage());
        }
    }

    private function updateServerStatus(string $serverKey, array $data): void
    {
        try {
            Database::query("
                INSERT INTO server_status (server_key, status, players_online, ping_ms, uptime_seconds, last_updated)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    players_online = VALUES(players_online),
                    ping_ms = VALUES(ping_ms),
                    uptime_seconds = VALUES(uptime_seconds),
                    last_updated = VALUES(last_updated)
            ", [
                $serverKey,
                $data['status'],
                $data['players_online'],
                $data['ping_ms'],
                $data['uptime_seconds'],
                $data['last_updated']
            ]);
        } catch (Exception $e) {
            $this->logError("Failed to update server status for $serverKey: " . $e->getMessage());
        }
    }

    private function checkServerAlerts(string $serverKey, array $config, int $playerCount, int $ping): void
    {
        // High ping alert
        if ($ping > 500) {
            $this->addAlert($serverKey, 'High Ping', "Server {$config['name']} has high ping: {$ping}ms");
        }
        
        // Server full alert
        if ($playerCount >= ($config['max_players'] ?? 150)) {
            $this->addAlert($serverKey, 'Server Full', "Server {$config['name']} is at capacity: $playerCount players");
        }
        
        // Low player count during peak hours (7-11 PM)
        $hour = (int)date('H');
        if ($hour >= 19 && $hour <= 23 && $playerCount < 5) {
            $this->addAlert($serverKey, 'Low Activity', "Server {$config['name']} has low activity during peak hours: $playerCount players");
        }
    }

    private function getServerUptime(string $serverKey): int
    {
        $uptimeFile = __DIR__ . "/../logs/uptime_{$serverKey}.dat";
        if (!file_exists($uptimeFile)) {
            file_put_contents($uptimeFile, (string)time());
            return 0;
        }
        
        $startTime = (int)file_get_contents($uptimeFile);
        return time() - $startTime;
    }

    private function updateClusterHealth(): void
    {
        try {
            $totalServers = Database::fetchOne("SELECT COUNT(*) as count FROM servers WHERE is_active = 1")['count'] ?? 0;
            $onlineServers = Database::fetchOne("SELECT COUNT(*) as count FROM server_status WHERE status = 'online'")['count'] ?? 0;
            $totalPlayers = Database::fetchOne("SELECT SUM(players_online) as total FROM server_status WHERE status = 'online'")['total'] ?? 0;
            
            // Log performance metrics
            Database::query("INSERT INTO performance_metrics (metric_name, metric_value) VALUES (?, ?)", ['cluster_uptime_percentage', ($onlineServers / max($totalServers, 1)) * 100]);
            Database::query("INSERT INTO performance_metrics (metric_name, metric_value) VALUES (?, ?)", ['total_players', $totalPlayers]);
            
        } catch (Exception $e) {
            $this->logError("Failed to update cluster health: " . $e->getMessage());
        }
    }

    private function addAlert(string $serverKey, string $type, string $message): void
    {
        $this->alerts[] = [
            'server_key' => $serverKey,
            'type' => $type,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    private function sendAlerts(): void
    {
        if (empty($this->alerts)) return;
        
        foreach ($this->alerts as $alert) {
            // Log to database
            try {
                Database::query("
                    INSERT INTO event_logs (event_type, event_data, server_key, timestamp)
                    VALUES (?, ?, ?, ?)
                ", [
                    'alert',
                    json_encode($alert),
                    $alert['server_key'],
                    $alert['timestamp']
                ]);
            } catch (Exception $e) {
                $this->logError("Failed to log alert: " . $e->getMessage());
            }
            
            // Send Discord webhook if configured
            $this->sendDiscordAlert($alert);
        }
    }

    private function sendDiscordAlert(array $alert): void
    {
        $webhookUrl = $this->config->get('DISCORD_WEBHOOK_URL');
        if (!$webhookUrl) return;
        
        $payload = [
            'embeds' => [[
                'title' => "🚨 {$alert['type']} Alert",
                'description' => $alert['message'],
                'color' => $alert['type'] === 'Server Offline' ? 15158332 : 16776960, // Red or Yellow
                'timestamp' => date('c'),
                'footer' => ['text' => 'HyperAbyss Monitoring']
            ]]
        ];