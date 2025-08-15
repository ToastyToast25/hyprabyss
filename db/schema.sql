-- Complete Database Schema for HyperAbyss ARK Cluster
-- Merged from database_schema.sql and database-updates.sql
-- Run this to create all required tables

CREATE DATABASE IF NOT EXISTS hyperabyss_cluster CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hyperabyss_cluster;

-- Servers configuration
CREATE TABLE IF NOT EXISTS servers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_key VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    ip VARCHAR(45) NOT NULL,
    port INT NOT NULL,
    query_port INT NOT NULL,
    rcon_port INT NOT NULL,
    map_name VARCHAR(100),
    max_players INT DEFAULT 150,
    server_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_server_key (server_key),
    INDEX idx_active (is_active),
    INDEX idx_order (server_order),
    INDEX idx_featured (is_featured)
);

-- Server status cache (updated every 15 seconds)
CREATE TABLE IF NOT EXISTS server_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_key VARCHAR(50) NOT NULL,
    status ENUM('online', 'offline', 'crashed', 'restarting') NOT NULL,
    players_online INT DEFAULT 0,
    max_players INT DEFAULT 150,
    ping_ms INT DEFAULT 0,
    uptime_seconds INT DEFAULT 0,
    last_crash_at TIMESTAMP NULL,
    crash_count INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_server_key (server_key),
    INDEX idx_status (status),
    INDEX idx_last_updated (last_updated),
    FOREIGN KEY (server_key) REFERENCES servers(server_key) ON DELETE CASCADE
);

-- Player tracking
CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_key VARCHAR(50) NOT NULL,
    player_name VARCHAR(255) NOT NULL,
    eos_id VARCHAR(100) NOT NULL,
    steam_id VARCHAR(50) NULL,
    first_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    total_playtime INT DEFAULT 0,
    is_online BOOLEAN DEFAULT FALSE,
    UNIQUE KEY unique_player (server_key, eos_id),
    INDEX idx_server_key (server_key),
    INDEX idx_eos_id (eos_id),
    INDEX idx_online (is_online),
    INDEX idx_last_seen (last_seen),
    FOREIGN KEY (server_key) REFERENCES servers(server_key) ON DELETE CASCADE
);

-- Unique player tracking (stores all unique players ever seen)
CREATE TABLE IF NOT EXISTS unique_player_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_name VARCHAR(255) NOT NULL,
    eos_id VARCHAR(100) NOT NULL UNIQUE,
    server_key VARCHAR(50) NOT NULL,
    first_seen DATETIME NOT NULL,
    last_seen DATETIME NOT NULL,
    total_sessions INT DEFAULT 1,
    last_server VARCHAR(50),
    INDEX idx_eos_id (eos_id),
    INDEX idx_server_key (server_key),
    INDEX idx_first_seen (first_seen),
    INDEX idx_last_seen (last_seen),
    FOREIGN KEY (server_key) REFERENCES servers(server_key) ON DELETE CASCADE
);

-- Player analytics tracking
CREATE TABLE IF NOT EXISTS player_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unique_players_all_time INT DEFAULT 0,
    peak_concurrent_players INT DEFAULT 0,
    peak_concurrent_date DATETIME,
    daily_unique_players INT DEFAULT 0,
    weekly_unique_players INT DEFAULT 0,
    monthly_unique_players INT DEFAULT 0,
    last_daily_reset DATE,
    last_weekly_reset DATE,
    last_monthly_reset DATE,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Server uptime history (daily summaries)
CREATE TABLE IF NOT EXISTS server_uptime_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_key VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    total_uptime_seconds INT DEFAULT 0,
    total_downtime_seconds INT DEFAULT 0,
    uptime_percentage DECIMAL(5,2) DEFAULT 0,
    crash_count INT DEFAULT 0,
    avg_players DECIMAL(5,2) DEFAULT 0,
    peak_players INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_server_date (server_key, date),
    INDEX idx_date (date),
    FOREIGN KEY (server_key) REFERENCES servers(server_key) ON DELETE CASCADE
);

-- Concurrent player history (every 15 minutes)
CREATE TABLE IF NOT EXISTS concurrent_player_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME NOT NULL,
    total_players INT DEFAULT 0,
    server_breakdown JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_timestamp (timestamp)
);

-- Rate limiting for API requests
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    endpoint VARCHAR(100) NOT NULL,
    request_count INT DEFAULT 1,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    blocked_until TIMESTAMP NULL,
    INDEX idx_ip_endpoint (ip_address, endpoint),
    INDEX idx_window_start (window_start)
);

-- API request logging for performance monitoring
CREATE TABLE IF NOT EXISTS api_request_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    endpoint VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    response_time_ms DECIMAL(8,2),
    status_code INT,
    error_message TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_endpoint (endpoint),
    INDEX idx_timestamp (timestamp),
    INDEX idx_status (status_code)
);

-- News/announcements
CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    excerpt VARCHAR(500),
    author VARCHAR(100),
    is_published BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_published (is_published, published_at),
    INDEX idx_featured (is_featured)
);

-- Newsletter subscribers
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    unsubscribe_token VARCHAR(100) UNIQUE,
    INDEX idx_email (email),
    INDEX idx_active (is_active)
);

-- Shop items/categories
CREATE TABLE IF NOT EXISTS shop_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS shop_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USD',
    image_url VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES shop_categories(id) ON DELETE CASCADE
);

-- Discord integration stats
CREATE TABLE IF NOT EXISTS discord_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_count INT DEFAULT 0,
    online_count INT DEFAULT 0,
    voice_channels INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Performance metrics
CREATE TABLE IF NOT EXISTS performance_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,4) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metric_name (metric_name),
    INDEX idx_timestamp (timestamp)
);

-- System settings
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User sessions (for admin panel)
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(128) NOT NULL UNIQUE,
    user_data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session_id (session_id),
    INDEX idx_expires_at (expires_at)
);

-- Event logs
CREATE TABLE IF NOT EXISTS event_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON,
    server_key VARCHAR(50) NULL,
    user_id VARCHAR(100) NULL,
    ip_address VARCHAR(45),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_server_key (server_key),
    INDEX idx_timestamp (timestamp)
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('refresh_interval', '15', 'integer', 'Server status refresh interval in seconds'),
('rate_limit_requests', '60', 'integer', 'Max requests per minute per IP'),
('rate_limit_window', '60', 'integer', 'Rate limit window in seconds'),
('site_title', 'HyperAbyss ARK Cluster', 'string', 'Website title'),
('site_description', 'The ultimate ARK: Survival Ascended multiplayer experience', 'string', 'Website description'),
('discord_invite', 'https://discord.gg/hyperabyss', 'string', 'Discord invite link'),
('discord_bot_token', '', 'string', 'Discord bot token for API integration'),
('discord_guild_id', '', 'string', 'Discord guild ID for stats'),
('discord_webhook_url', '', 'string', 'Discord webhook URL for notifications'),
('shop_enabled', 'true', 'boolean', 'Enable shop functionality'),
('newsletter_enabled', 'true', 'boolean', 'Enable newsletter functionality'),
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode'),
('analytics_enabled', 'true', 'boolean', 'Enable analytics tracking'),
('peak_players_notification', 'true', 'boolean', 'Notify when peak players record is broken'),
('uptime_alert_threshold', '95', 'integer', 'Alert when uptime drops below this percentage'),
('max_players_default', '150', 'integer', 'Default max players for new servers'),
('server_rates_gathering', '3', 'integer', 'Default gathering rate multiplier'),
('server_rates_taming', '3', 'integer', 'Default taming rate multiplier'),
('server_rates_breeding', '3', 'integer', 'Default breeding rate multiplier'),
('server_rates_experience', '3', 'integer', 'Default experience rate multiplier')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Initialize default analytics data
INSERT INTO player_analytics (
    unique_players_all_time,
    peak_concurrent_players,
    peak_concurrent_date,
    daily_unique_players,
    weekly_unique_players,
    monthly_unique_players,
    last_daily_reset,
    last_weekly_reset,
    last_monthly_reset
) VALUES (
    0,
    0,
    NOW(),
    0,
    0,
    0,
    CURDATE(),
    CURDATE(),
    CURDATE()
) ON DUPLICATE KEY UPDATE id = id;

-- Initialize Discord stats
INSERT INTO discord_stats (member_count, online_count, voice_channels)
VALUES (500, 50, 5)
ON DUPLICATE KEY UPDATE id = id;

-- Insert sample server data (based on your .env)
INSERT INTO servers (server_key, name, description, ip, port, query_port, rcon_port, map_name, max_players, server_order, is_featured) VALUES
('ragnarok', 'Cluster [HyperAbyss] Ragnarok PvP/3X/ORP/8Man', 'Our flagship server featuring the massive Ragnarok map with balanced PvP, offline raid protection, and active tribal warfare.', '198.23.225.136', 7795, 27028, 27028, 'Ragnarok_WP', 150, 1, TRUE),
('theisland', 'Cluster [HyperAbyss] The Island PvP/3X/ORP/8Man', 'Experience ARK the way it was meant to be played on the original map with enhanced rates and quality of life improvements.', '198.23.225.136', 7777, 27015, 27020, 'TheIsland_WP', 100, 2, FALSE),
('thecenter', 'Cluster [HyperAbyss] The Center PvP/3X/ORP/8Man', 'Explore the unique underground world and floating islands of The Center with our community.', '198.23.225.136', 7781, 27022, 27022, 'TheCenter_WP', 100, 3, FALSE),
('forglar', 'Cluster [HyperAbyss] Forglar [ENDGAME]', 'End-game content server for experienced players with advanced progression and challenges.', '198.23.225.136', 7793, 27029, 27029, 'Forglar_WP', 75, 4, TRUE),
('svartalfheim', 'Cluster [HyperAbyss] Svartalfheim [ENDGAME]', 'Norse mythology-inspired endgame server with unique creatures and challenging environments.', '198.23.225.136', 7797, 27031, 27031, 'Svartalfheim_WP', 75, 5, FALSE)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    ip = VALUES(ip),
    port = VALUES(port),
    query_port = VALUES(query_port),
    rcon_port = VALUES(rcon_port),
    map_name = VALUES(map_name);

-- Insert sample shop categories
INSERT INTO shop_categories (name, description, icon, sort_order) VALUES
('Character Skins', 'Custom character skins and cosmetic items', 'fas fa-user', 1),
('Tools & Equipment', 'Special tools and enhanced equipment', 'fas fa-tools', 2),
('Tamed Creatures', 'Pre-tamed creatures and breeding pairs', 'fas fa-dragon', 3),
('Building Materials', 'Advanced building materials and structures', 'fas fa-cubes', 4),
('Resource Packs', 'Starter packs and resource bundles', 'fas fa-box', 5)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description);

-- Insert sample news articles
INSERT INTO news (title, content, excerpt, author, is_published, is_featured, published_at) VALUES
('Welcome to HyperAbyss ARK Cluster!', 'Welcome to the official launch of HyperAbyss ARK Cluster! We are excited to provide you with the ultimate ARK: Survival Ascended experience. Our servers feature balanced 3X rates, offline raid protection, and a thriving community of survival enthusiasts.
Our cluster includes multiple maps including Ragnarok, The Island, The Center, and exclusive endgame content on Forglar and Svartalfheim. Each server is professionally managed with 24/7 admin support and regular community events.
Join our Discord community to connect with other players, participate in events, and get real-time server updates. Your survival adventure starts here!', 'Welcome to the official launch of HyperAbyss ARK Cluster! Experience balanced gameplay with professional management and an amazing community.', 'HyperAbyss Team', TRUE, TRUE, NOW()),
('Weekly Community Events Schedule', 'We are excited to announce our weekly community events schedule! Every week, we will be hosting various events to bring our community together and provide exciting challenges with amazing rewards.
**Monday**: Boss Fight Mondays - Community boss fights with shared loot
**Wednesday**: Building Competitions - Show off your creativity
**Friday**: PvP Tournaments - Structured PvP with prizes
**Saturday**: Treasure Hunts - Server-wide treasure hunting events
**Sunday**: Community Meetings - Voice chat discussions and feedback
All events will be announced in our Discord server with full details and prize information. We look forward to seeing you there!', 'Join our weekly community events! Boss fights, building competitions, PvP tournaments, and more with amazing rewards.', 'Event Team', TRUE, FALSE, DATE_SUB(NOW(), INTERVAL 5 DAY)),
('Enhanced Offline Raid Protection Now Live', 'Our new and improved Offline Raid Protection (ORP) system is now active across all servers! This system has been designed to provide fair protection while maintaining the competitive PvP experience that makes ARK exciting.
**How it works:**
- ORP activates 15 minutes after all tribe members log off
- Structures receive 90% damage reduction when protected
- PvP is still possible when players are online
- Special events may temporarily disable ORP with advance notice
The system has been extensively tested and provides the perfect balance between protection and gameplay. Your feedback has been invaluable in creating this system, and we will continue to monitor and adjust as needed.', 'Our enhanced ORP system ensures fair gameplay while maintaining competitive PvP. 90% damage reduction when offline with balanced mechanics.', 'Admin Team', TRUE, FALSE, DATE_SUB(NOW(), INTERVAL 10 DAY))
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    content = VALUES(content);

-- Create indexes for better performance (MySQL compatible)
ALTER TABLE players ADD INDEX idx_players_composite (server_key, is_online, last_seen);
ALTER TABLE api_request_log ADD INDEX idx_api_log_composite (endpoint, timestamp, status_code);
ALTER TABLE server_uptime_history ADD INDEX idx_uptime_history_composite (server_key, date, uptime_percentage);
ALTER TABLE player_analytics ADD INDEX idx_player_analytics_updated (last_updated);

-- Create views for common queries
CREATE OR REPLACE VIEW v_server_summary AS
SELECT
    s.server_key,
    s.name,
    s.description,
    s.map_name,
    s.max_players,
    s.is_active,
    s.is_featured,
    COALESCE(st.status, 'offline') as current_status,
    COALESCE(st.players_online, 0) as players_online,
    COALESCE(st.ping_ms, 0) as ping_ms,
    COALESCE(st.uptime_seconds, 0) as uptime_seconds,
    st.last_updated
FROM servers s
LEFT JOIN server_status st ON s.server_key = st.server_key
WHERE s.is_active = 1
ORDER BY s.server_order;

CREATE OR REPLACE VIEW v_player_statistics AS
SELECT
    server_key,
    COUNT(*) as total_unique_players,
    COUNT(CASE WHEN is_online = 1 THEN 1 END) as players_online,
    AVG(total_playtime) as avg_playtime,
    MAX(last_seen) as last_activity
FROM players
GROUP BY server_key;

-- Create stored procedures for common operations
DELIMITER //

CREATE PROCEDURE UpdatePlayerAnalytics()
BEGIN
    DECLARE total_unique INT DEFAULT 0;
    DECLARE peak_concurrent INT DEFAULT 0;
    DECLARE current_online INT DEFAULT 0;

    -- Get total unique players
    SELECT COUNT(DISTINCT eos_id) INTO total_unique FROM unique_player_tracking;

    -- Get current online players
    SELECT SUM(players_online) INTO current_online FROM server_status WHERE status = 'online';

    -- Get peak concurrent from history
    SELECT COALESCE(MAX(total_players), 0) INTO peak_concurrent FROM concurrent_player_history;

    -- Check if current online is new peak
    IF current_online > peak_concurrent THEN
        SET peak_concurrent = current_online;

        -- Insert new peak record
        INSERT INTO concurrent_player_history (timestamp, total_players, server_breakdown)
        VALUES (NOW(), current_online, JSON_OBJECT());
    END IF;

    -- Update analytics table
    INSERT INTO player_analytics (
        unique_players_all_time,
        peak_concurrent_players,
        peak_concurrent_date,
        last_updated
    ) VALUES (
        total_unique,
        peak_concurrent,
        CASE WHEN current_online = peak_concurrent THEN NOW() ELSE (SELECT peak_concurrent_date FROM player_analytics ORDER BY id DESC LIMIT 1) END,
        NOW()
    ) ON DUPLICATE KEY UPDATE
        unique_players_all_time = VALUES(unique_players_all_time),
        peak_concurrent_players = VALUES(peak_concurrent_players),
        peak_concurrent_date = VALUES(peak_concurrent_date),
        last_updated = VALUES(last_updated);

END //

CREATE PROCEDURE CleanupOldData()
BEGIN
    -- Clean old API logs (keep 30 days)
    DELETE FROM api_request_log WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY);

    -- Clean old rate limit entries (keep 7 days)
    DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL 7 DAY);

    -- Clean old concurrent player history (keep 90 days)
    DELETE FROM concurrent_player_history WHERE timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY);

    -- Clean old event logs (keep 60 days)
    DELETE FROM event_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL 60 DAY);

    -- Clean old performance metrics (keep 30 days)
    DELETE FROM performance_metrics WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY);

    -- Clean expired sessions
    DELETE FROM user_sessions WHERE expires_at < NOW();

END //

DELIMITER ;

-- Grant necessary permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON hyperabyss_cluster.* TO 'hyperabyss_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE hyperabyss_cluster.UpdatePlayerAnalytics TO 'hyperabyss_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE hyperabyss_cluster.CleanupOldData TO 'hyperabyss_user'@'localhost';

-- Final setup complete message
SELECT 'HyperAbyss database schema installation complete!' as message;
