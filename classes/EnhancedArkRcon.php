<?php

declare(strict_types=1);

namespace HyperAbyss;

use Exception;

/**
 * Enhanced ARK RCON Communication Class
 * PHP 8.4 compatible with strict typing and modern features
 */
enum RconPacketType: int
{
    case AUTH = 3;
    case EXECCOMMAND = 2;
    case RESPONSE_VALUE = 0;
    case AUTH_RESPONSE = 2;
}

readonly class PlayerInfo
{
    public function __construct(
        public string $name,
        public string $eosId
    ) {}
}

readonly class PlayerListResult
{
    public function __construct(
        public int $count,
        public array $players
    ) {}
}

class EnhancedArkRcon
{
    private readonly string $host;
    private readonly int $port;
    private readonly string $password;
    private readonly int $timeout;
    private mixed $socket = null;
    private int $requestId = 1;

    public function __construct(
        string $host,
        int $port,
        string $password,
        int $timeout = 10
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->timeout = $timeout;
        
        $this->connect();
    }

    private function connect(): void
    {
        $this->socket = @fsockopen("tcp://{$this->host}", $this->port, $errno, $errstr, $this->timeout);
        
        if (!$this->socket) {
            throw new Exception("Failed to connect to RCON: $errstr ($errno)");
        }

        stream_set_timeout($this->socket, $this->timeout);
        $this->authenticate();
    }

    private function authenticate(): void
    {
        $packet = $this->createPacket(RconPacketType::AUTH, $this->password);
        $this->sendPacket($packet);
        
        $response = $this->readPacket();
        
        if (!$response || $response['id'] === -1) {
            throw new Exception("RCON authentication failed");
        }
    }

    private function createPacket(RconPacketType $type, string $body): string
    {
        $id = $this->requestId++;
        $packet = pack('VV', $id, $type->value) . $body . "\x00\x00";
        return pack('V', strlen($packet)) . $packet;
    }

    private function sendPacket(string $packet): void
    {
        if (!$this->socket) {
            throw new Exception("Not connected to RCON server");
        }
        
        if (fwrite($this->socket, $packet) === false) {
            throw new Exception("Failed to send RCON packet");
        }
    }

    private function readPacket(): array|false
    {
        if (!$this->socket) {
            throw new Exception("Not connected to RCON server");
        }
        
        $sizeData = fread($this->socket, 4);
        if (strlen($sizeData) < 4) {
            return false;
        }
        
        $size = unpack('V', $sizeData)[1];
        $packetData = fread($this->socket, $size);
        
        if (strlen($packetData) < $size) {
            return false;
        }
        
        $id = unpack('V', substr($packetData, 0, 4))[1];
        $type = unpack('V', substr($packetData, 4, 4))[1];
        $body = substr($packetData, 8, -2);
        
        return [
            'id' => $id,
            'type' => $type,
            'body' => $body
        ];
    }

    public function executeCommand(string $command): string
    {
        $packet = $this->createPacket(RconPacketType::EXECCOMMAND, $command);
        $this->sendPacket($packet);
        
        $response = $this->readPacket();
        
        if (!$response) {
            throw new Exception("Failed to execute RCON command: $command");
        }
        
        return $response['body'];
    }

    public function getPlayerList(): PlayerListResult
    {
        try {
            $result = $this->executeCommand('listplayers');
            $players = [];
            $lines = explode("\n", trim($result));
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_contains($line, 'No Players Connected')) {
                    continue;
                }
                
                if (preg_match('/^\d+\.\s*([^,]+),\s*([a-f0-9]+)\s*$/i', $line, $matches)) {
                    $players[] = new PlayerInfo(trim($matches[1]), trim($matches[2]));
                }
            }
            
            return new PlayerListResult(count($players), $players);
        } catch (Exception) {
            return new PlayerListResult(0, []);
        }
    }

    public function getPing(): int
    {
        $startTime = microtime(true);
        
        try {
            $this->executeCommand('GetGameLog');
            return (int)round((microtime(true) - $startTime) * 1000);
        } catch (Exception) {
            return 0;
        }
    }

    public function getMaxPlayers(): int
    {
        try {
            $result = $this->executeCommand('GetGameLog');
            
            if (preg_match('/MaxPlayers[:\s]+(\d+)/i', $result, $matches)) {
                return (int)$matches[1];
            }
            
            return 150;
        } catch (Exception) {
            return 150;
        }
    }

    public function getServerInfo(): array
    {
        try {
            $result = $this->executeCommand('GetGameLog');
            
            return [
                'response' => $result,
                'timestamp' => date('c')
            ];
        } catch (Exception $e) {
            return [
                'response' => '',
                'error' => $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
    }

    public function saveWorld(): string
    {
        try {
            return $this->executeCommand('SaveWorld');
        } catch (Exception $e) {
            throw new Exception("Failed to save world: " . $e->getMessage());
        }
    }

    public function broadcast(string $message): string
    {
        try {
            return $this->executeCommand("Broadcast $message");
        } catch (Exception $e) {
            throw new Exception("Failed to broadcast message: " . $e->getMessage());
        }
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}

/**
 * Backwards compatibility alias
 */
class ArkRcon extends EnhancedArkRcon {}