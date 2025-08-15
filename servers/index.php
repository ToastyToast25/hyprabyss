<?php
/**
 * HyperAbyss Server Dashboard
 * Modern PHP 8.4 server management interface
 */

declare(strict_types=1);

use HyperAbyss\Config;
use HyperAbyss\Views\LayoutConfig;

require_once '../classes/Config.php';
require_once '../views/layout.php';

// Initialize configuration
$config = Config::getInstance();

// Page configuration
$layoutConfig = new LayoutConfig(
    title: 'Server Status - HyperAbyss ARK Cluster',
    description: 'Real-time server status dashboard for HyperAbyss ARK Cluster. Monitor player counts, server performance, and uptime.',
    keywords: ['ARK servers', 'server status', 'gaming dashboard', 'real-time monitoring'],
    currentPage: 'servers',
    additionalCSS: ['/css/servers.css'],
    additionalJS: ['/js/servers.js']
);

// Render the page
echo HyperAbyss\Views\renderLayout($layoutConfig, function() {
?>

<div class="dashboard-container">
    <!-- Dashboard Header -->
    <header class="dashboard-header">
        <div class="container">
            <div class="header-content">
                <h1 class="dashboard-title">
                    <i class="fas fa-server" aria-hidden="true"></i>
                    HyperAbyss Server Dashboard
                </h1>
                <p class="dashboard-subtitle">Real-time Server Status & Performance Monitoring</p>
                
                <!-- Cluster Stats Overview -->
                <div class="cluster-overview grid grid-auto-sm" role="region" aria-label="Cluster Overview">
                    <div class="overview-stat">
                        <span class="stat-number" id="cluster-total-servers">-</span>
                        <span class="stat-label">Total Servers</span>
                    </div>
                    <div class="overview-stat">
                        <span class="stat-number" id="cluster-online-servers">-</span>
                        <span class="stat-label">Online Servers</span>
                    </div>
                    <div class="overview-stat">
                        <span class="stat-number" id="cluster-total-players">-</span>
                        <span class="stat-label">Total Players</span>
                    </div>
                    <div class="overview-stat">
                        <span class="stat-number" id="cluster-avg-ping">-</span>
                        <span class="stat-label">Avg Response</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Dashboard Controls -->
    <section class="dashboard-controls">
        <div class="container">
            <div class="controls-content">
                <div class="control-group">
                    <button class="btn btn-primary" id="refresh-btn" onclick="refreshServerData()">
                        <i class="fas fa-sync-alt"></i>
                        <span>Refresh All</span>
                    </button>
                    <button class="btn btn-secondary" id="auto-refresh-btn" onclick="toggleAutoRefresh()">
                        <i class="fas fa-play"></i>
                        <span id="auto-refresh-text">Start Auto-Refresh</span>
                    </button>
                </div>
                
                <div class="control-group">
                    <div class="refresh-info" id="refresh-info">
                        <span class="refresh-status">Last updated: <time id="last-update">Never</time></span>
                        <span class="next-refresh" id="next-refresh" style="display: none;">
                            Next refresh in: <span class="countdown" id="refresh-countdown">--</span>s
                        </span>
                    </div>
                </div>
                
                <div class="control-group">
                    <div class="view-options">
                        <button class="view-btn active" data-view="grid" title="Grid View">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button class="view-btn" data-view="list" title="List View">
                            <i class="fas fa-list"></i>
                        </button>
                        <button class="view-btn" data-view="compact" title="Compact View">
                            <i class="fas fa-th"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Server Grid -->
    <main class="dashboard-main">
        <div class="container">
            <div class="servers-container" id="servers-container">
                <div class="servers-grid" id="servers-grid">
                    <div class="loading-state">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Loading server data...</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Server Details Modal -->
    <div class="modal-overlay" id="server-details-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-server-name">Server Details</h3>
                <button class="modal-close" onclick="closeServerModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modal-server-content">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>

    <!-- Error Toast Container -->
    <div class="toast-container" id="toast-container"></div>
</div>

<style>
/* Dashboard Specific Styles */
.dashboard-container {
    min-height: 100vh;
    padding-top: 0;
}

.dashboard-header {
    background: linear-gradient(135deg, var(--space-dark), var(--space-purple));
    padding: var(--spacing-2xl) 0;
    border-bottom: 1px solid var(--glass-border);
}

.header-content {
    text-align: center;
}

.dashboard-title {
    font-size: clamp(2rem, 5vw, 3rem);
    margin-bottom: var(--spacing-md);
    color: var(--primary-blue);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-md);
}

.dashboard-subtitle {
    color: var(--text-muted);
    font-size: 1.2rem;
    margin-bottom: var(--spacing-2xl);
}

.cluster-overview {
    max-width: 800px;
    margin: 0 auto;
}

.overview-stat {
    background: var(--glass-bg);
    padding: var(--spacing-lg);
    border-radius: var(--radius-lg);
    text-align: center;
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(20px);
}

.dashboard-controls {
    background: rgba(0, 0, 0, 0.2);
    padding: var(--spacing-lg) 0;
    border-bottom: 1px solid var(--glass-border);
}

.controls-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--spacing-lg);
}

.control-group {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
}

.refresh-info {
    font-size: 0.9rem;
    color: var(--text-muted);
}

.countdown {
    color: var(--primary-blue);
    font-weight: 600;
}

.view-options {
    display: flex;
    background: var(--glass-bg);
    border-radius: var(--radius-md);
    padding: var(--spacing-xs);
    gap: var(--spacing-xs);
}

.view-btn {
    background: none;
    border: none;
    color: var(--text-muted);
    padding: var(--spacing-sm);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all var(--transition-normal);
}

.view-btn.active,
.view-btn:hover {
    background: var(--primary-blue);
    color: white;
}

.dashboard-main {
    padding: var(--spacing-2xl) 0;
}

.servers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: var(--spacing-xl);
}

.servers-grid.view-list {
    grid-template-columns: 1fr;
}

.servers-grid.view-compact {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--spacing-lg);
}

.loading-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: var(--spacing-2xl);
    color: var(--text-muted);
    font-size: 1.2rem;
}

.loading-state i {
    font-size: 2rem;
    margin-bottom: var(--spacing-md);
    display: block;
}

/* Server Cards */
.server-card {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-xl);
    padding: var(--spacing-xl);
    transition: all var(--transition-normal);
    position: relative;
    overflow: hidden;
    cursor: pointer;
}

.server-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--status-offline);
    transition: background var(--transition-normal);
}

.server-card.status-online::before { background: var(--status-online); }
.server-card.status-offline::before { background: var(--status-offline); }
.server-card.status-crashed::before { background: var(--status-warning); }

.server-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
    border-color: var(--primary-blue);
}

.server-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-lg);
}

.server-name {
    color: var(--text-primary);
    font-size: 1.3rem;
    font-weight: 600;
    margin: 0;
    line-height: 1.3;
}

.server-status-badge {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: var(--spacing-xs) var(--spacing-md);
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.server-status-badge.online {
    background: var(--status-online);
    color: white;
}

.server-status-badge.offline {
    background: var(--status-offline);
    color: white;
}

.server-metrics {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.metric-item {
    background: rgba(255, 255, 255, 0.05);
    padding: var(--spacing-md);
    border-radius: var(--radius-md);
    text-align: center;
}

.metric-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-blue);
    display: block;
}

.metric-label {
    font-size: 0.8rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.players-section {
    background: rgba(255, 255, 255, 0.05);
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
}

.players-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-md);
}

.player-count {
    background: var(--primary-blue);
    color: white;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 600;
}

.players-list {
    max-height: 150px;
    overflow-y: auto;
}

.player-item {
    background: rgba(255, 255, 255, 0.1);
    margin-bottom: var(--spacing-xs);
    padding: var(--spacing-sm);
    border-radius: var(--radius-sm);
    border-left: 3px solid var(--primary-blue);
}

.player-name {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.9rem;
}

.player-id {
    font-size: 0.7rem;
    color: var(--text-muted);
    font-family: monospace;
}

.no-players {
    text-align: center;
    color: var(--text-muted);
    padding: var(--spacing-lg);
    font-style: italic;
}

/* Toast Notifications */
.toast-container {
    position: fixed;
    top: 90px;
    right: 20px;
    z-index: 10000;
    max-width: 400px;
}

.toast {
    background: var(--space-dark);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-lg);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-sm);
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    animation: slideInRight 0.3s ease;
    box-shadow: var(--shadow-lg);
}

.toast.success { border-color: var(--status-online); }
.toast.error { border-color: var(--status-offline); }
.toast.warning { border-color: var(--status-warning); }

@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

/* Responsive Design */
@media (max-width: 768px) {
    .controls-content {
        flex-direction: column;
        align-items: stretch;
    }
    
    .servers-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-title {
        font-size: 2rem;
        flex-direction: column;
        gap: var(--spacing-sm);
    }
    
    .toast-container {
        left: 10px;
        right: 10px;
        max-width: none;
    }
}
</style>

<?php
});
?>