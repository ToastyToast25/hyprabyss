// Servers page functionality
const ServersModule = {
    servers: [],
    refreshInterval: null,

    async init() {
        console.log('Servers module initializing...');
        await this.loadServers();
        this.startAutoRefresh();
    },

    async loadServers() {
        try {
            const response = await utils.apiRequest('servers');
            this.servers = response.data?.servers || [];
            this.renderServers();
        } catch (error) {
            console.error('Failed to load servers:', error);
            this.showError('Failed to load server data');
        }
    },

    renderServers() {
        const container = document.getElementById('servers-grid');
        if (!container) return;

        if (this.servers.length === 0) {
            container.innerHTML = '<div class="loading">No servers found</div>';
            return;
        }

        container.innerHTML = this.servers.map(server => `
            <div class="server-card">
                <h3>${server.name || 'Unknown Server'}</h3>
                <div class="server-status ${server.status === 'online' ? 'online' : 'offline'}">
                    ${server.status || 'Unknown'}
                </div>
                <div class="server-info">
                    <p><strong>Map:</strong> ${server.map || 'Unknown'}</p>
                    <p><strong>Players:</strong> ${server.current_players || 0}/${server.max_players || 0}</p>
                    <p><strong>Address:</strong> ${server.ip || 'Unknown'}:${server.port || 'Unknown'}</p>
                    ${server.last_updated ? `<p><strong>Last Updated:</strong> ${new Date(server.last_updated).toLocaleString()}</p>` : ''}
                </div>
            </div>
        `).join('');
    },

    showError(message) {
        const container = document.getElementById('servers-grid');
        if (container) {
            container.innerHTML = `<div class="error">${message}</div>`;
        }
    },

    startAutoRefresh() {
        this.refreshInterval = setInterval(() => {
            this.loadServers();
        }, 30000); // Refresh every 30 seconds
    },

    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    ServersModule.init();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    ServersModule.stopAutoRefresh();
});