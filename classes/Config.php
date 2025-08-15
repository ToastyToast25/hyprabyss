<?php

declare(strict_types=1);

namespace HyperAbyss;

use PDO;
use PDOException;
use Exception;

/**
 * Enhanced Configuration Management for HyperAbyss ARK Cluster
 * PHP 8.4 compatible with strict typing and modern features
 */
class Config
{
    private static ?self $instance = null;
    private readonly array $env;
    private readonly array $servers;
    private ?PDO $pdo = null;

    public function __construct(string $envFile = '.env')
    {
        $this->env = $this->loadEnvironment($envFile);
        $this->servers = $this->loadServers();
        $this->initializeDatabase();
    }

    public static function getInstance(string $envFile = '.env'): self
    {
        return self::$instance ??= new self($envFile);
    }

    private function loadEnvironment(string $envFile): array
    {
        if (!file_exists($envFile)) {
            throw new Exception("Environment file not found: {$envFile}");
        }

        $env = [];
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');

            // Type conversion
            $env[$key] = match ($value) {
                'true' => true,
                'false' => false,
                default => is_numeric($value) 
                    ? (str_contains($value, '.') ? (float)$value : (int)$value)
                    : $value
            };
        }

        return $env;
    }

    private function loadServers(): array
    {
        // Try database first, fallback to environment
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->query("SELECT * FROM servers WHERE is_active = 1 ORDER BY server_order ASC");
                $dbServers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($dbServers)) {
                    $servers = [];
                    foreach ($dbServers as $row) {
                        $servers[$row['server_key']] = [
                            'key' => $row['server_key'],
                            'name' => $row['name'],
                            'description' => $row['description'],
                            'ip' => $row['ip'],
                            'port' => (int)$row['port'],
                            'query_port' => (int)$row['query_port'],
                            'rcon_port' => (int)$row['rcon_port'],
                            'rcon_password' => $this->env[strtoupper($row['server_key']) . '_RCON_PASSWORD'] ?? '',
                            'map' => $row['map_name']
                        ];
                    }
                    return $servers;
                }
            } catch (Exception $e) {
                error_log("Database server lookup failed: " . $e->getMessage());
            }
        }

        return $this->loadServersFromEnv();
    }

    private function loadServersFromEnv(): array
    {
        $servers = [];

        foreach ($this->env as $key => $value) {
            if (!preg_match('/^(.+)_NAME$/', $key, $matches)) {
                continue;
            }

            $serverKey = strtolower($matches[1]);
            $upperKey = $matches[1];

            if (!($this->env[$upperKey . '_ENABLED'] ?? true)) {
                continue;
            }

            $requiredFields = ['IP', 'PORT', 'RCON_PORT', 'RCON_PASSWORD', 'MAP'];
            if (!$this->hasAllFields($upperKey, $requiredFields)) {
                continue;
            }

            $servers[$serverKey] = [
                'key' => $serverKey,
                'name' => $this->env[$upperKey . '_NAME'],
                'ip' => $this->env[$upperKey . '_IP'],
                'port' => (int)$this->env[$upperKey . '_PORT'],
                'query_port' => (int)($this->env[$upperKey . '_QUERY_PORT'] ?? $this->env[$upperKey . '_PORT']),
                'rcon_port' => (int)$this->env[$upperKey . '_RCON_PORT'],
                'rcon_password' => $this->env[$upperKey . '_RCON_PASSWORD'],
                'map' => $this->env[$upperKey . '_MAP']
            ];
        }

        return $servers;
    }

    private function hasAllFields(string $prefix, array $fields): bool
    {
        foreach ($fields as $field) {
            if (!isset($this->env[$prefix . '_' . $field])) {
                return false;
            }
        }
        return true;
    }

    private function initializeDatabase(): void
    {
        try {
            $config = $this->getDatabase();
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['name']};charset={$config['charset']}";

            $this->pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException) {
            // Database not available, continue without it
            $this->pdo = null;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->env[$key] ?? $default;
    }

    public function getDatabase(): array
    {
        return [
            'host' => $this->get('DB_HOST', 'localhost'),
            'name' => $this->get('DB_NAME', 'hyperabyss_cluster'),
            'user' => $this->get('DB_USER', 'root'),
            'pass' => $this->get('DB_PASS', ''),
            'port' => (int)$this->get('DB_PORT', 3306),
            'charset' => $this->get('DB_CHARSET', 'utf8mb4')
        ];
    }

    public function getServers(): array
    {
        return $this->servers;
    }

    public function getRefreshInterval(): int
    {
        $dbSetting = $this->getDbSetting('refresh_interval');
        return $dbSetting ? (int)$dbSetting : (int)$this->get('REFRESH_INTERVAL', 15);
    }

    public function getRateLimit(): array
    {
        return [
            'requests' => (int)($this->getDbSetting('rate_limit_requests') ?? $this->get('RATE_LIMIT_REQUESTS', 60)),
            'window' => (int)($this->getDbSetting('rate_limit_window') ?? $this->get('RATE_LIMIT_WINDOW', 60))
        ];
    }

    public function getSecurity(): array
    {
        return [
            'cors_enabled' => (bool)$this->get('CORS_ENABLED', true),
            'cors_origins' => explode(',', (string)$this->get('CORS_ORIGINS', '*')),
            'https_only' => (bool)$this->get('HTTPS_ONLY', false),
            'api_key_required' => (bool)$this->get('API_KEY_REQUIRED', false),
            'api_key' => $this->get('API_KEY', ''),
        ];
    }

    private function getDbSetting(string $key): ?string
    {
        if (!$this->pdo) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            return $result['setting_value'] ?? null;
        } catch (Exception) {
            return null;
        }
    }

    public function getConnection(): ?PDO
    {
        return $this->pdo;
    }
}

/**
 * Simple Database wrapper for convenience
 */
class Database
{
    private static ?PDO $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo === null) {
            $config = Config::getInstance();
            self::$pdo = $config->getConnection() ?? throw new Exception('Database connection not available');
        }
        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $pdo = self::connect();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchOne(string $sql, array $params = []): array|false
    {
        return self::query($sql, $params)->fetch();
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert(string $table, array $data): \PDOStatement
    {
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
        return self::query($sql, $data);
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): \PDOStatement
    {
        $sets = array_map(fn($field) => "{$field} = :{$field}", array_keys($data));
        $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE {$where}";
        return self::query($sql, [...$data, ...$whereParams]);
    }
}