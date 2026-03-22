/**
 * Staff Dashboard JavaScript
 * Handles sidebar toggle, navigation, and administrative features
 */

class StaffDashboard {
    constructor() {
        this.sidebar = document.querySelector('.staff-sidebar');
        this.mainContent = document.querySelector('.staff-main-content');
        this.sidebarToggle = document.querySelector('#staff-sidebar-toggle');
        this.sidebarLinks = document.querySelectorAll('.staff-sidebar-link');
        this.isSidebarHidden = false;
        this.isMobile = window.innerWidth < 768;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.handleResize();
        this.addAnimations();
        
        
    }
    
    bindEvents() {
        // Sidebar toggle functionality
        if (this.sidebarToggle) {
            this.sidebarToggle.addEventListener('click', () => this.toggleSidebar());
        }
        
        // Sidebar navigation
        this.sidebarLinks.forEach(link => {
            link.addEventListener('click', (e) => this.handleNavClick(e, link));
        });
        
        // Handle window resize
        window.addEventListener('resize', () => this.handleResize());
        
        // Handle escape key to close sidebar on mobile
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isMobile && !this.isSidebarHidden) {
                this.toggleSidebar();
            }
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (this.isMobile && this.sidebar && !this.sidebar.contains(e.target) && 
                this.sidebarToggle && !this.sidebarToggle.contains(e.target)) {
                if (!this.isSidebarHidden) {
                    this.toggleSidebar();
                }
            }
        });
    }
    
    toggleSidebar() {
        if (this.isMobile) {
            // Mobile toggle
            this.sidebar.classList.toggle('mobile-open');
            this.isSidebarHidden = !this.sidebar.classList.contains('mobile-open');
        } else {
            // Desktop toggle
            this.sidebar.classList.toggle('hidden');
            this.mainContent.classList.toggle('expanded');
            this.isSidebarHidden = this.sidebar.classList.contains('hidden');
        }
        
        // Update toggle button icon
        this.updateToggleIcon();
        
        // Save state to localStorage
        localStorage.setItem('staffSidebarHidden', this.isSidebarHidden);
    }
    
    updateToggleIcon() {
        if (this.sidebarToggle) {
            const icon = this.sidebarToggle.querySelector('i');
            if (icon) {
                if (this.isSidebarHidden) {
                    icon.className = 'fas fa-bars';
                } else {
                    icon.className = 'fas fa-times';
                }
            }
        }
    }
    
    handleNavClick(e, link) {
        // Don't prevent default for logout link
        if (link.href.includes('logout')) {
            return;
        }
        
        e.preventDefault();
        
        // Remove active class from all links
        this.sidebarLinks.forEach(l => l.classList.remove('active'));
        
        // Add active class to clicked link
        link.classList.add('active');
        
        // Get the page name from the link text
        const span = link.querySelector('span');
        const pageName = span ? span.textContent.trim() : 'Dashboard';
        
        // Handle navigation
        this.handlePageNavigation(pageName);
        
        // On mobile, close sidebar after navigation
        if (this.isMobile) {
            setTimeout(() => this.toggleSidebar(), 300);
        }
    }
    
    handlePageNavigation(pageName) {
        // Update page title
        const pageTitle = document.querySelector('h1');
        if (pageTitle) {
            switch(pageName) {
                case 'Dashboard':
                    pageTitle.textContent = 'Staff Dashboard';
                    break;
                case 'Manage Books':
                    pageTitle.textContent = 'Manage Books';
                    break;
                case 'User Management':
                    pageTitle.textContent = 'User Management';
                    break;
                case 'Reports':
                    pageTitle.textContent = 'Reports & Analytics';
                    break;
                case 'Settings':
                    pageTitle.textContent = 'System Settings';
                    break;
                default:
                    pageTitle.textContent = pageName;
            }
        }
        
        
    }
    
    handleResize() {
        const newIsMobile = window.innerWidth < 768;
        
        if (newIsMobile !== this.isMobile) {
            this.isMobile = newIsMobile;
            
            if (this.isMobile) {
                // Switching to mobile
                this.sidebar.classList.remove('hidden');
                this.mainContent.classList.remove('expanded');
            } else {
                // Switching to desktop
                this.sidebar.classList.remove('mobile-open');
                
                // Restore desktop state from localStorage
                const savedState = localStorage.getItem('staffSidebarHidden') === 'true';
                if (savedState) {
                    this.sidebar.classList.add('hidden');
                    this.mainContent.classList.add('expanded');
                    this.isSidebarHidden = true;
                } else {
                    this.isSidebarHidden = false;
                }
            }
            
            this.updateToggleIcon();
        }
    }
    
    addAnimations() {
        // Add fade-in animations to cards
        const cards = document.querySelectorAll('.staff-stat-card');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }
    
    // Staff-specific utility methods
    showAdminNotification(message, type = 'info') {
        const notification = document.createElement('div');
        const classColor = type === 'success' ? 'bg-white text-blue-600 border-blue-200' : 
                          type === 'error' ? 'bg-red-500 text-white border-red-500' : 
                          type === 'warning' ? 'bg-yellow-500 text-white border-yellow-500' : 'bg-purple-500 text-white border-purple-500';
        
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg border z-50 ${classColor}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 4000);
    }
}

// Initialize the staff dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new StaffDashboard();
});

// Export for potential module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = StaffDashboard;
}
