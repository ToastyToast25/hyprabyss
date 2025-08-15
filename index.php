<?php
/**
 * HyperAbyss ARK Cluster Homepage
 * Modernized with PHP 8.4 and separated concerns
 */

declare(strict_types=1);

use HyperAbyss\Config;
use HyperAbyss\Database;
use HyperAbyss\Views\LayoutConfig;

require_once 'classes/Config.php';
require_once 'views/layout.php';

// Initialize configuration
$config = Config::getInstance();

// Get analytics data
try {
    $analytics = Database::fetchOne("SELECT * FROM player_analytics ORDER BY id DESC LIMIT 1") ?: [
        'unique_players_all_time' => 0,
        'peak_concurrent_players' => 0,
        'peak_concurrent_date' => null
    ];
    
    $discordStats = Database::fetchOne("SELECT * FROM discord_stats ORDER BY id DESC LIMIT 1") ?: [
        'member_count' => 500,
        'online_count' => 50
    ];
    
    $latestNews = Database::fetchAll("SELECT * FROM news WHERE is_published = 1 ORDER BY published_at DESC LIMIT 3");
    
    $serverCount = Database::fetchOne("SELECT COUNT(*) as count FROM servers WHERE is_active = 1")['count'] ?? 0;
    
    $avgUptime = Database::fetchOne("
        SELECT AVG(uptime_percentage) as avg_uptime 
        FROM server_uptime_history 
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS)
    ")['avg_uptime'] ?? 99.5;
    
} catch (Exception $e) {
    // Fallback data if database is unavailable
    $analytics = ['unique_players_all_time' => 0, 'peak_concurrent_players' => 0, 'peak_concurrent_date' => null];
    $discordStats = ['member_count' => 500, 'online_count' => 50];
    $latestNews = [];
    $serverCount = 1;
    $avgUptime = 99.5;
}

// Page configuration
$layoutConfig = new LayoutConfig(
    title: 'HyperAbyss ARK Cluster - The Ultimate Survival Experience',
    description: "Join HyperAbyss ARK: Survival Ascended cluster. {$analytics['unique_players_all_time']} unique players, professional management, 3X rates, ORP protection. Start your survival journey today!",
    keywords: ['ARK Survival Ascended', 'ARK cluster', 'PvP server', 'gaming community', 'survival game', 'multiplayer'],
    currentPage: 'home',
    additionalCSS: ['/css/homepage.css'],
    additionalJS: ['/js/homepage.js'],
    metaTags: [
        'og:title' => "HyperAbyss ARK Cluster - {$analytics['unique_players_all_time']} Players Strong",
        'og:description' => "The most active ARK: Survival Ascended cluster with balanced rates, professional management, and an amazing community. Peak: {$analytics['peak_concurrent_players']} concurrent players!"
    ]
);

// Render the page
echo HyperAbyss\Views\renderLayout($layoutConfig, function() use ($analytics, $discordStats, $latestNews, $serverCount, $avgUptime) {
?>

<!-- Hero Section -->
<section class="hero section">
    <div class="container">
        <div class="hero-content">
            <div class="hero-logo" aria-label="HyperAbyss Logo">
                🚀
            </div>
            <h1 class="hero-title">HYPERABYSS</h1>
            <h2 class="hero-subtitle">ARK Survival Ascended Cluster</h2>
            <p class="hero-description">
                Experience the ultimate survival adventure with balanced rates, professional management, 
                and the most active ARK community. Your survival journey starts here.
            </p>
            
            <div class="hero-stats grid grid-auto-sm" role="region" aria-label="Server Statistics">
                <div class="stat-card">
                    <span class="stat-number" id="live-players" aria-live="polite">-</span>
                    <span class="stat-label">Players Online</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= number_format($analytics['unique_players_all_time']) ?></span>
                    <span class="stat-label">Total Players</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $analytics['peak_concurrent_players'] ?></span>
                    <span class="stat-label">Peak Record</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= number_format($avgUptime, 1) ?>%</span>
                    <span class="stat-label">Uptime</span>
                </div>
            </div>
            
            <div class="hero-actions">
                <a href="/servers" class="btn btn-primary">
                    <i class="fas fa-server" aria-hidden="true"></i>
                    View Servers
                </a>
                <a href="https://discord.gg/hyperabyss" class="btn btn-secondary" target="_blank" rel="noopener">
                    <i class="fab fa-discord" aria-hidden="true"></i>
                    Join Discord
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features section">
    <div class="container">
        <header class="section-header">
            <h2 class="section-title">Why Choose HyperAbyss?</h2>
            <p class="section-subtitle">
                We've crafted the perfect balance of challenge and enjoyment, 
                with features designed to enhance your ARK experience.
            </p>
        </header>
        
        <div class="features-grid grid grid-auto">
            <article class="feature-card card animate-fade-in">
                <div class="feature-icon">
                    <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
                </div>
                <h3 class="feature-title">Balanced Rates</h3>
                <p class="feature-description">
                    3X gathering, breeding, and XP rates provide the perfect balance between 
                    progression and challenge. No excessive grinding, just pure fun.
                </p>
            </article>
            
            <article class="feature-card card animate-fade-in">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt" aria-hidden="true"></i>
                </div>
                <h3 class="feature-title">Offline Raid Protection</h3>
                <p class="feature-description">
                    Sleep peacefully knowing your base is protected. Our ORP system 
                    ensures fair play and prevents offline griefing.
                </p>
            </article>
            
            <article class="feature-card card animate-fade-in">
                <div class="feature-icon">
                    <i class="fas fa-users" aria-hidden="true"></i>
                </div>
                <h3 class="feature-title">Active Community</h3>
                <p class="feature-description">
                    Join <?= number_format($analytics['unique_players_all_time']) ?> players in our Discord community. Events, 
                    competitions, and friendly competition await.
                </p>
            </article>
            
            <article class="feature-card card animate-fade-in">
                <div class="feature-icon">
                    <i class="fas fa-cogs" aria-hidden="true"></i>
                </div>
                <h3 class="feature-title">Professional Management</h3>
                <p class="feature-description">
                    Dedicated admin team ensures fair play, quick support, and 
                    regular updates. Professional hosting with <?= number_format($avgUptime, 1) ?>% uptime.
                </p>
            </article>
            
            <article class="feature-card card animate-fade-in">
                <div class="feature-icon">
                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                </div>
                <h3 class="feature-title">Regular Events</h3>
                <p class="feature-description">
                    Weekly events, seasonal competitions, and special challenges 
                    keep the gameplay fresh and exciting for everyone.
                </p>
            </article>
            
            <article class="feature-card card animate-fade-in">
                <div class="feature-icon">
                    <i class="fas fa-headset" aria-hidden="true"></i>
                </div>
                <h3 class="feature-title">24/7 Support</h3>
                <p class="feature-description">
                    Our admin team is always available to help. Quick response times 
                    and professional support when you need it most.
                </p>
            </article>
        </div>
    </div>
</section>

<!-- Discord Section -->
<section class="discord-section section">
    <div class="container">
        <div class="discord-content grid grid-2">
            <div class="discord-text">
                <h2>
                    <i class="fas fa-rocket" aria-hidden="true"></i>
                    Join Our Thriving Community
                </h2>
                <p>
                    HyperAbyss isn't just about servers - we're a family of survival enthusiasts 
                    who share the passion for exploration, building, and conquering the ARK together.
                </p>
                <p>
                    Connect with like-minded players, share your adventures, get help when you need it, 
                    and participate in community events that bring everyone together.
                </p>
                
                <div class="discord-features grid grid-2">
                    <div class="discord-feature">
                        <i class="fas fa-comments" aria-hidden="true"></i>
                        <span>Real-time Chat</span>
                    </div>
                    <div class="discord-feature">
                        <i class="fas fa-bell" aria-hidden="true"></i>
                        <span>Server Alerts</span>
                    </div>
                    <div class="discord-feature">
                        <i class="fas fa-users" aria-hidden="true"></i>
                        <span>LFG Channels</span>
                    </div>
                    <div class="discord-feature">
                        <i class="fas fa-exchange-alt" aria-hidden="true"></i>
                        <span>Trading Post</span>
                    </div>
                    <div class="discord-feature">
                        <i class="fas fa-trophy" aria-hidden="true"></i>
                        <span>Events & Competitions</span>
                    </div>
                    <div class="discord-feature">
                        <i class="fas fa-headset" aria-hidden="true"></i>
                        <span>Voice Channels</span>
                    </div>
                </div>
            </div>
            
            <div class="discord-widget card animate-scale-in">
                <div class="discord-logo">
                    <i class="fab fa-discord" aria-hidden="true"></i>
                </div>
                <h3>Discord Community</h3>
                <p>
                    Join the most active ARK community with real-time updates, 
                    helpful members, and 24/7 admin support!
                </p>
                
                <div class="discord-stats grid grid-3">
                    <div class="discord-stat">
                        <span class="discord-stat-number"><?= number_format($discordStats['member_count']) ?></span>
                        <span class="discord-stat-label">Members</span>
                    </div>
                    <div class="discord-stat">
                        <span class="discord-stat-number"><?= $discordStats['online_count'] ?></span>
                        <span class="discord-stat-label">Online</span>
                    </div>
                    <div class="discord-stat">
                        <span class="discord-stat-number">15+</span>
                        <span class="discord-stat-label">Channels</span>
                    </div>
                </div>
                
                <a href="https://discord.gg/hyperabyss" class="btn btn-primary discord-join-btn" target="_blank" rel="noopener">
                    <i class="fab fa-discord" aria-hidden="true"></i>
                    Join Our Discord
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Servers Showcase -->
<section class="servers section">
    <div class="container">
        <header class="section-header">
            <h2 class="section-title">Our Servers</h2>
            <p class="section-subtitle">
                Multiple maps, different playstyles, one amazing community. 
                Choose your perfect survival experience.
            </p>
        </header>
        
        <div class="servers-grid grid grid-auto" id="servers-showcase">
            <div class="loading">Loading server information...</div>
        </div>
        
        <div class="text-center mt-xl">
            <a href="/servers" class="btn btn-secondary">
                <i class="fas fa-server" aria-hidden="true"></i>
                View All Servers
            </a>
        </div>
    </div>
</section>

<!-- News Section -->
<section class="news section">
    <div class="container">
        <header class="section-header">
            <h2 class="section-title">Latest News & Updates</h2>
            <p class="section-subtitle">
                Stay informed about server updates, community events, and ARK news
            </p>
        </header>
        
        <div class="news-grid grid grid-auto">
            <?php if (!empty($latestNews)): ?>
                <?php foreach ($latestNews as $news): ?>
                    <article class="news-card card animate-fade-in">
                        <div class="news-image">
                            <i class="fas fa-newspaper" aria-hidden="true"></i>
                        </div>
                        <div class="news-content">
                            <time class="news-date" datetime="<?= date('Y-m-d', strtotime($news['published_at'])) ?>">
                                <?= date('F j, Y', strtotime($news['published_at'])) ?>
                            </time>
                            <h3 class="news-title"><?= htmlspecialchars($news['title']) ?></h3>
                            <p class="news-excerpt">
                                <?= htmlspecialchars($news['excerpt'] ?: substr(strip_tags($news['content']), 0, 150) . '...') ?>
                            </p>
                            <a href="/news/<?= $news['id'] ?>" class="news-link">
                                Read More <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Default news items -->
                <article class="news-card card animate-fade-in">
                    <div class="news-image">
                        <i class="fas fa-rocket" aria-hidden="true"></i>
                    </div>
                    <div class="news-content">
                        <time class="news-date" datetime="2024-12-15">December 15, 2024</time>
                        <h3 class="news-title">HyperAbyss Cluster Launch</h3>
                        <p class="news-excerpt">
                            Welcome to the official launch of HyperAbyss ARK Cluster! 
                            Experience balanced gameplay with professional management.
                        </p>
                        <a href="/news" class="news-link">
                            Read More <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </article>
                
                <article class="news-card card animate-fade-in">
                    <div class="news-image">
                        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                    </div>
                    <div class="news-content">
                        <time class="news-date" datetime="2024-12-10">December 10, 2024</time>
                        <h3 class="news-title">Weekly Events Starting Soon</h3>
                        <p class="news-excerpt">
                            Join our weekly community events! Boss fights, building competitions, 
                            and special challenges with amazing rewards.
                        </p>
                        <a href="/news" class="news-link">
                            Read More <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </article>
                
                <article class="news-card card animate-fade-in">
                    <div class="news-image">
                        <i class="fas fa-shield-alt" aria-hidden="true"></i>
                    </div>
                    <div class="news-content">
                        <time class="news-date" datetime="2024-12-05">December 5, 2024</time>
                        <h3 class="news-title">Enhanced ORP System</h3>
                        <p class="news-excerpt">
                            Our new offline raid protection system ensures fair gameplay 
                            while maintaining the competitive PvP experience.
                        </p>
                        <a href="/news" class="news-link">
                            Read More <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </article>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-xl">
            <a href="/news" class="btn btn-secondary">
                <i class="fas fa-newspaper" aria-hidden="true"></i>
                View All News
            </a>
        </div>
    </div>
</section>

<?php
});
?>