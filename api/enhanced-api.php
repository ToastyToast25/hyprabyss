<?php

declare(strict_types=1);

/**
 * Enhanced API - Unified API endpoint for HyperAbyss ARK Cluster
 * PHP 8.4 compatible with strict typing and modern features
 */

namespace HyperAbyss\API;

use Exception;
use PDO;
use PDOException;

// Error handling
ini_set('display_errors', '0');
error_reporting(E_ALL);

// CORS and security headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../classes/Config.php';
require_once __DIR__ . '/../classes/EnhancedArkRcon.php';

enum ResponseStatus: string
{
    case SUCCESS = 'success';
    case ERROR = 'error';
    case WARNING = 'warning';
}

enum ServerStatus: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';
    case CRASHED = 'crashed';
    case RESTARTING = 'restarting';
}

class APIResponse
{
    public function __construct(
        public readonly ResponseStatus $status,
        public readonly array $data = [],
        public readonly ?string $error = null,
        public readonly int $responseTime = 0
    ) {}

    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'timestamp' => date('c'),
            'response_time_ms' => $this->responseTime,
            'data' => $this->data,
            'error' => $this->error
        ];
    }
}

class EnhancedAPI
{
    private readonly float $startTime;
    private readonly Config $config;
    private readonly ?PDO $pdo;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->config = new Config();
        $this->pdo = $this->initializeDatabase();
    }

    private function initializeDatabase(): ?PDO
    {
        try {
            $dbConfig = $this->config->getDatabase();
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}";
            
            return new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return null;
        }
    }

    public function handleRequest(): void
    {
        try {
            $endpoint = $_GET['endpoint'] ?? 'servers';
            $response = match ($endpoint) {
                'servers' => $this->getServers(),
                'analytics' => $this->getAnalytics(),
                'discord' => $this->getDiscordStats(),
                'health' => $this->getHealthCheck(),
                'player-count' => $this->getPlayerCount(),
                default => throw new Exception("Unknown endpoint: $endpoint")
            };

            $this->logRequest($endpoint, 200);
            echo json_encode($response->toArray());
        } catch (Exception $e) {
            $this->logRequest($_GET['endpoint'] ?? 'unknown', 500, $e->getMessage());
            http_response_code(500);
            echo json_encode((new APIResponse(
                ResponseStatus::ERROR,
                error: $e->getMessage(),
                responseTime: $this->getResponseTime()
            ))->toArray());
        }
    }

    private function getServers(): APIResponse
    {
        $servers = $this->config->getServers();
        $serverData = [];
        $totalPlayers = 0;
        $onlineServers = 0;

        foreach ($servers as $serverKey => $serverConfig) {
            try {
                $rcon = new \EnhancedArkRcon(
                    $serverConfig['ip'],
                    (int)$serverConfig['rcon_port'],
                    $serverConfig['rcon_password'],
                    10
                );

                $playerData = $rcon->getPlayerList();
                $ping = $rcon->getPing();
                $maxPlayers = $rcon->getMaxPlayers();

                $serverData[$serverKey] = [
                    'key' => $serverKey,
                    'name' => $serverConfig['name'],
                    'ip' => $serverConfig['ip'],
                    'port' => (int)$serverConfig['port'],
                    'status' => ServerStatus::ONLINE->value,
                    'players' => [
                        'online' => $playerData['count'],
                        'max' => $maxPlayers,
                        'list' => $playerData['players']
                    ],
                    'server_info' => [
                        'map' => $serverConfig['map'],
                        'ping' => $ping
                    ],
                    'uptime' => $this->getServerUptime($serverKey),
                    'last_updated' => date('c')
                ];

                $totalPlayers += $playerData['count'];
                $onlineServers++;

                // Update database if available
                $this->updateServerStatus($serverKey, $playerData['count'], $ping);

            } catch (Exception $e) {
                $serverData[$serverKey] = [
                    'key' => $serverKey,
                    'name' => $serverConfig['name'],
                    'ip' => $serverConfig['ip'],
                    'port' => (int)$serverConfig['port'],
                    'status' => ServerStatus::OFFLINE->value,
                    'players' => ['online' => 0, 'max' => 150, 'list' => []],
                    'server_info' => ['map' => $serverConfig['map'], 'ping' => 0],
                    'uptime' => 0,
                    'error' => $e->getMessage(),
                    'last_updated' => date('c')
                ];
            }
        }

        return new APIResponse(
            ResponseStatus::SUCCESS,
            [
                'servers' => $serverData,
                'meta' => [
                    'total_servers' => count($serverData),
                    'online_servers' => $onlineServers,
                    'total_players' => $totalPlayers,
                    'refresh_interval' => $this->config->getRefreshInterval()
                ]
            ],
            responseTime: $this->getResponseTime()
        );
    }

    private function getAnalytics(): APIResponse
    {
        if (!$this->pdo) {
            throw new Exception('Database not available for analytics');
        }

        $stmt = $this->pdo->query("SELECT * FROM player_analytics ORDER BY id DESC LIMIT 1");
        $analytics = $stmt->fetch() ?: [];

        $stmt = $this->pdo->query("SELECT AVG(uptime_percentage) as avg_uptime FROM server_uptime_history WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS)");
        $uptime = $stmt->fetch();

        return new APIResponse(
            ResponseStatus::SUCCESS,
            [
                'analytics' => [
                    'unique_players_all_time' => (int)($analytics['unique_players_all_time'] ?? 0),
                    'peak_concurrent_players' => (int)($analytics['peak_concurrent_players'] ?? 0),
                    'peak_concurrent_date' => $analytics['peak_concurrent_date'] ?? null,
                    'avg_uptime_7_days' => round((float)($uptime['avg_uptime'] ?? 99.5), 1)
                ]
            ],
            responseTime: $this->getResponseTime()
        );
    }

    private function getDiscordStats(): APIResponse
    {
        if (!$this->pdo) {
            return new APIResponse(
                ResponseStatus::SUCCESS,
                ['discord' => ['member_count' => 500, 'online_count' => 50, 'voice_channels' => 5]],
                responseTime: $this->getResponseTime()
            );
        }

        $stmt = $this->pdo->query("SELECT * FROM discord_stats ORDER BY id DESC LIMIT 1");
        $discord = $stmt->fetch() ?: ['member_count' => 500, 'online_count' => 50, 'voice_channels' => 5];

        return new APIResponse(
            ResponseStatus::SUCCESS,
            ['discord' => $discord],
            responseTime: $this->getResponseTime()
        );
    }

    private function getHealthCheck(): APIResponse
    {
        $checks = [
            'api' => ['status' => 'healthy', 'response_time' => $this->getResponseTime()],
            'database' => $this->checkDatabase(),
            'config' => $this->checkConfig()
        ];

        $overallStatus = in_array('error', array_column($checks, 'status')) 
            ? ResponseStatus::ERROR 
            : (in_array('warning', array_column($checks, 'status')) 
                ? ResponseStatus::WARNING 
                : ResponseStatus::SUCCESS);

        return new APIResponse(
            $overallStatus,
            ['health' => $checks],
            responseTime: $this->getResponseTime()
        );
    }

    private function getPlayerCount(): APIResponse
    {
        $servers = $this->getServers();
        $totalPlayers = $servers->data['meta']['total_players'] ?? 0;

        return new APIResponse(
            ResponseStatus::SUCCESS,
            ['player_count' => $totalPlayers],
            responseTime: $this->getResponseTime()
        );
    }

    private function checkDatabase(): array
    {
        if (!$this->pdo) {
            return ['status' => 'error', 'message' => 'Database connection failed'];
        }

        try {
            $this->pdo->query("SELECT 1");
            return ['status' => 'healthy', 'message' => 'Database connected'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkConfig(): array
    {
        $servers = $this->config->getServers();
        return [
            'status' => empty($servers) ? 'warning' : 'healthy',
            'message' => empty($servers) ? 'No servers configured' : count($servers) . ' servers configured'
        ];
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

    private function updateServerStatus(string $serverKey, int $playerCount, int $ping): void
    {
        if (!$this->pdo) return;

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO server_status (server_key, status, players_online, ping_ms, uptime_seconds)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    players_online = VALUES(players_online),
                    ping_ms = VALUES(ping_ms),
                    uptime_seconds = VALUES(uptime_seconds)
            ");

            $stmt->execute([
                $serverKey,
                ServerStatus::ONLINE->value,
                $playerCount,
                $ping,
                $this->getServerUptime($serverKey)
            ]);
        } catch (Exception $e) {
            error_log("Failed to update server status: " . $e->getMessage());
        }
    }

    private function logRequest(string $endpoint, int $statusCode, ?string $error = null): void
    {
        if (!$this->pdo) return;

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO api_request_log (endpoint, ip_address, user_agent, response_time_ms, status_code, error_message)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $endpoint,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                $this->getResponseTime(),
                $statusCode,
                $error
            ]);
        } catch (Exception $e) {
            error_log("Failed to log API request: " . $e->getMessage());
        }
    }

    private function getResponseTime(): float
    {
        return round((microtime(true) - $this->startTime) * 1000, 2);
    }
}

// Handle the request
$api = new EnhancedAPI();
$api->handleRequest();