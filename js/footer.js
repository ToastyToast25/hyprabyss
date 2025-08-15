/**
 * Footer Component for HyperAbyss ARK Cluster
 * Handles newsletter signup, stats updates, and footer interactions
 */

const FooterComponent = {
    elements: {},
    
    init() {
        this.bindElements();
        this.bindEvents();
        this.updateStats();
        this.startStatsUpdates();
        
        console.log('Footer component initialized');
    },
    
    bindElements() {
        this.elements = {
            footer: document.querySelector('.site-footer'),
            newsletterForm: document.getElementById('newsletter-form'),
            newsletterInput: document.querySelector('.newsletter-input'),
            newsletterButton: document.querySelector('.newsletter-button'),
            newsletterStatus: document.getElementById('newsletter-status'),
            totalPlayersFooter: document.getElementById('total-players-footer'),
            serversOnlineFooter: document.getElementById('servers-online-footer'),
            socialLinks: document.querySelectorAll('.social-link'),
            footerLinks: document.querySelectorAll('.footer-link')
        };
    },
    
    bindEvents() {
        // Newsletter form submission
        if (this.elements.newsletterForm) {
            this.elements.newsletterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleNewsletterSubmission();
            });
        }
        
        // Social link tracking
        this.elements.socialLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                const platform = this.getSocialPlatform(link.href);
                this.trackSocialClick(platform);
                
                // Add visual feedback
                link.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    link.style.transform = '';
                }, 150);
            });
        });
        
        // Footer link hover effects
        this.elements.footerLinks.forEach(link => {
            link.addEventListener('mouseenter', () => {
                link.style.transform = 'translateX(5px)';
            });
            
            link.addEventListener('mouseleave', () => {
                link.style.transform = '';
            });
        });
        
        // Copy server IP functionality
        const copyButtons = document.querySelectorAll('[data-copy]');
        copyButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const textToCopy = button.dataset.copy;
                this.copyToClipboard(textToCopy, button);
            });
        });
    },
    
    async handleNewsletterSubmission() {
        const email = this.elements.newsletterInput?.value?.trim();
        
        if (!email) {
            this.showNewsletterStatus('Please enter your email address', 'error');
            return;
        }
        
        if (!this.isValidEmail(email)) {
            this.showNewsletterStatus('Please enter a valid email address', 'error');
            return;
        }
        
        // Show loading state
        this.setNewsletterLoading(true);
        this.showNewsletterStatus('Subscribing...', 'loading');
        
        try {
            const response = await fetch('/api/newsletter', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email })
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                this.showNewsletterStatus('Successfully subscribed! Welcome to the community!', 'success');
                this.elements.newsletterForm?.reset();
                
                // Track successful subscription
                this.trackNewsletterSignup(email);
            } else {
                throw new Error(result.message || 'Subscription failed');
            }
        } catch (error) {
            console.error('Newsletter subscription error:', error);
            this.showNewsletterStatus(error.message || 'Subscription failed. Please try again.', 'error');
        } finally {
            this.setNewsletterLoading(false);
        }
    },
    
    setNewsletterLoading(loading) {
        if (!this.elements.newsletterButton) return;
        
        this.elements.newsletterButton.disabled = loading;
        this.elements.newsletterInput.disabled = loading;
        
        if (loading) {
            this.elements.newsletterButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        } else {
            this.elements.newsletterButton.innerHTML = '<i class="fas fa-paper-plane"></i>';
        }
    },
    
    showNewsletterStatus(message, type) {
        if (!this.elements.newsletterStatus) return;
        
        this.elements.newsletterStatus.textContent = message;
        this.elements.newsletterStatus.className = `newsletter-status newsletter-${type}`;
        
        // Clear status after delay (except for success messages)
        if (type !== 'success') {
            setTimeout(() => {
                this.elements.newsletterStatus.textContent = '';
                this.elements.newsletterStatus.className = 'newsletter-status';
            }, 5000);
        }
    },
    
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },
    
    async updateStats() {
        try {
            const data = await window.HyperAbyss.utils.apiRequest('servers');
            
            if (data && data.data && data.data.meta) {
                const { total_players, online_servers } = data.data.meta;
                this.setStats(total_players, online_servers);
            }
        } catch (error) {
            console.warn('Failed to update footer stats:', error);
            // Keep current values or show placeholder
        }
    },
    
    setStats(totalPlayers, serversOnline) {
        if (this.elements.totalPlayersFooter) {
            window.HyperAbyss.utils.animateNumber(this.elements.totalPlayersFooter, totalPlayers);
        }
        
        if (this.elements.serversOnlineFooter) {
            window.HyperAbyss.utils.animateNumber(this.elements.serversOnlineFooter, serversOnline);
        }
        
        // Emit stats update event
        window.HyperAbyss.utils.emit('footer-stats-updated', {
            totalPlayers,
            serversOnline
        });
    },
    
    startStatsUpdates() {
        // Initial update
        this.updateStats();
        
        // Update every 30 seconds
        setInterval(() => {
            this.updateStats();
        }, 30000);
        
        // Listen for external stats updates
        window.HyperAbyss.utils.on('server-stats-updated', (e) => {
            const { totalPlayers, serversOnline } = e.detail;
            this.setStats(totalPlayers, serversOnline);
        });
    },
    
    getSocialPlatform(url) {
        if (url.includes('discord')) return 'discord';
        if (url.includes('twitter')) return 'twitter';
        if (url.includes('youtube')) return 'youtube';
        if (url.includes('steam')) return 'steam';
        if (url.includes('facebook')) return 'facebook';
        if (url.includes('reddit')) return 'reddit';
        return 'unknown';
    },
    
    trackSocialClick(platform) {
        window.HyperAbyss.utils.emit('social-click', { platform });
        
        // Analytics tracking (if available)
        if (typeof gtag !== 'undefined') {
            gtag('event', 'click', {
                event_category: 'social',
                event_label: platform
            });
        }
    },
    
    trackNewsletterSignup(email) {
        window.HyperAbyss.utils.emit('newsletter-signup', { email });
        
        // Analytics tracking (if available)
        if (typeof gtag !== 'undefined') {
            gtag('event', 'newsletter_signup', {
                event_category: 'engagement',
                event_label: 'footer'
            });
        }
    },
    
    async copyToClipboard(text, button) {
        try {
            const success = await window.HyperAbyss.utils.copyToClipboard(text);
            
            if (success) {
                // Show success feedback
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Copied!';
                button.style.background = 'var(--status-online)';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.background = '';
                }, 2000);
            } else {
                throw new Error('Copy failed');
            }
        } catch (error) {
            console.error('Copy to clipboard failed:', error);
            
            // Show error feedback
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-times"></i> Failed';
            button.style.background = 'var(--status-offline)';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '';
            }, 2000);
        }
    },
    
    // Public methods for external use
    updatePlayerCount(count) {
        if (this.elements.totalPlayersFooter) {
            window.HyperAbyss.utils.animateNumber(this.elements.totalPlayersFooter, count);
        }
    },
    
    updateServerCount(count) {
        if (this.elements.serversOnlineFooter) {
            window.HyperAbyss.utils.animateNumber(this.elements.serversOnlineFooter, count);
        }
    },
    
    showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `footer-notification footer-notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info'}"></i>
            <span>${window.HyperAbyss.utils.sanitizeHtml(message)}</span>
            <button onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        // Add to footer
        if (this.elements.footer) {
            this.elements.footer.appendChild(notification);
            
            // Auto-remove after duration
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, duration);
        }
    }
};

// Global function for backward compatibility
window.updateFooterStats = function(totalPlayers, serversOnline) {
    FooterComponent.setStats(totalPlayers, serversOnline);
};

// Register component
window.HyperAbyss.utils.registerComponent('footer', FooterComponent);

// Add CSS for notifications
const style = document.createElement('style');
style.textContent = `
    .footer-notification-success {
        border-color: var(--status-online);
        background: rgba(76, 175, 80, 0.1);
    }
    
    .footer-notification-error {
        border-color: var(--status-offline);
        background: rgba(244, 67, 54, 0.1);
    }
    
    .footer-notification-info {
        border-color: var(--primary-blue);
        background: rgba(33, 150, 243, 0.1);
    }
    
    .footer-notification button {
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        padding: var(--spacing-xs);
        border-radius: var(--radius-sm);
        transition: all var(--transition-fast);
    }
    
    .footer-notification button:hover {
        color: var(--text-primary);
        background: var(--glass-bg);
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .newsletter-status.newsletter-loading {
        color: var(--status-warning);
    }
    
    .social-link {
        position: relative;
        overflow: hidden;
    }
    
    .social-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle at center, rgba(255,255,255,0.2) 0%, transparent 70%);
        opacity: 0;
        transition: opacity var(--transition-normal);
    }
    
    .social-link:hover::before {
        opacity: 1;
    }
    
    @media (max-width: 768px) {
        .footer-notification {
            bottom: 10px;
            right: 10px;
            left: 10px;
            max-width: none;
        }
    }
`;

document.head.appendChild(style); {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: var(--space-dark);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-lg);
        padding: var(--spacing-md) var(--spacing-lg);
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
        max-width: 400px;
        z-index: 10000;
        animation: slideInRight 0.3s ease;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    }
    
    .footer-notification