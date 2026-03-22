// Books Page Sidebar Functionality
class BooksPageSidebar {
    constructor() {
        this.sidebar = document.querySelector('.sidebar');
        this.mainContent = document.querySelector('.main-content');
        this.header = document.querySelector('.header');
        this.sidebarToggle = document.getElementById('sidebar-toggle');
        this.backdrop = document.getElementById('sidebar-backdrop');
        this.isSidebarHidden = false;
        this.isMobile = window.innerWidth < 768;
        
        // Only initialize if essential elements exist
        if (this.sidebar && this.mainContent && this.header && this.sidebarToggle) {
            this.init();
        }
    }
    
    init() {
        this.bindEvents();
        this.initializeLayout();
    }
    
    bindEvents() {
        // Sidebar toggle
        if (this.sidebarToggle) {
            this.sidebarToggle.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleSidebar();
            });
        }
        
        // Close sidebar when clicking backdrop (mobile only)
        if (this.backdrop) {
            this.backdrop.addEventListener('click', () => {
                if (this.isMobile && this.sidebar.classList.contains('mobile-open')) {
                    this.toggleSidebar();
                }
            });
        }
        
        // Handle window resize
        window.addEventListener('resize', () => {
            this.handleResize();
        });
        
        // Close sidebar on escape key (mobile)
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isMobile && this.sidebar.classList.contains('mobile-open')) {
                this.toggleSidebar();
            }
        });
    }
    
    initializeLayout() {
        if (this.isMobile) {
            // Mobile: Start with sidebar hidden
            this.sidebar.classList.remove('mobile-open');
            this.backdrop.classList.add('hidden');
            this.header.classList.remove('sidebar-collapsed');
            this.header.classList.add('sidebar-expanded');
        } else {
            // Desktop: Start with sidebar visible
            this.sidebar.classList.remove('hidden');
            this.backdrop.classList.add('hidden');
            this.header.classList.remove('sidebar-collapsed');
            this.header.classList.add('sidebar-expanded');
        }
        this.isSidebarHidden = this.isMobile;
    }
    
    toggleSidebar() {
        if (this.isMobile) {
            // Mobile: Slide in/out
            this.sidebar.classList.toggle('mobile-open');
            this.backdrop.classList.toggle('hidden');
            this.isSidebarHidden = !this.sidebar.classList.contains('mobile-open');
        } else {
            // Desktop: Hide/show completely
            const isHidden = this.sidebar.classList.contains('hidden');
            
            if (isHidden) {
                // Show sidebar
                this.sidebar.classList.remove('hidden');
                this.header.classList.remove('sidebar-collapsed');
                this.header.classList.add('sidebar-expanded');
            } else {
                // Hide sidebar
                this.sidebar.classList.add('hidden');
                this.header.classList.remove('sidebar-expanded');
                this.header.classList.add('sidebar-collapsed');
            }
            
            this.isSidebarHidden = this.sidebar.classList.contains('hidden');
        }
    }
    
    handleResize() {
        const newIsMobile = window.innerWidth < 768;
        if (newIsMobile !== this.isMobile) {
            this.isMobile = newIsMobile;
            this.initializeLayout();
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize on books page and if student dashboard is not already handling the sidebar
    if (document.getElementById('books-grid') && !window.__studentDashboard) {
        window.__booksPageSidebar = new BooksPageSidebar();
    }
});
