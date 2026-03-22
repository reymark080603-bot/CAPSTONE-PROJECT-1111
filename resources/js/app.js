import './bootstrap';

// Initialize BooksManager when the page is loaded
if (document.getElementById('books-container')) {
    // BooksManager will be initialized by the individual page scripts
}

// Sidebar Toggle Functionality
function initSidebar() {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarBackdrop = document.getElementById('sidebar-backdrop');
    const mainContent = document.querySelector('.main-content');
    const header = document.querySelector('.header');

    if (!sidebarToggle || !sidebar || !sidebarBackdrop) return;

    const toggleSidebar = () => {
        const isMobile = window.innerWidth < 768;

        if (isMobile) {
            sidebar.classList.toggle('active');
            sidebarBackdrop.classList.toggle('active');
        } else {
            const isHidden = sidebar.classList.toggle('hidden');
            sidebarBackdrop.classList.remove('active');

            if (header && mainContent) {
                if (isHidden) {
                    header.classList.remove('sidebar-expanded');
                    header.classList.add('sidebar-collapsed');
                    mainContent.classList.add('expanded');
                } else {
                    header.classList.add('sidebar-expanded');
                    header.classList.remove('sidebar-collapsed');
                    mainContent.classList.remove('expanded');
                }
            }
        }

        const isSidebarVisible = !sidebar.classList.contains('hidden') || sidebar.classList.contains('active');
        document.body.classList.toggle('sidebar-expanded', isSidebarVisible);
    };

    // Toggle sidebar when button is clicked
    sidebarToggle.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        toggleSidebar();
    });

    // Close sidebar when clicking on backdrop (mobile view)
    sidebarBackdrop.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        toggleSidebar();
    });

    // Close sidebar when clicking outside (mobile view)
    document.addEventListener('click', (e) => {
        const isClickInsideSidebar = sidebar.contains(e.target) || e.target === sidebarToggle;
        const isMobile = window.innerWidth < 768;
        if (!isClickInsideSidebar && isMobile && sidebar.classList.contains('active')) {
            toggleSidebar();
        }
    });

    const handleResize = () => {
        const isMobile = window.innerWidth < 768;

        if (isMobile) {
            sidebar.classList.remove('hidden');
            sidebar.classList.remove('active');
            sidebarBackdrop.classList.remove('active');

            if (header) {
                header.classList.remove('sidebar-collapsed');
                header.classList.add('sidebar-expanded');
            }

            if (mainContent) {
                mainContent.classList.remove('expanded');
            }
        } else {
            sidebarBackdrop.classList.remove('active');

            const isHidden = sidebar.classList.contains('hidden');
            if (header && mainContent) {
                if (isHidden) {
                    header.classList.remove('sidebar-expanded');
                    header.classList.add('sidebar-collapsed');
                    mainContent.classList.add('expanded');
                } else {
                    header.classList.add('sidebar-expanded');
                    header.classList.remove('sidebar-collapsed');
                    mainContent.classList.remove('expanded');
                }
            }
        }
    };

    // Initial check
    handleResize();
    
    // Add resize listener
    window.addEventListener('resize', handleResize);
}

// Initialize when DOM is loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebar);
} else {
    initSidebar();
}
