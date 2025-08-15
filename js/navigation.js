/**
 * Navigation Component for HyperAbyss ARK Cluster
 * Handles mobile menu, status updates, and navigation interactions
 */

const NavigationComponent = {
    elements: {},
    isMenuOpen: false,
    
    init() {
        this.bindElements();
        this.bindEvents();
        this.updateStatus();
        this.startStatusUpdates();
        
        console.log('Navigation component initialized');
    },
    
    bindElements() {
        this.elements = {
            navbar: document.querySelector('.navbar'),
            toggle: document.querySelector('.navbar-toggle'),
            menu: document.querySelector('.navbar-menu'),
            statusDot: document.querySelector('.status-dot'),
            statusText: document.querySelector('.status-text'),
            navLinks: document.querySelectorAll('.nav-link')
        };
    },
    
    bindEvents() {
        // Mobile toggle
        if (this.elements.toggle) {
            this.elements.toggle.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleMobileMenu();
            });
        }
        
        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (this.isMenuOpen && !this.elements.navbar?.contains(e.target)) {
                this.closeMobileMenu();
            }
        });
        
        // Close menu on window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                this.closeMobileMenu();
            }
        });
        
        // Handle nav link clicks
        this.elements.navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                // Close mobile menu on link click
                if (this.isMenuOpen) {
                    this.closeMobileMenu();
                }
                
                // Add active state
                this.setActiveLink(link);
                
                // Handle external links
                if (link.hasAttribute('target') && link.getAttribute('target') === '_blank') {
                    // Let the browser handle external links naturally
                    return;
                }
                
                // Handle internal navigation
                const href = link.getAttribute('href');
                if (href && !href.startsWith('#')) {
                    // Add loading state for page transitions
                    this.showNavigationLoading();
                }
            });
        });
        
        // Scroll-based navbar styling
        let lastScrollY = window.scrollY;
        const throttledScroll = window.HyperAbyss.utils.throttle(() => {
            const currentScrollY = window.scrollY;
            
            if (this.elements.navbar) {
                if (currentScrollY > 100) {
                    this.elements.navbar.classList.add('navbar-scrolled');
                } else {
                    this.elements.navbar.classList.remove('navbar-scrolled');
                }
                
                // Hide/show navbar on scroll
                if (currentScrollY > lastScrollY && currentScrollY > 200) {
                    this.elements.navbar.style.transform = 'translateY(-100%)';
                } else {
                    this.elements.navbar.style.transform = 'translateY(0)';
                }
            }
            
            lastScrollY = currentScrollY;
        }, 100);
        
        window.addEventListener('scroll', throttledScroll);
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isMenuOpen) {
                this.closeMobileMenu();
            }
        });
    },
    
    toggleMobileMenu() {
        if (this.isMenuOpen) {
            this.closeMobileMenu();
        } else {
            this.openMobileMenu();
        }
    },
    
    openMobileMenu() {
        if (!this.elements.menu || !this.elements.toggle) return;
        
        this.isMenuOpen = true;
        this.elements.menu.classList.add('active');
        this.elements.toggle.classList.add('active');
        this.elements.toggle.setAttribute('aria-expanded', 'true');
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
        // Focus management
        const firstFocusable = this.elements.menu.querySelector('a, button');
        if (firstFocusable) {
            firstFocusable.focus();
        }
        
        // Emit event
        window.HyperAbyss.utils.emit('navigation-menu-opened');
    },
    
    closeMobileMenu() {
        if (!this.elements.menu || !this.elements.toggle) return;
        
        this.isMenuOpen = false;
        this.elements.menu.classList.remove('active');
        this.elements.toggle.classList.remove('active');
        this.elements.toggle.setAttribute('aria-expanded', 'false');
        
        // Restore body scroll
        document.body.style.overflow = '';
        
        // Emit event
        window.HyperAbyss.utils.emit('navigation-menu-closed');
    },
    
    setActiveLink(activeLink) {
        // Remove active class from all links
        this.elements.navLinks.forEach(link => {
            link.closest('.nav-item')?.classList.remove('nav-active');
        });
        
        // Add active class to current link
        activeLink.closest('.nav-item')?.classList.add('nav-active');
    },
    
    showNavigationLoading() {
        if (this.elements.navbar) {
            this.elements.navbar.classList.add('loading');
        }
    },
    
    hideNavigationLoading() {
        if (this.elements.navbar) {
            this.elements.navbar.classList.remove('loading');
        }
    },
    
    async updateStatus() {
        try {
            const data = await window.HyperAbyss.utils.apiRequest('servers');
            
            if (data && data.data && data.data.meta) {
                const { online_servers, total_servers } = data.data.meta;
                this.setServerStatus(online_servers, total_servers);
            }
        } catch (error) {
            console.warn('Failed to update navigation status:', error);
            this.setServerStatus(0, 0, 'error');
        }
    },
    
    setServerStatus(onlineServers, totalServers, statusOverride = null) {
        if (!this.elements.statusDot || !this.elements.statusText) return;
        
        let statusClass, statusText;
        
        if (statusOverride === 'error') {
            statusClass = 'status-offline';
            statusText = 'Connection Error';
        } else if (onlineServers === totalServers && totalServers > 0) {
            statusClass = 'status-online';
            statusText = `All servers online (${totalServers})`;
        } else if (onlineServers > 0) {
            statusClass = 'status-loading';
            statusText = `${onlineServers}/${totalServers} online`;
        } else {
            statusClass = 'status-offline';
            statusText = 'All servers offline';
        }
        
        // Update status dot
        this.elements.statusDot.className = `status-dot ${statusClass}`;
        
        // Update status text
        this.elements.statusText.textContent = statusText;
        
        // Emit status update event
        window.HyperAbyss.utils.emit('server-status-updated', {
            online: onlineServers,
            total: totalServers,
            status: statusClass
        });
    },
    
    startStatusUpdates() {
        // Initial update
        this.updateStatus();
        
        // Update every 30 seconds
        setInterval(() => {
            this.updateStatus();
        }, 30000);
        
        // Update when page becomes visible
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.updateStatus();
            }
        });
    },
    
    // Public methods for external use
    showStatus(message, type = 'info', duration = 3000) {
        const statusElement = this.elements.statusText;
        if (!statusElement) return;
        
        const originalText = statusElement.textContent;
        const originalClass = this.elements.statusDot.className;
        
        // Set temporary status
        statusElement.textContent = message;
        this.elements.statusDot.className = `status-dot status-${type}`;
        
        // Restore original status after duration
        setTimeout(() => {
            statusElement.textContent = originalText;
            this.elements.statusDot.className = originalClass;
        }, duration);
    },
    
    highlightNavItem(href) {
        const link = document.querySelector(`.nav-link[href="${href}"]`);
        if (link) {
            this.setActiveLink(link);
        }
    },
    
    addNavItem(text, href, icon = null, position = 'end') {
        if (!this.elements.menu) return;
        
        const navList = this.elements.menu.querySelector('.navbar-nav');
        if (!navList) return;
        
        const listItem = document.createElement('li');
        listItem.className = 'nav-item';
        
        const link = document.createElement('a');
        link.className = 'nav-link';
        link.href = href;
        
        if (icon) {
            const iconElement = document.createElement('i');
            iconElement.className = icon;
            link.appendChild(iconElement);
        }
        
        const textElement = document.createElement('span');
        textElement.className = 'nav-text';
        textElement.textContent = text;
        link.appendChild(textElement);
        
        listItem.appendChild(link);
        
        if (position === 'start') {
            navList.insertBefore(listItem, navList.firstChild);
        } else {
            navList.appendChild(listItem);
        }
        
        // Bind events for new item
        link.addEventListener('click', (e) => {
            if (this.isMenuOpen) {
                this.closeMobileMenu();
            }
            this.setActiveLink(link);
        });
    }
};

// Global function for backward compatibility
window.toggleMobileMenu = function() {
    NavigationComponent.toggleMobileMenu();
};

window.updateNavbarStatus = function(onlineServers, totalServers) {
    NavigationComponent.setServerStatus(onlineServers, totalServers);
};

// Register component
window.HyperAbyss.utils.registerComponent('navigation', NavigationComponent);

// Add CSS for additional states
const style = document.createElement('style');
style.textContent = `
    .navbar.navbar-scrolled {
        background: rgba(26, 26, 46, 0.95);
        backdrop-filter: blur(20px);
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
    }
    
    .navbar.loading::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background: linear-gradient(45deg, var(--primary-blue), var(--secondary-blue));
        animation: loading-bar 2s infinite;
    }
    
    @keyframes loading-bar {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    
    .navbar {
        transition: transform 0.3s ease, background 0.3s ease;
    }
    
    @media (max-width: 768px) {
        .navbar-menu.active {
            animation: slideDown 0.3s ease;
        }
    }
    
    @keyframes slideDown {
        from { transform: translateY(-100vh); }
        to { transform: translateY(0); }
    }
`;

document.head.appendChild(style);