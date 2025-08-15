<?php
/**
 * Enhanced Footer Component for HyperAbyss ARK Cluster
 * PHP 8.4 compatible with modern features and accessibility
 */

declare(strict_types=1);

namespace HyperAbyss\Components;

readonly class FooterConfig
{
    public function __construct(
        public string $brandName = 'HyperAbyss ARK Cluster',
        public string $brandDescription = 'The ultimate ARK: Survival Ascended multiplayer experience with custom rates, events, and an amazing community.',
        public string $brandLogo = '/assets/images/logo.png',
        public array $sections = [],
        public array $socialLinks = [],
        public array $legalLinks = [],
        public bool $showStats = true,
        public bool $showNewsletter = true,
        public string $style = 'dark'
    ) {}
}

function renderFooter(array $customConfig = []): string
{
    $defaultSections = [
        'servers' => [
            'title' => 'Our Servers',
            'icon' => 'fas fa-server',
            'links' => [
                'Ragnarok PvP' => '/servers#ragnarok',
                'The Island' => '/servers#island',
                'The Center' => '/servers#center',
                'Server Rules' => '/rules',
                'Status Dashboard' => '/servers'
            ]
        ],
        'community' => [
            'title' => 'Community',
            'icon' => 'fas fa-users',
            'links' => [
                'Discord Server' => 'https://discord.gg/hyperabyss',
                'Latest News' => '/news',
                'Events Calendar' => '/events',
                'Player Guides' => '/guides',
                'Support Center' => '/support'
            ]
        ],
        'shop' => [
            'title' => 'Shop & Support',
            'icon' => 'fas fa-shopping-cart',
            'links' => [
                'Character Skins' => '/shop/skins',
                'Tools & Items' => '/shop/tools',
                'Creatures & Tames' => '/shop/creatures',
                'VIP Membership' => '/shop/vip',
                'Donate' => '/donate'
            ]
        ],
        'resources' => [
            'title' => 'Resources',
            'icon' => 'fas fa-book',
            'links' => [
                'Getting Started' => '/guide/getting-started',
                'Server Commands' => '/guide/commands',
                'Mod List' => '/guide/mods',
                'FAQ' => '/faq',
                'Contact Us' => '/contact'
            ]
        ]
    ];

    $defaultSocial = [
        'discord' => [
            'url' => 'https://discord.gg/hyperabyss',
            'icon' => 'fab fa-discord',
            'name' => 'Discord'
        ],
        'youtube' => [
            'url' => 'https://youtube.com/@hyperabyss',
            'icon' => 'fab fa-youtube',
            'name' => 'YouTube'
        ],
        'twitter' => [
            'url' => 'https://twitter.com/hyperabyss',
            'icon' => 'fab fa-twitter',
            'name' => 'Twitter'
        ],
        'steam' => [
            'url' => 'https://steamcommunity.com/groups/hyperabyss',
            'icon' => 'fab fa-steam',
            'name' => 'Steam Group'
        ]
    ];

    $defaultLegal = [
        'Privacy Policy' => '/privacy',
        'Terms of Service' => '/terms',
        'Cookie Policy' => '/cookies',
        'DMCA' => '/dmca'
    ];

    $config = new FooterConfig(
        sections: array_merge($defaultSections, $customConfig['sections'] ?? []),
        socialLinks: array_merge($defaultSocial, $customConfig['social'] ?? []),
        legalLinks: array_merge($defaultLegal, $customConfig['legal'] ?? []),
        showStats: $customConfig['showStats'] ?? true,
        showNewsletter: $customConfig['showNewsletter'] ?? true,
        style: $customConfig['style'] ?? 'dark'
    );

    $styleClass = "footer-{$config->style}";

    ob_start();
    ?>
    <footer class="site-footer <?= $styleClass ?>" role="contentinfo">
        <!-- Main Footer Content -->
        <div class="footer-main">
            <div class="footer-container">
                <div class="footer-grid">
                    <!-- Brand Section -->
                    <div class="footer-brand">
                        <div class="brand-header">
                            <?php if ($config->brandLogo): ?>
                                <img src="<?= htmlspecialchars($config->brandLogo) ?>" 
                                     alt="<?= htmlspecialchars($config->brandName) ?> Logo" 
                                     class="footer-logo"
                                     width="40" height="40">
                            <?php endif; ?>
                            <h3 class="footer-brand-name"><?= htmlspecialchars($config->brandName) ?></h3>
                        </div>
                        
                        <p class="footer-description"><?= htmlspecialchars($config->brandDescription) ?></p>
                        
                        <?php if ($config->showStats): ?>
                        <!-- Live Statistics -->
                        <div class="footer-stats" id="footer-stats" role="region" aria-label="Live Server Statistics">
                            <div class="stat-item">
                                <span class="stat-number" id="total-players-footer" aria-live="polite">-</span>
                                <span class="stat-label">Players Online</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number" id="servers-online-footer" aria-live="polite">-</span>
                                <span class="stat-label">Servers Online</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number" id="uptime-footer">99.5%</span>
                                <span class="stat-label">Uptime</span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Social Links -->
                        <div class="footer-social" role="region" aria-label="Social Media Links">
                            <?php foreach ($config->socialLinks as $platform => $social): ?>
                                <a href="<?= htmlspecialchars($social['url']) ?>" 
                                   class="social-link social-<?= htmlspecialchars($platform) ?>"
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   aria-label="Follow us on <?= htmlspecialchars($social['name']) ?>">
                                    <i class="<?= htmlspecialchars($social['icon']) ?>" aria-hidden="true"></i>
                                    <span class="social-text"><?= htmlspecialchars($social['name']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Navigation Sections -->
                    <?php foreach ($config->sections as $sectionKey => $section): ?>
                        <div class="footer-section">
                            <h4 class="footer-section-title">
                                <?php if (!empty($section['icon'])): ?>
                                    <i class="<?= htmlspecialchars($section['icon']) ?>" aria-hidden="true"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($section['title']) ?>
                            </h4>
                            <ul class="footer-links" role="list">
                                <?php foreach ($section['links'] as $linkText => $linkUrl): ?>
                                    <li role="listitem">
                                        <a href="<?= htmlspecialchars($linkUrl) ?>" 
                                           class="footer-link"
                                           <?= str_starts_with($linkUrl, 'http') ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                                            <?= htmlspecialchars($linkText) ?>
                                            <?php if (str_starts_with($linkUrl, 'http')): ?>
                                                <i class="fas fa-external-link-alt external-icon" aria-hidden="true"></i>
                                                <span class="sr-only">(opens in new tab)</span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ($config->showNewsletter): ?>
                    <!-- Newsletter Signup -->
                    <div class="footer-section footer-newsletter">
                        <h4 class="footer-section-title">
                            <i class="fas fa-envelope" aria-hidden="true"></i>
                            Stay Updated
                        </h4>
                        <p class="newsletter-description">
                            Get notified about server events, updates, and community news. 
                            Join thousands of survivors in our newsletter!
                        </p>
                        
                        <form class="newsletter-form" id="newsletter-form" aria-label="Newsletter Signup">
                            <div class="input-group">
                                <label for="newsletter-email" class="sr-only">Email Address</label>
                                <input type="email" 
                                       id="newsletter-email"
                                       class="newsletter-input" 
                                       placeholder="Enter your email" 
                                       required
                                       aria-describedby="newsletter-status">
                                <button type="submit" 
                                        class="newsletter-button"
                                        aria-label="Subscribe to newsletter">
                                    <i class="fas fa-paper-plane" aria-hidden="true"></i>
                                    <span class="sr-only">Subscribe</span>
                                </button>
                            </div>
                            <div class="newsletter-status" 
                                 id="newsletter-status" 
                                 role="status" 
                                 aria-live="polite" 
                                 aria-atomic="true"></div>
                        </form>
                        
                        <!-- Newsletter Benefits -->
                        <div class="newsletter-benefits">
                            <div class="benefit-item">
                                <i class="fas fa-bell" aria-hidden="true"></i>
                                <span>Event Notifications</span>
                            </div>
                            <div class="benefit-item">
                                <i class="fas fa-gift" aria-hidden="true"></i>
                                <span>Exclusive Rewards</span>
                            </div>
                            <div class="benefit-item">
                                <i class="fas fa-newspaper" aria-hidden="true"></i>
                                <span>Server Updates</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Server Status Banner -->
        <div class="footer-status-banner" id="footer-status-banner">
            <div class="footer-container">
                <div class="status-banner-content">
                    <div class="status-info">
                        <div class="status-indicator">
                            <div class="status-dot status-online" aria-hidden="true"></div>
                            <span class="status-text">All Systems Operational</span>
                        </div>
                        <div class="status-details">
                            <span id="cluster-status-text">Cluster running smoothly</span>
                        </div>
                    </div>
                    <div class="status-actions">
                        <a href="/servers" class="status-link">
                            <i class="fas fa-chart-line" aria-hidden="true"></i>
                            View Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="footer-container">
                <div class="footer-bottom-content">
                    <div class="footer-copyright">
                        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($config->brandName) ?>. All rights reserved.</p>
                        <p class="footer-game-disclaimer">
                            ARK: Survival Ascended is a trademark of Studio Wildcard. 
                            This server is not affiliated with or endorsed by Studio Wildcard.
                        </p>
                    </div>
                    
                    <div class="footer-legal">
                        <?php foreach ($config->legalLinks as $page => $url): ?>
                            <a href="<?= htmlspecialchars($url) ?>" class="legal-link">
                                <?= htmlspecialchars($page) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="footer-info">
                        <div class="server-info">
                            <span class="info-item">
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                Last updated: <time id="last-update-time">-</time>
                            </span>
                            <span class="info-item">
                                <i class="fas fa-server" aria-hidden="true"></i>
                                API v2.0
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <style>
    /* Enhanced Footer Styles */
    .site-footer {
        background: linear-gradient(145deg, var(--space-dark), var(--space-purple));
        color: var(--text-secondary);
        margin-top: auto;
        position: relative;
        overflow: hidden;
    }

    .site-footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="footer-pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="0.5" fill="%23ffffff" opacity="0.05"/></pattern></defs><rect width="100" height="100" fill="url(%23footer-pattern)"/></svg>');
        opacity: 0.3;
        z-index: 0;
    }

    .footer-main {
        padding: 4rem 0 2rem;
        position: relative;
        z-index: 1;
    }

    .footer-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 var(--spacing-lg);
    }

    .footer-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr 1.5fr;
        gap: 3rem;
        align-items: start;
    }

    /* Enhanced Brand Section */
    .footer-brand {
        max-width: 400px;
    }

    .brand-header {
        display: flex;
        align-items: center;
        margin-bottom: var(--spacing-lg);
        gap: var(--spacing-md);
    }

    .footer-logo {
        border-radius: 50%;
        filter: drop-shadow(0 4px 8px rgba(33, 150, 243, 0.3));
        transition: transform var(--transition-normal);
    }

    .brand-header:hover .footer-logo {
        transform: rotate(5deg) scale(1.1);
    }

    .footer-brand-name {
        margin: 0;
        background: linear-gradient(45deg, var(--primary-blue), var(--secondary-blue));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-size: 1.4rem;
        font-family: 'Orbitron', monospace;
        font-weight: 700;
    }

    .footer-description {
        color: var(--text-muted);
        line-height: 1.7;
        margin-bottom: var(--spacing-xl);
        font-size: 1rem;
    }

    /* Enhanced Statistics */
    .footer-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: var(--spacing-lg);
        margin-bottom: var(--spacing-xl);
        padding: var(--spacing-lg) 0;
        border-top: 1px solid var(--glass-border);
        border-bottom: 1px solid var(--glass-border);
    }

    .stat-item {
        text-align: center;
        padding: var(--spacing-md);
        background: rgba(255, 255, 255, 0.05);
        border-radius: var(--radius-lg);
        transition: all var(--transition-normal);
        position: relative;
        overflow: hidden;
    }

    .stat-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(33, 150, 243, 0.1), transparent);
        transition: left var(--transition-slow);
    }

    .stat-item:hover::before {
        left: 100%;
    }

    .stat-item:hover {
        transform: translateY(-2px);
        background: rgba(255, 255, 255, 0.08);
    }

    .stat-number {
        display: block;
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary-blue);
        margin-bottom: var(--spacing-xs);
        font-family: 'Orbitron', monospace;
    }

    .stat-label {
        font-size: 0.85rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 500;
    }

    /* Enhanced Social Links */
    .footer-social {
        display: flex;
        gap: var(--spacing-md);
        flex-wrap: wrap;
    }

    .social-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        color: var(--text-secondary);
        text-decoration: none;
        transition: all var(--transition-normal);
        position: relative;
        overflow: hidden;
    }

    .social-link::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: linear-gradient(45deg, var(--primary-blue), var(--secondary-blue));
        opacity: 0;
        transition: opacity var(--transition-normal);
    }

    .social-link:hover::before, .social-link:focus::before {
        opacity: 1;
    }

    .social-link:hover, .social-link:focus {
        color: white;
        transform: translateY(-3px) scale(1.1);
        outline: none;
        box-shadow: 0 8px 20px rgba(33, 150, 243, 0.4);
    }

    .social-link i {
        position: relative;
        z-index: 1;
        font-size: 1.2rem;
    }

    .social-text {
        display: none;
    }

    /* Platform-specific colors */
    .social-discord:hover { box-shadow: 0 8px 20px rgba(88, 101, 242, 0.4); }
    .social-youtube:hover { box-shadow: 0 8px 20px rgba(255, 0, 0, 0.4); }
    .social-twitter:hover { box-shadow: 0 8px 20px rgba(29, 161, 242, 0.4); }
    .social-steam:hover { box-shadow: 0 8px 20px rgba(23, 26, 33, 0.4); }

    /* Footer Sections */
    .footer-section-title {
        color: var(--text-primary);
        margin: 0 0 var(--spacing-lg) 0;
        font-size: 1.1rem;
        font-weight: 600;
        position: relative;
        padding-bottom: var(--spacing-sm);
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
    }

    .footer-section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 2px;
        background: linear-gradient(45deg, var(--primary-blue), var(--secondary-blue));
        border-radius: 2px;
    }

    .footer-links {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .footer-links li {
        margin-bottom: var(--spacing-sm);
    }

    .footer-link {
        color: var(--text-muted);
        text-decoration: none;
        transition: all var(--transition-normal);
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-xs);
        padding: var(--spacing-xs) 0;
    }

    .footer-link:hover, .footer-link:focus {
        color: var(--primary-blue);
        padding-left: var(--spacing-sm);
        outline: none;
    }

    .external-icon {
        font-size: 0.7rem;
        opacity: 0.7;
    }

    /* Enhanced Newsletter */
    .footer-newsletter .newsletter-description {
        color: var(--text-muted);
        font-size: 0.95rem;
        margin-bottom: var(--spacing-lg);
        line-height: 1.6;
    }

    .newsletter-form {
        margin-bottom: var(--spacing-lg);
    }

    .input-group {
        display: flex;
        margin-bottom: var(--spacing-md);
        border-radius: var(--radius-lg);
        overflow: hidden;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid var(--glass-border);
        transition: all var(--transition-normal);
    }

    .input-group:focus-within {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
    }

    .newsletter-input {
        flex: 1;
        padding: var(--spacing-md) var(--spacing-lg);
        background: transparent;
        border: none;
        color: var(--text-primary);
        font-size: 0.95rem;
        outline: none;
    }

    .newsletter-input::placeholder {
        color: var(--text-muted);
    }

    .newsletter-button {
        padding: var(--spacing-md) var(--spacing-lg);
        background: linear-gradient(45deg, var(--primary-blue), var(--primary-blue-dark));
        border: none;
        color: white;
        cursor: pointer;
        transition: all var(--transition-normal);
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 50px;
    }

    .newsletter-button:hover, .newsletter-button:focus {
        background: linear-gradient(45deg, var(--primary-blue-dark), var(--primary-blue));
        transform: scale(1.05);
        outline: none;
    }

    .newsletter-button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .newsletter-status {
        font-size: 0.85rem;
        margin-top: var(--spacing-xs);
        min-height: 20px;
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
    }

    .newsletter-success {
        color: var(--status-online);
    }

    .newsletter-error {
        color: var(--status-offline);
    }

    .newsletter-loading {
        color: var(--status-warning);
    }

    /* Newsletter Benefits */
    .newsletter-benefits {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: var(--spacing-sm);
    }

    .benefit-item {
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
        font-size: 0.85rem;
        color: var(--text-muted);
        padding: var(--spacing-xs);
        border-radius: var(--radius-sm);
        transition: all var(--transition-normal);
    }

    .benefit-item:hover {
        color: var(--primary-blue);
        background: rgba(33, 150, 243, 0.1);
    }

    .benefit-item i {
        color: var(--primary-blue);
        font-size: 0.9rem;
    }

    /* Status Banner */
    .footer-status-banner {
        background: rgba(0, 0, 0, 0.2);
        border-top: 1px solid var(--glass-border);
        padding: var(--spacing-lg) 0;
        position: relative;
        z-index: 1;
    }

    .status-banner-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: var(--spacing-lg);
    }

    .status-info {
        display: flex;
        align-items: center;
        gap: var(--spacing-lg);
    }

    .status-indicator {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
    }

    .status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        position: relative;
    }

    .status-dot::after {
        content: '';
        position: absolute;
        inset: -2px;
        border-radius: 50%;
        background: inherit;
        opacity: 0.3;
        animation: ping 2s infinite;
    }

    .status-text {
        font-weight: 600;
        color: var(--text-primary);
    }

    .status-details {
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    .status-link {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        color: var(--primary-blue);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all var(--transition-normal);
    }

    .status-link:hover, .status-link:focus {
        color: var(--secondary-blue);
        outline: none;
    }

    /* Footer Bottom */
    .footer-bottom {
        background: rgba(0, 0, 0, 0.3);
        padding: var(--spacing-lg) 0;
        border-top: 1px solid var(--glass-border);
        position: relative;
        z-index: 1;
    }

    .footer-bottom-content {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        gap: var(--spacing-lg);
        align-items: center;
    }

    .footer-copyright p {
        margin: 0;
        color: var(--text-muted);
        font-size: 0.9rem;
        line-height: 1.5;
    }

    .footer-game-disclaimer {
        font-size: 0.8rem !important;
        opacity: 0.8;
        margin-top: var(--spacing-xs) !important;
    }

    .footer-legal {
        display: flex;
        gap: var(--spacing-lg);
        justify-content: center;
        flex-wrap: wrap;
    }

    .legal-link {
        color: var(--text-muted);
        text-decoration: none;
        font-size: 0.9rem;
        transition: color var(--transition-normal);
        padding: var(--spacing-xs) 0;
    }

    .legal-link:hover, .legal-link:focus {
        color: var(--primary-blue);
        outline: none;
    }

    .footer-info {
        text-align: right;
    }

    .server-info {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-xs);
        font-size: 0.8rem;
        color: var(--text-muted);
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
        justify-content: flex-end;
    }

    /* Light Theme */
    .footer-light {
        background: linear-gradient(145deg, #f8f9fa, #e9ecef);
        color: #2c3e50;
    }

    .footer-light .footer-description,
    .footer-light .footer-link,
    .footer-light .footer-copyright p,
    .footer-light .legal-link,
    .footer-light .newsletter-description {
        color: #6c757d;
    }

    .footer-light .footer-section-title,
    .footer-light .footer-brand-name {
        color: #2c3e50;
    }

    .footer-light .social-link {
        background: rgba(0, 0, 0, 0.1);
        color: #2c3e50;
    }

    .footer-light .newsletter-input {
        background: rgba(0, 0, 0, 0.05);
        border-color: rgba(0, 0, 0, 0.1);
        color: #2c3e50;
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .footer-grid {
            grid-template-columns: 1fr 1fr 1fr;
        }
        
        .footer-brand {
            grid-column: 1 / -1;
            max-width: none;
            margin-bottom: var(--spacing-xl);
        }
    }

    @media (max-width: 768px) {
        .footer-grid {
            grid-template-columns: 1fr;
            gap: var(--spacing-xl);
        }
        
        .footer-stats {
            grid-template-columns: repeat(3, 1fr);
            gap: var(--spacing-md);
        }
        
        .footer-bottom-content {
            grid-template-columns: 1fr;
            text-align: center;
            gap: var(--spacing-md);
        }
        
        .footer-legal {
            justify-content: center;
        }
        
        .footer-info {
            text-align: center;
        }
        
        .info-item {
            justify-content: center;
        }
        
        .status-banner-content {
            flex-direction: column;
            text-align: center;
        }
    }

    @media (max-width: 480px) {
        .footer-stats {
            grid-template-columns: 1fr;
        }
        
        .newsletter-benefits {
            grid-template-columns: 1fr;
        }
        
        .footer-social {
            justify-content: center;
        }
    }

    /* Accessibility */
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    /* Animations */
    @keyframes ping {
        0% { transform: scale(1); opacity: 0.3; }
        100% { transform: scale(2); opacity: 0; }
    }

    @media (prefers-reduced-motion: reduce) {
        .footer-logo, .social-link, .stat-item, .status-dot::after {
            animation: none !important;
            transition: none !important;
        }
    }
    </style>
    <?php
    return ob_get_clean();
}

// Global compatibility function
if (!function_exists('updateFooterStats')) {
    function updateFooterStats(int $totalPlayers, int $serversOnline): void
    {
        // This will be handled by the JavaScript component
    }
}
?>