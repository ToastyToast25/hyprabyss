<?php
/**
 * Enhanced Navigation Component for HyperAbyss ARK Cluster
 * PHP 8.4 compatible with modern features and accessibility
 */

declare(strict_types=1);

namespace HyperAbyss\Components;

readonly class NavigationConfig
{
    public function __construct(
        public string $brandName = 'HyperAbyss',
        public string $brandLogo = '/assets/images/logo.png',
        public string $brandUrl = '/',
        public array $menuItems = [],
        public string $style = 'dark',
        public bool $showStatus = true,
        public bool $isFixed = true,
        public array $socialLinks = []
    ) {}
}

function renderNavigation(string $currentPage = '', array $customConfig = []): string
{
    $defaultItems = [
        'home' => [
            'name' => 'Home',
            'url' => '/',
            'icon' => 'fas fa-home',
            'description' => 'Homepage'
        ],
        'servers' => [
            'name' => 'Servers',
            'url' => '/servers',
            'icon' => 'fas fa-server',
            'description' => 'Server Status Dashboard'
        ],
        'shop' => [
            'name' => 'Shop',
            'url' => '/shop',
            'icon' => 'fas fa-shopping-cart',
            'description' => 'In-game Items & Perks'
        ],
        'news' => [
            'name' => 'News',
            'url' => '/news',
            'icon' => 'fas fa-newspaper',
            'description' => 'Latest Updates & Events'
        ],
        'discord' => [
            'name' => 'Discord',
            'url' => 'https://discord.gg/hyperabyss',
            'icon' => 'fab fa-discord',
            'description' => 'Join our Community',
            'external' => true
        ]
    ];

    $config = new NavigationConfig(
        menuItems: array_merge($defaultItems, $customConfig['items'] ?? []),
        style: $customConfig['style'] ?? 'dark',
        showStatus: $customConfig['showStatus'] ?? true,
        isFixed: $customConfig['fixed'] ?? true,
        socialLinks: $customConfig['socialLinks'] ?? []
    );

    $fixedClass = $config->isFixed ? 'navbar-fixed' : '';
    $styleClass = "navbar-{$config->style}";

    ob_start();
    ?>
    <nav class="navbar <?= $styleClass ?> <?= $fixedClass ?>" role="navigation" aria-label="Main navigation">
        <div class="navbar-container">
            <!-- Brand -->
            <div class="navbar-brand">
                <a href="<?= htmlspecialchars($config->brandUrl) ?>" class="brand-link" aria-label="<?= htmlspecialchars($config->brandName) ?> Homepage">
                    <?php if ($config->brandLogo): ?>
                        <img src="<?= htmlspecialchars($config->brandLogo) ?>" 
                             alt="<?= htmlspecialchars($config->brandName) ?> Logo" 
                             class="brand-logo"
                             width="40" height="40">
                    <?php endif; ?>
                    <span class="brand-text"><?= htmlspecialchars($config->brandName) ?></span>
                </a>
            </div>

            <!-- Mobile Toggle -->
            <button class="navbar-toggle" 
                    onclick="window.HyperAbyss?.components?.navigation?.toggleMobileMenu()"
                    aria-expanded="false"
                    aria-controls="navbar-menu"
                    aria-label="Toggle navigation menu">
                <span class="toggle-bar" aria-hidden="true"></span>
                <span class="toggle-bar" aria-hidden="true"></span>
                <span class="toggle-bar" aria-hidden="true"></span>
                <span class="sr-only">Menu</span>
            </button>

            <!-- Navigation Menu -->
            <div class="navbar-menu" id="navbar-menu">
                <ul class="navbar-nav" role="menubar">
                    <?php foreach ($config->menuItems as $key => $item): ?>
                        <?php
                        $isActive = $currentPage === $key;
                        $isExternal = !empty($item['external']);
                        $externalAttrs = $isExternal ? 'target="_blank" rel="noopener noreferrer"' : '';
                        $activeClass = $isActive ? 'nav-active' : '';
                        ?>
                        <li class="nav-item <?= $activeClass ?>" role="none">
                            <a href="<?= htmlspecialchars($item['url']) ?>" 
                               class="nav-link"
                               role="menuitem"
                               aria-current="<?= $isActive ? 'page' : 'false' ?>"
                               title="<?= htmlspecialchars($item['description'] ?? $item['name']) ?>"
                               <?= $externalAttrs ?>>
                                <?php if (!empty($item['icon'])): ?>
                                    <i class="<?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                                <?php endif; ?>
                                <span class="nav-text"><?= htmlspecialchars($item['name']) ?></span>
                                <?php if ($isExternal): ?>
                                    <i class="fas fa-external-link-alt external-icon" aria-hidden="true"></i>
                                    <span class="sr-only">(opens in new tab)</span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($config->showStatus): ?>
                <!-- Server Status Indicator -->
                <div class="navbar-status" id="navbar-status" role="status" aria-live="polite">
                    <div class="status-indicator">
                        <div class="status-dot status-loading" aria-hidden="true"></div>
                        <span class="status-text">Checking servers...</span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($config->socialLinks)): ?>
                <!-- Social Links (Mobile) -->
                <div class="navbar-social">
                    <?php foreach ($config->socialLinks as $platform => $url): ?>
                        <a href="<?= htmlspecialchars($url) ?>" 
                           class="social-link"
                           target="_blank" 
                           rel="noopener noreferrer"
                           aria-label="Follow us on <?= htmlspecialchars(ucfirst($platform)) ?>">
                            <i class="fab fa-<?= htmlspecialchars($platform) ?>" aria-hidden="true"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Progress Bar for Loading States -->
        <div class="navbar-progress" id="navbar-progress" aria-hidden="true">
            <div class="progress-bar"></div>
        </div>
    </nav>

    <style>
    /* Enhanced Navigation Styles */
    .navbar {
        background: linear-gradient(145deg, var(--space-dark), var(--space-purple));
        backdrop-filter: blur(15px);
        border-bottom: 1px solid var(--glass-border);
        z-index: 1000;
        transition: all var(--transition-normal);
        position: relative;
    }

    .navbar-fixed {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
    }

    .navbar-glass {
        background: rgba(26, 26, 46, 0.95);
        backdrop-filter: blur(20px);
    }

    .navbar-light {
        background: linear-gradient(145deg, #f8f9fa, #e9ecef);
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }

    .navbar-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 var(--spacing-lg);
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 70px;
    }

    /* Enhanced Brand */
    .brand-link {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: var(--text-primary);
        font-weight: 700;
        font-size: 1.5rem;
        transition: all var(--transition-normal);
        gap: var(--spacing-sm);
    }

    .brand-link:hover, .brand-link:focus {
        color: var(--primary-blue);
        transform: scale(1.02);
        outline: none;
    }

    .brand-logo {
        border-radius: 50%;
        transition: transform var(--transition-normal);
    }

    .brand-link:hover .brand-logo {
        transform: rotate(5deg) scale(1.1);
    }

    .brand-text {
        background: linear-gradient(45deg, var(--primary-blue), var(--secondary-blue));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-family: 'Orbitron', monospace;
    }

    /* Enhanced Navigation Menu */
    .navbar-menu {
        display: flex;
        align-items: center;
        gap: var(--spacing-lg);
    }

    .navbar-nav {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
        gap: var(--spacing-xs);
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        padding: var(--spacing-md) var(--spacing-lg);
        color: var(--text-secondary);
        text-decoration: none;
        border-radius: var(--radius-lg);
        transition: all var(--transition-normal);
        font-weight: 500;
        position: relative;
        overflow: hidden;
    }

    .nav-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(33, 150, 243, 0.1), transparent);
        transition: left var(--transition-slow);
    }

    .nav-link:hover::before, .nav-link:focus::before {
        left: 100%;
    }

    .nav-link:hover, .nav-link:focus {
        color: var(--primary-blue);
        background: rgba(33, 150, 243, 0.1);
        transform: translateY(-2px);
        outline: none;
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
    }

    .nav-active .nav-link {
        color: var(--primary-blue);
        background: rgba(33, 150, 243, 0.15);
        box-shadow: 0 2px 8px rgba(33, 150, 243, 0.3);
    }

    .external-icon {
        font-size: 0.7rem;
        opacity: 0.7;
        margin-left: var(--spacing-xs);
    }

    /* Enhanced Status Indicator */
    .navbar-status {
        margin-left: var(--spacing-lg);
        padding-left: var(--spacing-lg);
        border-left: 1px solid var(--glass-border);
    }

    .status-indicator {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        font-size: 0.9rem;
        padding: var(--spacing-sm) var(--spacing-md);
        border-radius: var(--radius-md);
        background: rgba(255, 255, 255, 0.05);
        transition: all var(--transition-normal);
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        transition: all var(--transition-normal);
        position: relative;
    }

    .status-dot::after {
        content: '';
        position: absolute;
        inset: -2px;
        border-radius: 50%;
        opacity: 0;
        transition: opacity var(--transition-normal);
    }

    .status-loading {
        background: var(--status-warning);
        animation: pulse 2s infinite;
    }

    .status-online {
        background: var(--status-online);
        box-shadow: 0 0 10px rgba(76, 175, 80, 0.5);
    }

    .status-online::after {
        background: var(--status-online);
        opacity: 0.3;
        animation: ping 2s infinite;
    }

    .status-offline {
        background: var(--status-offline);
        box-shadow: 0 0 10px rgba(244, 67, 54, 0.5);
    }

    .status-text {
        color: var(--text-muted);
        font-size: 0.85rem;
        font-weight: 500;
    }

    /* Mobile Toggle */
    .navbar-toggle {
        display: none;
        flex-direction: column;
        background: none;
        border: none;
        cursor: pointer;
        padding: var(--spacing-sm);
        gap: 4px;
        border-radius: var(--radius-sm);
        transition: all var(--transition-normal);
    }

    .navbar-toggle:hover, .navbar-toggle:focus {
        background: rgba(255, 255, 255, 0.1);
        outline: none;
    }

    .toggle-bar {
        width: 24px;
        height: 3px;
        background: var(--text-secondary);
        border-radius: 2px;
        transition: all var(--transition-normal);
        transform-origin: center;
    }

    .navbar-toggle.active .toggle-bar:nth-child(1) {
        transform: rotate(45deg) translate(6px, 6px);
    }

    .navbar-toggle.active .toggle-bar:nth-child(2) {
        opacity: 0;
        transform: scale(0);
    }

    .navbar-toggle.active .toggle-bar:nth-child(3) {
        transform: rotate(-45deg) translate(6px, -6px);
    }

    /* Progress Bar */
    .navbar-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background: rgba(255, 255, 255, 0.1);
        opacity: 0;
        transition: opacity var(--transition-normal);
    }

    .navbar-progress.active {
        opacity: 1;
    }

    .progress-bar {
        height: 100%;
        background: linear-gradient(45deg, var(--primary-blue), var(--secondary-blue));
        width: 0;
        transition: width var(--transition-normal);
        animation: progress-shimmer 2s infinite;
    }

    /* Social Links */
    .navbar-social {
        display: none;
        gap: var(--spacing-md);
        padding-top: var(--spacing-lg);
        border-top: 1px solid var(--glass-border);
        margin-top: var(--spacing-lg);
    }

    .social-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        color: var(--text-secondary);
        text-decoration: none;
        transition: all var(--transition-normal);
    }

    .social-link:hover, .social-link:focus {
        background: var(--primary-blue);
        color: white;
        transform: translateY(-2px) scale(1.1);
        outline: none;
    }

    /* Animations */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    @keyframes ping {
        0% { transform: scale(1); opacity: 0.3; }
        100% { transform: scale(2); opacity: 0; }
    }

    @keyframes progress-shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .navbar-toggle {
            display: flex;
        }

        .navbar-menu {
            position: fixed;
            top: 70px;
            left: 0;
            right: 0;
            background: rgba(26, 26, 46, 0.98);
            backdrop-filter: blur(20px);
            flex-direction: column;
            padding: var(--spacing-xl);
            transform: translateY(-100vh);
            transition: transform var(--transition-normal);
            border-bottom: 1px solid var(--glass-border);
            max-height: calc(100vh - 70px);
            overflow-y: auto;
        }

        .navbar-menu.active {
            transform: translateY(0);
        }

        .navbar-nav {
            flex-direction: column;
            width: 100%;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }

        .nav-link {
            justify-content: center;
            padding: var(--spacing-lg);
            width: 100%;
            border-radius: var(--radius-lg);
            font-size: 1.1rem;
        }

        .navbar-status {
            margin-left: 0;
            padding-left: 0;
            border-left: none;
            border-top: 1px solid var(--glass-border);
            padding-top: var(--spacing-lg);
            width: 100%;
            justify-content: center;
        }

        .navbar-social {
            display: flex;
            justify-content: center;
        }
    }

    /* Light Theme */
    .navbar-light .brand-link,
    .navbar-light .nav-link {
        color: #2c3e50;
    }

    .navbar-light .nav-link:hover, .navbar-light .nav-link:focus {
        color: var(--primary-blue);
        background: rgba(33, 150, 243, 0.1);
    }

    .navbar-light .status-text {
        color: #6c757d;
    }

    .navbar-light .toggle-bar {
        background: #2c3e50;
    }

    .navbar-light .navbar-menu {
        background: rgba(248, 249, 250, 0.98);
    }

    /* Accessibility */
    @media (prefers-reduced-motion: reduce) {
        .navbar *, .nav-link::before, .toggle-bar, .status-dot {
            animation: none !important;
            transition: none !important;
        }
    }

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
    </style>
    <?php
    return ob_get_clean();
}

// Global compatibility function
if (!function_exists('updateNavbarStatus')) {
    function updateNavbarStatus(int $onlineServers, int $totalServers): void
    {
        // This will be handled by the JavaScript component
    }
}
?>