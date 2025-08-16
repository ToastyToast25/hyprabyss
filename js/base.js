/**
 * Base JavaScript for HyperAbyss ARK Cluster
 * Core functionality and utilities
 */

// Global app object
window.HyperAbyss = {
    config: {
        apiUrl: '/api/enhanced-api.php',
        refreshInterval: 30000,
        animationDuration: 300,
        debounceDelay: 250
    },
    cache: new Map(),
    utils: {},
    components: {},
    events: new EventTarget()
};

const { config, cache, utils, components, events } = window.HyperAbyss;

/**
 * Utility Functions
 */
utils.debounce = function(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

utils.throttle = function(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
};

utils.formatNumber = function(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
};

utils.formatUptime = function(seconds) {
    if (seconds < 60) return `${seconds}s`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ${Math.floor((seconds % 3600) / 60)}m`;
    return `${Math.floor(seconds / 86400)}d ${Math.floor((seconds % 86400) / 3600)}h`;
};

utils.animateNumber = function(element, targetValue, duration = 1000) {
    const startValue = parseInt(element.textContent.replace(/[^\d]/g, '')) || 0;
    const increment = (targetValue - startValue) / (duration / 16);
    let current = startValue;
    
    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= targetValue) || (increment < 0 && current <= targetValue)) {
            element.textContent = targetValue.toLocaleString();
            clearInterval(timer);
        } else {
            element.textContent = Math.round(current).toLocaleString();
        }
    }, 16);
};

utils.copyToClipboard = async function(text) {
    try {
        await navigator.clipboard.writeText(text);
        return true;
    } catch (err) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            return true;
        } catch (err) {
            return false;
        } finally {
            document.body.removeChild(textArea);
        }
    }
};

utils.sanitizeHtml = function(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
};

/**
 * API Helper Functions
 */
utils.apiRequest = async function(endpoint, options = {}) {
    const cacheKey = `${endpoint}-${JSON.stringify(options)}`;
    const cached = cache.get(cacheKey);
    
    if (cached && Date.now() - cached.timestamp < 300000) {
        return cached.data;
    }
    
    try {
        const url = endpoint.startsWith('http') ? endpoint : `${config.apiUrl}?endpoint=${endpoint}`;
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.status === 'error') {
            throw new Error(data.error || 'API request failed');
        }
        
        cache.set(cacheKey, {
            data,
            timestamp: Date.now()
        });
        
        return data;
    } catch (error) {
        console.error('API request failed:', error);
        events.dispatchEvent(new CustomEvent('api-error', { detail: error }));
        throw error;
    }
};

/**
 * Performance Monitoring
 */
utils.performance = {
    mark: function(name) {
        if (window.performance && performance.mark) {
            performance.mark(name);
        }
    },
    
    measure: function(name, startMark, endMark) {
        if (window.performance && performance.measure) {
            try {
                performance.measure(name, startMark, endMark);
                const entries = performance.getEntriesByName(name);
                if (entries.length > 0) {
                    console.log(`${name}: ${entries[0].duration.toFixed(2)}ms`);
                }
            } catch (e) {
                console.warn('Performance measurement failed:', e);
            }
        }
    }
};

/**
 * Theme Management
 */
utils.theme = {
    get: function() {
        return localStorage.getItem('theme') || 'dark';
    },
    
    set: function(theme) {
        localStorage.setItem('theme', theme);
        document.documentElement.setAttribute('data-theme', theme);
        events.dispatchEvent(new CustomEvent('theme-changed', { detail: theme }));
    },
    
    toggle: function() {
        const current = this.get();
        this.set(current === 'dark' ? 'light' : 'dark');
    }
};

/**
 * Storage Utilities
 */
utils.storage = {
    set: function(key, value, expiry = null) {
        const item = {
            value,
            timestamp: Date.now(),
            expiry
        };
        localStorage.setItem(key, JSON.stringify(item));
    },
    
    get: function(key) {
        try {
            const item = JSON.parse(localStorage.getItem(key));
            if (!item) return null;
            
            if (item.expiry && Date.now() > item.timestamp + item.expiry) {
                localStorage.removeItem(key);
                return null;
            }
            
            return item.value;
        } catch (e) {
            return null;
        }
    },
    
    remove: function(key) {
        localStorage.removeItem(key);
    },
    
    clear: function() {
        localStorage.clear();
    }
};

/**
 * Modal System
 */
utils.modal = {
    open: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            const focusableElements = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            if (focusableElements.length > 0) {
                focusableElements[0].focus();
            }
        }
    },
    
    close: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    },
    
    closeAll: function() {
        document.querySelectorAll('.modal-overlay.active').forEach(modal => {
            modal.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
};

/**
 * Component Registration System
 */
utils.registerComponent = function(name, component) {
    components[name] = component;
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            component.init?.();
        });
    } else {
        component.init?.();
    }
};

/**
 * Event System
 */
utils.on = function(event, callback) {
    events.addEventListener(event, callback);
};

utils.emit = function(event, data) {
    events.dispatchEvent(new CustomEvent(event, { detail: data }));
};

utils.off = function(event, callback) {
    events.removeEventListener(event, callback);
};

/**
 * Intersection Observer for Animations
 */
utils.observeElements = function() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('in-view');
                
                const numbers = entry.target.querySelectorAll('[data-animate-number]');
                numbers.forEach(el => {
                    const target = parseInt(el.dataset.animateNumber);
                    if (target) {
                        utils.animateNumber(el, target);
                    }
                });
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    document.querySelectorAll('.animate-fade-in, .animate-scale-in, [data-animate]').forEach(el => {
        observer.observe(el);
    });
};

/**
 * Particle System
 */
utils.createParticles = function() {
    const particlesContainer = document.getElementById('particles');
    if (!particlesContainer) return;
    
    const particleCount = window.innerWidth < 768 ? 20 : 50;
    
    particlesContainer.innerHTML = '';
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 15 + 's';
        particle.style.animationDuration = (15 + Math.random() * 10) + 's';
        particlesContainer.appendChild(particle);
    }
};

/**
 * Error Handling
 */
utils.showError = function(message, duration = 5000) {
    const errorContainer = document.getElementById('error-container') || createErrorContainer();
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-notification';
    errorDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle"></i>
        <span>${utils.sanitizeHtml(message)}</span>
        <button class="error-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    errorContainer.appendChild(errorDiv);
    
    setTimeout(() => {
        if (errorDiv.parentElement) {
            errorDiv.remove();
        }
    }, duration);
    
    function createErrorContainer() {
        const container = document.createElement('div');
        container.id = 'error-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 400px;
        `;
        document.body.appendChild(container);
        return container;
    }
};

/**
 * Initialize Base Functionality
 */
function initializeBase() {
    utils.performance.mark('base-init-start');
    
    const savedTheme = utils.theme.get();
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    utils.createParticles();
    utils.observeElements();
    
    window.addEventListener('error', (e) => {
        console.error('Global error:', e.error);
        utils.showError('An unexpected error occurred. Please refresh the page.');
    });
    
    window.addEventListener('unhandledrejection', (e) => {
        console.error('Unhandled promise rejection:', e.reason);
        utils.showError('A network error occurred. Please check your connection.');
    });
    
    utils.on('api-error', (e) => {
        const error = e.detail;
        if (error.message.includes('fetch')) {
            utils.showError('Connection error. Please check your internet connection.');
        } else {
            utils.showError('Server error. Please try again later.');
        }
    });
    
    const debouncedResize = utils.debounce(() => {
        utils.createParticles();
    }, config.debounceDelay);
    
    window.addEventListener('resize', debouncedResize);
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            utils.modal.closeAll();
        }
    });
    
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal-overlay')) {
            utils.modal.close(e.target.id);
        }
    });
    
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a[href^="#"]');
        if (link) {
            e.preventDefault();
            const target = document.querySelector(link.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });
    
    document.addEventListener('click', (e) => {
        const button = e.target.closest('button[data-loading]');
        if (button && !button.disabled) {
            button.classList.add('loading');
            button.disabled = true;
            
            setTimeout(() => {
                button.classList.remove('loading');
                button.disabled = false;
            }, 5000);
        }
    });
    
    utils.performance.mark('base-init-end');
    utils.performance.measure('base-initialization', 'base-init-start', 'base-init-end');
    
    utils.emit('base-ready');
    
    console.log('HyperAbyss base system initialized');
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeBase);
} else {
    initializeBase();
}