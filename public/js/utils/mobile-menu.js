/**
 * Mobile Menu Handler
 * Manages responsive sidebar and mobile navigation
 */

class MobileMenu {
    constructor() {
        this.sidebar = null;
        this.toggleBtn = null;
        this.overlay = null;
        this.isOpen = false;
        this.isMobile = false;
        this.breakpoint = 768;
        this.init();
    }

    init() {
        this.sidebar = document.querySelector('.sidebar');
        if (!this.sidebar) return;

        this.checkViewport();
        this.createMobileUI();
        this.attachEventListeners();
        this.handleNavigation();

        // Listen for resize events
        window.addEventListener('resize', () => {
            this.checkViewport();
        });
    }

    checkViewport() {
        const wasDesktop = !this.isMobile;
        this.isMobile = window.innerWidth < this.breakpoint;

        // If switched from desktop to mobile, create toggle button
        if (this.isMobile && wasDesktop) {
            this.createMobileUI();
        }

        // If switched from mobile to desktop, remove mobile UI
        if (!this.isMobile && !wasDesktop) {
            this.removeMobileUI();
        }
    }

    createMobileUI() {
        if (!this.isMobile) return;

        // Remove if already exists
        const existing = document.getElementById('mobile-menu-toggle');
        if (existing) return;

        // Create hamburger button
        const toggleBtn = document.createElement('button');
        toggleBtn.id = 'mobile-menu-toggle';
        toggleBtn.className = 'mobile-menu-toggle';
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        toggleBtn.setAttribute('aria-label', 'Toggle navigation menu');
        toggleBtn.setAttribute('type', 'button');

        // Insert at the beginning of top navbar
        const topNavbar = document.querySelector('.top-navbar');
        if (topNavbar) {
            topNavbar.insertBefore(toggleBtn, topNavbar.firstChild);
        }

        // Create overlay
        const overlay = document.createElement('div');
        overlay.id = 'mobile-menu-overlay';
        overlay.className = 'mobile-menu-overlay';
        overlay.style.cssText = `
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        `;
        document.body.appendChild(overlay);

        this.toggleBtn = toggleBtn;
        this.overlay = overlay;
        this.attachEventListeners();
    }

    removeMobileUI() {
        if (this.toggleBtn) {
            this.toggleBtn.remove();
            this.toggleBtn = null;
        }

        if (this.overlay) {
            this.overlay.remove();
            this.overlay = null;
        }

        // Close sidebar if open
        if (this.sidebar) {
            this.sidebar.classList.remove('mobile-open');
        }

        this.isOpen = false;
    }

    attachEventListeners() {
        if (!this.isMobile) return;

        // Toggle button click
        if (this.toggleBtn) {
            this.toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleMenu();
            });
        }

        // Overlay click to close
        if (this.overlay) {
            this.overlay.addEventListener('click', () => {
                this.closeMenu();
            });
        }

        // Close menu on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeMenu();
            }
        });

        // Close menu on navigation
        this.handleNavigation();
    }

    handleNavigation() {
        if (!this.isMobile) return;

        // Close menu when clicking nav links
        const navLinks = this.sidebar?.querySelectorAll('.nav-link');
        if (navLinks) {
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    this.closeMenu();
                });
            });
        }
    }

    toggleMenu() {
        if (this.isOpen) {
            this.closeMenu();
        } else {
            this.openMenu();
        }
    }

    openMenu() {
        if (!this.isMobile) return;

        if (this.sidebar) {
            this.sidebar.classList.add('mobile-open');
        }

        if (this.overlay) {
            this.overlay.style.display = 'block';
        }

        this.isOpen = true;
        document.body.style.overflow = 'hidden';

        // Update button icon
        if (this.toggleBtn) {
            this.toggleBtn.innerHTML = '<i class="fas fa-times"></i>';
        }
    }

    closeMenu() {
        if (this.sidebar) {
            this.sidebar.classList.remove('mobile-open');
        }

        if (this.overlay) {
            this.overlay.style.display = 'none';
        }

        this.isOpen = false;
        document.body.style.overflow = 'auto';

        // Update button icon
        if (this.toggleBtn) {
            this.toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        }
    }

    // Auto-close menu on body click (except on menu itself)
    setupBodyClickHandler() {
        document.addEventListener('click', (e) => {
            if (!this.isMobile || !this.isOpen) return;

            const isClickInsideSidebar = this.sidebar?.contains(e.target);
            const isClickOnToggle = this.toggleBtn?.contains(e.target);

            if (!isClickInsideSidebar && !isClickOnToggle) {
                this.closeMenu();
            }
        });
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.mobileMenu = new MobileMenu();
    });
} else {
    window.mobileMenu = new MobileMenu();
}
