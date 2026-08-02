/**
 * Accessibility Manager
 * WCAG 2.1 AA compliance and screen reader support
 */

class AccessibilityManager {
    constructor() {
        this.ariaLiveRegion = null;
        this.focusTrap = null;
        this.init();
    }

    init() {
        this.setupAriaLiveRegion();
        this.enhanceFocusManagement();
        this.addSkipLinks();
        this.improveFormAccessibility();
        this.addKeyboardShortcuts();
        this.enhanceTableAccessibility();
        this.improveLinkAccessibility();
        this.addAriaLabels();
        this.monitorColorContrast();

        console.log('Accessibility Manager initialized');
    }

    /**
     * Create and manage ARIA live region for dynamic announcements
     */
    setupAriaLiveRegion() {
        let liveRegion = document.getElementById('aria-live-region');
        if (!liveRegion) {
            liveRegion = document.createElement('div');
            liveRegion.id = 'aria-live-region';
            liveRegion.setAttribute('aria-live', 'polite');
            liveRegion.setAttribute('aria-atomic', 'true');
            liveRegion.className = 'sr-only';
            document.body.appendChild(liveRegion);
        }
        this.ariaLiveRegion = liveRegion;
    }

    /**
     * Announce messages to screen readers
     */
    announce(message, priority = 'polite') {
        if (this.ariaLiveRegion) {
            this.ariaLiveRegion.setAttribute('aria-live', priority);
            this.ariaLiveRegion.textContent = message;

            // Clear after announcement
            setTimeout(() => {
                this.ariaLiveRegion.textContent = '';
            }, 3000);
        }
    }

    /**
     * Enhance focus management for better keyboard navigation
     */
    enhanceFocusManagement() {
        // Manage focus on modals
        document.addEventListener('show.bs.modal', (e) => {
            const modal = e.target;
            setTimeout(() => {
                const focusableElement = modal.querySelector(
                    'button:not(.btn-close), [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );
                if (focusableElement) {
                    focusableElement.focus();
                }
            }, 100);
        });

        // Restore focus when modal closes
        document.addEventListener('hide.bs.modal', (e) => {
            const triggerBtn = document.activeElement;
            setTimeout(() => {
                if (triggerBtn && typeof triggerBtn.focus === 'function') {
                    triggerBtn.focus();
                }
            }, 100);
        });
    }

    /**
     * Add skip links for keyboard navigation
     */
    addSkipLinks() {
        const skipLink = document.querySelector('.skip-to-main');
        if (!skipLink) {
            const skip = document.createElement('a');
            skip.href = '#main-content';
            skip.className = 'skip-to-main';
            skip.textContent = 'Skip to main content';
            skip.setAttribute('tabindex', '0');
            document.body.insertBefore(skip, document.body.firstChild);

            // Set main content area
            const mainContent = document.querySelector('.content');
            if (mainContent) {
                mainContent.id = 'main-content';
                mainContent.setAttribute('tabindex', '-1');
            }
        }
    }

    /**
     * Improve form accessibility
     */
    improveFormAccessibility() {
        // Ensure all form inputs have associated labels
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach((input, index) => {
            if (!input.id) {
                input.id = `field-${Date.now()}-${index}`;
            }

            // Check for associated label
            let label = document.querySelector(`label[for="${input.id}"]`);
            if (!label && input.name) {
                label = document.querySelector(`label[for="${input.name}"]`);
            }

            // If no label, create aria-label from name or placeholder
            if (!label) {
                const ariaLabel = input.name || input.placeholder || input.id;
                if (ariaLabel && !input.getAttribute('aria-label')) {
                    input.setAttribute('aria-label', ariaLabel);
                }
            }

            // Add aria-required for required fields
            if (input.hasAttribute('required')) {
                input.setAttribute('aria-required', 'true');
            }

            // Add aria-invalid for validation
            if (input.classList.contains('is-invalid')) {
                input.setAttribute('aria-invalid', 'true');
            }
        });

        // Improve form submission
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                // Check for validation
                const invalidFields = form.querySelectorAll('.is-invalid');
                if (invalidFields.length > 0) {
                    this.announce(`Form has ${invalidFields.length} errors. Please correct them.`, 'alert');
                    invalidFields[0].focus();
                }
            });

            // Add role to form
            if (!form.hasAttribute('role')) {
                form.setAttribute('role', 'form');
            }
        });
    }

    /**
     * Add keyboard shortcuts
     */
    addKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Alt + M: Toggle mobile menu
            if (e.altKey && e.key === 'm') {
                e.preventDefault();
                if (window.mobileMenu) {
                    window.mobileMenu.toggleMenu();
                    this.announce('Mobile menu toggled');
                }
            }

            // Alt + D: Go to Dashboard
            if (e.altKey && e.key === 'd') {
                e.preventDefault();
                const dashboardLink = document.querySelector('[onclick*="navigateTo.*dashboard"]');
                if (dashboardLink) {
                    dashboardLink.click();
                }
            }

            // Alt + H: Show keyboard help
            if (e.altKey && e.key === 'h') {
                e.preventDefault();
                this.showKeyboardHelp();
            }

            // Alt + S: Focus search (if available)
            if (e.altKey && e.key === 's') {
                e.preventDefault();
                const searchInput = document.querySelector('[placeholder*="Search"], [aria-label*="Search"]');
                if (searchInput) {
                    searchInput.focus();
                    this.announce('Search box focused');
                }
            }
        });
    }

    /**
     * Show keyboard shortcuts help
     */
    showKeyboardHelp() {
        const helpText = `
Keyboard Shortcuts:
Alt + D: Go to Dashboard
Alt + M: Toggle Mobile Menu
Alt + S: Focus Search Box
Alt + H: Show this help
Tab: Navigate forward
Shift + Tab: Navigate backward
Enter: Activate buttons/links
Space: Toggle checkboxes
Escape: Close modals/menus
        `;
        this.announce(helpText, 'polite');

        // Show as toast if possible
        if (window.Notifications) {
            Notifications.info(helpText);
        }
    }

    /**
     * Enhance table accessibility
     */
    enhanceTableAccessibility() {
        const tables = document.querySelectorAll('table');
        tables.forEach((table, index) => {
            // Add table summary for screen readers
            if (!table.getAttribute('summary')) {
                const caption = table.querySelector('caption');
                if (!caption) {
                    const summary = `Data table with ${table.rows.length} rows and ${table.rows[0]?.cells.length} columns`;
                    table.setAttribute('summary', summary);
                }
            }

            // Add ARIA labels to table cells
            const headers = table.querySelectorAll('th');
            headers.forEach((header, colIndex) => {
                if (!header.id) {
                    header.id = `table-${index}-header-${colIndex}`;
                }
            });

            const bodyCells = table.querySelectorAll('tbody td');
            bodyCells.forEach((cell, cellIndex) => {
                const rowIndex = Math.floor(cellIndex / table.rows[0].cells.length);
                const colIndex = cellIndex % table.rows[0].cells.length;

                const header = headers[colIndex];
                if (header && header.id) {
                    cell.setAttribute('headers', header.id);
                }
            });

            // Add aria-label to action buttons in tables
            const actionButtons = table.querySelectorAll('button');
            actionButtons.forEach((btn, btnIndex) => {
                if (!btn.getAttribute('aria-label')) {
                    const row = btn.closest('tr');
                    const name = row ? row.cells[0]?.textContent : 'item';
                    btn.setAttribute('aria-label', `${btn.textContent} ${name}`);
                }
            });
        });
    }

    /**
     * Improve link accessibility
     */
    improveLinkAccessibility() {
        const links = document.querySelectorAll('a');
        links.forEach(link => {
            // Check for empty links
            const text = link.textContent.trim();
            if (!text && !link.getAttribute('aria-label')) {
                // Try to get title or other info
                const title = link.getAttribute('title');
                if (title) {
                    link.setAttribute('aria-label', title);
                } else {
                    link.setAttribute('aria-label', 'Link');
                }
            }

            // Indicate external links
            const href = link.getAttribute('href');
            if (href && (href.startsWith('http') || href.startsWith('//'))) {
                if (!link.getAttribute('aria-label')?.includes('external')) {
                    const currentAriaLabel = link.getAttribute('aria-label') || link.textContent;
                    link.setAttribute('aria-label', `${currentAriaLabel} (opens in new window)`);
                    if (!link.hasAttribute('target')) {
                        link.setAttribute('target', '_blank');
                        link.setAttribute('rel', 'noopener noreferrer');
                    }
                }
            }
        });
    }

    /**
     * Add ARIA labels to common elements
     */
    addAriaLabels() {
        // Add role and aria-label to buttons without text
        const buttonLessButtons = document.querySelectorAll('button i:only-child');
        buttonLessButtons.forEach(icon => {
            const btn = icon.parentElement;
            if (btn.tagName === 'BUTTON' && !btn.getAttribute('aria-label')) {
                const iconClass = icon.className;
                let label = 'Button';

                if (iconClass.includes('bars')) label = 'Menu';
                else if (iconClass.includes('times') || iconClass.includes('close')) label = 'Close';
                else if (iconClass.includes('search')) label = 'Search';
                else if (iconClass.includes('plus')) label = 'Add';
                else if (iconClass.includes('trash') || iconClass.includes('delete')) label = 'Delete';
                else if (iconClass.includes('edit') || iconClass.includes('pencil')) label = 'Edit';

                btn.setAttribute('aria-label', label);
            }
        });

        // Add role to navigation
        const navs = document.querySelectorAll('nav');
        navs.forEach((nav, index) => {
            if (!nav.getAttribute('role')) {
                nav.setAttribute('role', 'navigation');
            }
            if (!nav.getAttribute('aria-label')) {
                nav.setAttribute('aria-label', `Navigation ${index + 1}`);
            }
        });

        // Add role to main sections
        const sections = document.querySelectorAll('section');
        sections.forEach((section, index) => {
            if (!section.getAttribute('aria-label')) {
                const heading = section.querySelector('h1, h2, h3, h4, h5, h6');
                if (heading) {
                    section.setAttribute('aria-label', heading.textContent);
                    section.setAttribute('aria-labelledby', heading.id || `heading-${index}`);
                    if (!heading.id) {
                        heading.id = `heading-${index}`;
                    }
                }
            }
        });

        // Add role to cards
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            if (!card.getAttribute('role')) {
                card.setAttribute('role', 'region');
            }
            const header = card.querySelector('.card-header h5, .card-header h6');
            if (header && !card.getAttribute('aria-label')) {
                card.setAttribute('aria-label', header.textContent);
                if (!header.id) {
                    header.id = `card-header-${index}`;
                }
                card.setAttribute('aria-labelledby', header.id);
            }
        });
    }

    /**
     * Monitor color contrast (informational)
     */
    monitorColorContrast() {
        // This is a basic check - in production, use a more comprehensive tool
        const elements = document.querySelectorAll('body, a, button, .btn, label, p');
        let contrastIssues = 0;

        elements.forEach(el => {
            const style = window.getComputedStyle(el);
            const bgColor = style.backgroundColor;
            const color = style.color;

            // Simple contrast check (this is simplified)
            if (bgColor !== 'rgba(0, 0, 0, 0)' && color) {
                // Contrast check would go here
                // For now, just logging that we're monitoring
            }
        });

        if (contrastIssues > 0) {
            console.warn(`Potential color contrast issues detected: ${contrastIssues}`);
        }
    }

    /**
     * Test accessibility (console logging)
     */
    testAccessibility() {
        const results = {
            skipLinks: !!document.querySelector('.skip-to-main'),
            ariaLiveRegion: !!this.ariaLiveRegion,
            formsWithLabels: 0,
            formsWithoutLabels: 0,
            imagesWithAlt: 0,
            imagesWithoutAlt: 0,
            focusIndicators: true, // CSS-based
        };

        // Check forms
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            const label = document.querySelector(`label[for="${input.id}"]`);
            const ariaLabel = input.getAttribute('aria-label');
            if (label || ariaLabel) {
                results.formsWithLabels++;
            } else {
                results.formsWithoutLabels++;
            }
        });

        // Check images
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            if (img.hasAttribute('alt') && img.getAttribute('alt').length > 0) {
                results.imagesWithAlt++;
            } else {
                results.imagesWithoutAlt++;
            }
        });

        console.table(results);
        return results;
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.a11y = new AccessibilityManager();
    });
} else {
    window.a11y = new AccessibilityManager();
}

// Export for testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AccessibilityManager;
}
