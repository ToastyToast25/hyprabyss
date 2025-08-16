/**
 * Homepage Component for HyperAbyss ARK Cluster
 * Handles server showcase, live stats, and homepage interactions
 */

const HomepageComponent = {
    elements: {},
    refreshInterval: null,
    
    init() {
        if (!document.body.classList.contains('page-home')) {
            return;
        }
        
        this.bindElements();
        this.bindEvents();
        this.loadServerShowcase();
        this.updateLiveStats();
        this.startAutoRefresh();
        
        console.log('Homepage component initialized');
    },
    
    bindElements() {
        this.elements = {
            livePlayersCount: document.getElementById('live-players'),
            serversShowcase: document.getElementById('servers-showcase'),
            heroStats: document.querySelectorAll('.hero-stats .stat-number'),
            discordStats: {
                members: document.querySelector('#discord-members'),
                online: document.querySelector('#discord-online')
            },
            ctaButtons: document.querySelectorAll('.btn-primary, .btn-secondary'),
            featureCards: document.querySelectorAll('.feature-card'),
            newsCards: document.querySelectorAll('.news-card')
        };
    },
    
    bindEvents() {
        this.elements.ctaButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                this.trackButtonClick(button);
                this.addClickEffect(button);
            });
        });
        
        this.elements.featureCards.forEach((card, index) => {
            card.addEventListener('click', () => {
                this.showFeatureDetails(index);
            });
            
            card.addEventListener('mouseenter', () => {
                this.playHoverSound();
            });
        });
        
        this.elements.newsCards.forEach(card => {
            card.addEventListener('click', (e) => {
                if (!e.target.closest('a')) {
                    const link = card.querySelector('.news-link');
                    if (link) {
                        link.click();
                    }
                }
            });
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                const focusedCard = document.activeElement.closest('.feature-card, .news-card');
                if (focusedCard) {
                    e.preventDefault();
                    focusedCard.click();
                }
            }
        });
        
        this.setupAdvancedAnimations();
    },
    
    async loadServerShowcase() {
        const showcase = this.elements.serversShowcase;
        if (!showcase) return;
        
        try {
            showcase.innerHTML = '<div class="loading">Loading server information...</div>';
            
            const data = await window.HyperAbyss.utils.apiRequest('servers');
            
            if (data && data.data && data.data.servers) {
                this.renderServerShowcase(data.data.servers);
            } else {
                throw new Error('No server data available');
            }
        } catch (error) {
            console.error('Failed to load server showcase:', error);
            this.showServerShowcaseError();
        }
    },
    
    renderServerShowcase(servers) {
        const showcase = this.elements.serversShowcase;
        if (!showcase) return;
        
        const serverEntries = Object.entries(servers).slice(0, 2);
        
        if (serverEntries.length === 0) {
            showcase.innerHTML = '<div class="no-servers">No servers available</div>';
            return;
        }
        
        const serverCards = serverEntries.map(([key, server]) => {
            const statusClass = server.status === 'online' ? 'status-online' : 'status-offline';
            const statusIcon = server.status === 'online' ? '🟢' : '🔴';
            
            return `
                <article class="server-card card animate-fade-in">
                    <div class="server-status-badge ${statusClass}">
                        <span>${statusIcon}</span>
                        <span>${server.status.toUpperCase()}</span>
                    </div>
                    
                    <div class="server-image">
                        🏝️
                    </div>
                    
                    <div class="server-content">
                        <h3 class="server-name">${this.escapeHtml(server.name)}</h3>
                        <p class="server-description">
                            ${server.description || 'Experience epic survival adventures on this server.'}
                        </p>
                        
                        <div class="server-stats grid grid-2 mb-lg">
                            <div class="stat-item">
                                <div class="stat-label">Players</div>
                                <div class="stat-value">${server.players?.online || 0}/${server.players?.max || 150}</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Map</div>
                                <div class="stat-value">${server.server_info?.map || 'Unknown'}</div>
                            </div>
                        </div>
                        
                        <div class="server-features">
                            ${this.renderServerFeatures(server)}
                        </div>
                    </div>
                </article>
            `;
        }).join('');
        
        showcase.innerHTML = serverCards;
        
        setTimeout(() => {
            showcase.querySelectorAll('.animate-fade-in').forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('in-view');
                }, index * 200);
            });
        }, 100);
    },
    
    renderServerFeatures(server) {
        const features = [];
        
        if (server.server_info?.map) {
            features.push(server.server_info.map);
        }
        
        features.push('3X Rates', 'ORP', 'Active Community');
        
        return features.map(feature => 
            `<span class="server-feature">${this.escapeHtml(feature)}</span>`
        ).join('');
    },
    
    showServerShowcaseError() {
        const showcase = this.elements.serversShowcase;
        if (!showcase) return;
        
        showcase.innerHTML = `
            <div class="error text-center">
                <div class="error-title">Failed to Load Servers</div>
                <p>Unable to retrieve server information. Please try again later.</p>
                <button class="btn btn-secondary" onclick="location.reload()">
                    <i class="fas fa-refresh"></i>
                    Retry
                </button>
            </div>
        `;
    },
    
    async updateLiveStats() {
        try {
            const [serversData, analyticsData, discordData] = await Promise.all([
                window.HyperAbyss.utils.apiRequest('servers'),
                window.HyperAbyss.utils.apiRequest('analytics'),
                window.HyperAbyss.utils.apiRequest('discord')
            ]);
            
            if (serversData?.data?.meta?.total_players !== undefined) {
                this.updateLivePlayerCount(serversData.data.meta.total_players);
            }
            
            if (discordData?.data?.discord) {
                this.updateDiscordStats(discordData.data.discord);
            }
            
            window.HyperAbyss.utils.emit('homepage-stats-updated', {
                servers: serversData?.data,
                analytics: analyticsData?.data,
                discord: discordData?.data
            });
            
        } catch (error) {
            console.warn('Failed to update live stats:', error);
        }
    },
    
    updateLivePlayerCount(count) {
        const element = this.elements.livePlayersCount;
        if (!element) return;
        
        window.HyperAbyss.utils.animateNumber(element, count);
        
        element.classList.add('stat-updated');
        setTimeout(() => {
            element.classList.remove('stat-updated');
        }, 1000);
    },
    
    updateDiscordStats(discordData) {
        if (this.elements.discordStats.members && discordData.member_count) {
            window.HyperAbyss.utils.animateNumber(
                this.elements.discordStats.members, 
                discordData.member_count
            );
        }
        
        if (this.elements.discordStats.online && discordData.online_count) {
            window.HyperAbyss.utils.animateNumber(
                this.elements.discordStats.online, 
                discordData.online_count
            );
        }
    },
    
    startAutoRefresh() {
        this.updateLiveStats();
        
        this.refreshInterval = setInterval(() => {
            this.updateLiveStats();
        }, 30000);
        
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.updateLiveStats();
            }
        });
    },
    
    setupAdvancedAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    
                    if (element.classList.contains('hero-stats')) {
                        this.animateHeroStats(element);
                    } else if (element.classList.contains('feature-card')) {
                        this.animateFeatureCard(element);
                    } else if (element.classList.contains('discord-widget')) {
                        this.animateDiscordWidget(element);
                    }
                }
            });
        }, {
            threshold: 0.3,
            rootMargin: '0px 0px -100px 0px'
        });
        
        document.querySelectorAll('.hero-stats, .feature-card, .discord-widget').forEach(el => {
            observer.observe(el);
        });
    },
    
    animateHeroStats(element) {
        const stats = element.querySelectorAll('.stat-card');
        stats.forEach((stat, index) => {
            setTimeout(() => {
                stat.style.transform = 'translateY(0) scale(1)';
                stat.style.opacity = '1';
            }, index * 150);
        });
    },
    
    animateFeatureCard(element) {
        element.style.animation = 'bounceIn 0.6s ease forwards';
    },
    
    animateDiscordWidget(element) {
        element.style.animation = 'zoomIn 0.8s ease forwards';
    },
    
    addClickEffect(button) {
        button.classList.add('clicked');
        setTimeout(() => {
            button.classList.remove('clicked');
        }, 300);
    },
    
    trackButtonClick(button) {
        const buttonText = button.textContent.trim();
        const buttonHref = button.getAttribute('href');
        
        window.HyperAbyss.utils.emit('cta-click', {
            text: buttonText,
            href: buttonHref,
            timestamp: Date.now()
        });
        
        if (typeof gtag !== 'undefined') {
            gtag('event', 'click', {
                event_category: 'cta',
                event_label: buttonText
            });
        }
    },
    
    showFeatureDetails(index) {
        const features = [
            {
                title: 'Balanced Rates',
                details: 'Our 3X rates provide the perfect balance between progression and challenge. Spend more time playing and less time grinding.'
            },
            {
                title: 'Offline Raid Protection',
                details: 'Sleep peacefully knowing your base is protected when you\'re offline. Our ORP system prevents unfair raids.'
            },
            {
                title: 'Active Community',
                details: 'Join thousands of players in our Discord community. Regular events, helpful members, and 24/7 admin support.'
            },
            {
                title: 'Professional Management',
                details: 'Dedicated admin team with years of experience. Professional hosting, regular updates, and 99.5% uptime.'
            },
            {
                title: 'Regular Events',
                details: 'Weekly boss fights, building competitions, and seasonal events with amazing rewards and community prizes.'
            },
            {
                title: '24/7 Support',
                details: 'Our admin team is always available to help. Quick response times and professional support when you need it.'
            }
        ];
        
        const feature = features[index];
        if (!feature) return;
        
        const modal = this.createFeatureModal(feature);
        document.body.appendChild(modal);
        
        setTimeout(() => {
            modal.classList.add('active');
        }, 10);
    },
    
    createFeatureModal(feature) {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">${this.escapeHtml(feature.title)}</h3>
                    <button class="modal-close" onclick="this.closest('.modal-overlay').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p>${this.escapeHtml(feature.details)}</p>
                </div>
            </div>
        `;
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
        
        return modal;
    },
    
    playHoverSound() {
        const soundEnabled = window.HyperAbyss.utils.storage.get('sound-effects') !== false;
        if (!soundEnabled) return;
        
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
            gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.1);
        } catch (e) {
            // Ignore audio errors
        }
    },
    
    escapeHtml(text) {
        return window.HyperAbyss.utils.sanitizeHtml(text);
    },
    
    destroy() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
    }
};

window.HyperAbyss.utils.registerComponent('homepage', HomepageComponent);

const homepageStyle = document.createElement('style');
homepageStyle.textContent = `
    .stat-updated {
        animation: statPulse 1s ease;
    }
    
    @keyframes statPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); color: var(--secondary-blue); }
        100% { transform: scale(1); }
    }
    
    .btn.clicked {
        transform: scale(0.95);
        transition: transform 0.1s ease;
    }
    
    @keyframes bounceIn {
        0% {
            opacity: 0;
            transform: scale(0.3) translateY(50px);
        }
        50% {
            opacity: 1;
            transform: scale(1.05) translateY(-10px);
        }
        70% {
            transform: scale(0.9) translateY(0);
        }
        100% {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }
    
    @keyframes zoomIn {
        0% {
            opacity: 0;
            transform: scale(0.5);
        }
        100% {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    .feature-card {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .feature-card:hover {
        transform: translateY(-10px) scale(1.02);
    }
    
    .news-card {
        cursor: pointer;
    }
    
    .server-card .server-status-badge {
        animation: fadeInDown 0.6s ease;
    }
    
    @keyframes fadeInDown {
        0% {
            opacity: 0;
            transform: translateY(-20px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;

document.head.appendChild(homepageStyle);

window.addEventListener('beforeunload', () => {
    HomepageComponent.destroy();
});