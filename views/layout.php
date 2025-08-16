<?php
/**
 * Layout Template for HyperAbyss ARK Cluster
 * PHP 8.4 compatible boilerplate with modern HTML5
 */

declare(strict_types=1);

namespace HyperAbyss\Views;

readonly class LayoutConfig
{
    public function __construct(
        public string $title = 'HyperAbyss ARK Cluster',
        public string $description = 'The ultimate ARK: Survival Ascended multiplayer experience',
        public array $keywords = ['ARK', 'Survival', 'Ascended', 'PvP', 'Gaming'],
        public string $currentPage = 'home',
        public bool $includeAnalytics = true,
        public array $additionalCSS = [],
        public array $additionalJS = [],
        public array $metaTags = []
    ) {}
}

function renderLayout(LayoutConfig $config, callable $contentCallback): string
{
    ob_start();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($config->description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars(implode(', ', $config->keywords)) ?>">
    <meta name="robots" content="index, follow">
    <meta name="author" content="HyperAbyss Gaming Community">
    
    <title><?= htmlspecialchars($config->title) ?></title>
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($config->title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($config->description) ?>">
    <meta property="og:image" content="/assets/images/og-banner.jpg">
    <meta property="og:site_name" content="HyperAbyss ARK Cluster">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($config->title) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($config->description) ?>">
    <meta name="twitter:image" content="/assets/images/og-banner.jpg">
    
    <!-- Additional meta tags -->
    <?php foreach ($config->metaTags as $name => $content): ?>
    <meta name="<?= htmlspecialchars($name) ?>" content="<?= htmlspecialchars($content) ?>">
    <?php endforeach; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png">
    
    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Site CSS -->
    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/navigation.css">
    <link rel="stylesheet" href="/css/footer.css">
    <link rel="stylesheet" href="/css/components.css">
    
    <!-- Additional CSS -->
    <?php foreach ($config->additionalCSS as $cssFile): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssFile) ?>">
    <?php endforeach; ?>
    
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "HyperAbyss ARK Cluster",
        "url": "https://hyperabyss.com",
        "logo": "https://hyperabyss.com/assets/images/logo.png",
        "description": "Professional ARK: Survival Ascended gaming cluster",
        "sameAs": ["https://discord.gg/hyperabyss"]
    }
    </script>
</head>
<body class="page-<?= htmlspecialchars($config->currentPage) ?>">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <!-- Background effects -->
    <div class="starfield" aria-hidden="true"></div>
    <div class="particles" id="particles" aria-hidden="true"></div>
    
    <!-- Header -->
    <header role="banner">
        <?php include __DIR__ . '/../components/navigation.php'; ?>
    </header>
    
    <!-- Main content -->
    <main id="main-content" role="main" class="main-container">
        <?php $contentCallback(); ?>
    </main>
    
    <!-- Footer -->
    <footer role="contentinfo">
        <?php include __DIR__ . '/../components/footer.php'; ?>
    </footer>
    
    <!-- Core JavaScript -->
    <script src="/js/base.js"></script>
    <script src="/js/navigation.js"></script>
    <script src="/js/footer.js"></script>
    
    <!-- Additional JavaScript -->
    <?php foreach ($config->additionalJS as $jsFile): ?>
    <script src="<?= htmlspecialchars($jsFile) ?>"></script>
    <?php endforeach; ?>
    
    <?php if ($config->includeAnalytics): ?>
    <!-- Analytics (if enabled) -->
    <script>
        // Add your analytics code here
        console.log('Analytics placeholder');
    </script>
    <?php endif; ?>
</body>
</html>
    <?php
    return ob_get_clean();
}

/**
 * Helper function for quick page rendering
 */
function renderPage(string $title, callable $contentCallback, array $options = []): string
{
    $config = new LayoutConfig(
        title: $title,
        description: $options['description'] ?? 'HyperAbyss ARK Cluster - The ultimate survival experience',
        keywords: $options['keywords'] ?? ['ARK', 'Survival', 'Ascended', 'Gaming'],
        currentPage: $options['currentPage'] ?? 'home',
        includeAnalytics: $options['includeAnalytics'] ?? true,
        additionalCSS: $options['additionalCSS'] ?? [],
        additionalJS: $options['additionalJS'] ?? [],
        metaTags: $options['metaTags'] ?? []
    );
    
    return renderLayout($config, $contentCallback);
}
?>