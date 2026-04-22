/**
 * Librarian Dashboard JavaScript
 * Handles charts, statistics, notifications, and interactive features
 */

class LibrarianDashboard {
    constructor() {
        this.sidebar = document.querySelector('.sidebar');
        this.mainContent = document.querySelector('.main-content');
        this.container = document.querySelector('.dashboard-container');
        this.sidebarToggle = document.querySelector('#sidebar-toggle');
        this.backdrop = document.querySelector('#sidebar-backdrop');
        this.sidebarLinks = document.querySelectorAll('.sidebar-link');
        this.isSidebarHidden = false;
        this.isMobile = window.innerWidth < 768;
        
        // Chart instances
        this.charts = {};
        
        // Dashboard data
        this.dashboardData = {};
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.handleResize();
        this.loadDashboardData();
        this.setupNotifications();
        this.setupDropdowns();
        this.initializeLayout();
        // Initial notifications load and polling refresh
        this.loadNotifications();
        this.notificationsInterval = setInterval(() => {
            try {
                this.loadNotifications();
            } catch (error) {
                
                // Stop polling on authentication errors to avoid continuous failed requests
                if (error.message === 'Authentication required') {
                    clearInterval(this.notificationsInterval);
                }
            }
        }, 60000);
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            this.stopPolling();
        });
        
        
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
        
        // Backdrop click handler
        if (this.backdrop) {
            this.backdrop.addEventListener('click', () => {
                if (this.isMobile && !this.isSidebarHidden) {
                    this.toggleSidebar();
                }
            });
        }
        
        // Refresh activities button
        const refreshActivities = document.getElementById('refresh-activities');
        if (refreshActivities) {
            refreshActivities.addEventListener('click', () => this.loadRecentActivities());
        }
        
        // Chart toggle buttons
        const chartToggles = document.querySelectorAll('.chart-toggle');
        chartToggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => this.handleChartToggle(e, toggle));
        });
        
        // Notification button
        const notificationsBtn = document.getElementById('notifications-btn');
        if (notificationsBtn) {
            notificationsBtn.addEventListener('click', () => this.toggleNotificationsDropdown());
        }
        
        // Export button
        const exportBtn = document.getElementById('export-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.toggleExportDropdown());
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#notifications-btn') && !e.target.closest('#notifications-dropdown')) {
                this.closeNotificationsDropdown();
            }
            if (!e.target.closest('#export-btn') && !e.target.closest('#export-dropdown')) {
                this.closeExportDropdown();
            }
        });
        
        // Notification action buttons
        const markAllBtn = document.getElementById('notifications-mark-all');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.markAllNotificationsAsRead();
            });
        }
        
        const clearAllBtn = document.getElementById('notifications-clear-all');
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.clearAllNotifications();
            });
        }
    }
    
    toggleSidebar() {
        if (this.isMobile) {
            // Mobile toggle
            this.sidebar.classList.toggle('mobile-open');
            this.isSidebarHidden = !this.sidebar.classList.contains('mobile-open');
            
            // Toggle backdrop
            if (this.backdrop) {
                if (this.isSidebarHidden) {
                    this.backdrop.classList.remove('show');
                } else {
                    this.backdrop.classList.add('show');
                }
            }
        } else {
            // Desktop toggle: match Manage Books behavior
            if (this.container) {
                this.container.classList.toggle('sidebar-closed');
                this.isSidebarHidden = this.container.classList.contains('sidebar-closed');
            } else {
                // Fallback to old method if container not found
                this.sidebar.classList.toggle('hidden');
                this.isSidebarHidden = this.sidebar.classList.contains('hidden');
            }
        }
        
        // Update toggle button icon
        this.updateToggleIcon();
        
        // Save state to localStorage
        localStorage.setItem('librarianSidebarHidden', this.isSidebarHidden);
        
        // Resize charts after sidebar toggle
        setTimeout(() => this.resizeCharts(), 300);
    }
    
    updateToggleIcon() {
        const icon = this.sidebarToggle.querySelector('i');
        if (this.isSidebarHidden) {
            icon.className = 'fas fa-bars';
        } else {
            icon.className = 'fas fa-times';
        }
    }
    
    initializeLayout() {
        // Initialize layout based on saved state and screen size
        if (!this.isMobile) {
            const savedState = localStorage.getItem('librarianSidebarHidden') === 'true';
            if (this.container) {
                if (savedState) {
                    this.container.classList.add('sidebar-closed');
                    this.isSidebarHidden = true;
                } else {
                    this.container.classList.remove('sidebar-closed');
                    this.isSidebarHidden = false;
                }
                // Ensure sidebar visibility is synced (optional)
                this.sidebar && this.sidebar.classList.toggle('hidden', this.isSidebarHidden);
            } else {
                // Fallback
                this.sidebar.classList.toggle('hidden', savedState);
                this.isSidebarHidden = savedState;
            }
        } else {
            // Mobile: ensure sidebar is hidden initially
            this.sidebar.classList.remove('mobile-open');
            this.isSidebarHidden = true;
        }
        this.updateToggleIcon();
    }
    
    handleNavClick(e, link) {
        // Don't prevent default for logout link or any link with a real href
        if (link.href.includes('logout') || 
            (link.href && !link.href.includes('#') && link.href !== window.location.href)) {
            return;
        }
        
        // Only prevent default for placeholder links (#)
        if (link.href.includes('#')) {
            e.preventDefault();
        }
        
        // Remove active class from all links
        this.sidebarLinks.forEach(l => l.classList.remove('active'));
        
        // Add active class to clicked link
        link.classList.add('active');
        
        // On mobile, close sidebar after navigation
        if (this.isMobile) {
            setTimeout(() => this.toggleSidebar(), 300);
        }
    }
    
    handleResize() {
        const newIsMobile = window.innerWidth < 768;
        
        if (newIsMobile !== this.isMobile) {
            this.isMobile = newIsMobile;
            this.initializeLayout();
            this.resizeCharts();
        }
    }
    
    async loadDashboardData() {
        try {
            const response = await this.makeAuthenticatedRequest('/librarian/dashboard/stats');
            
            if (!response.ok) {
                throw new Error('Failed to load dashboard data');
            }
            
            this.dashboardData = await response.json();
            
            this.updateStatCards();
            this.initializeCharts();
            this.loadRecentActivities();
            this.loadMostBorrowedBooks();
            this.updateTodaysSummary();
            
        } catch (error) {
            
            this.showNotification('Failed to load dashboard data', 'error');
        }
    }
    
    updateStatCards() {
        const stats = this.dashboardData.basic_stats;
        if (!stats) return;
        
        // Animate counter updates
        this.animateCounter('total-books-count', stats.total_books || 0);
        this.animateCounter('total-students-count', stats.total_students || 0);
        this.animateCounter('active-borrows-count', stats.active_borrows || 0);
        this.animateCounter('total-loans-count', stats.total_loans || 0);
    }
    
    animateCounter(elementId, targetValue) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        const currentValue = parseInt(element.textContent) || 0;
        const increment = Math.ceil((targetValue - currentValue) / 30);
        
        if (increment === 0) {
            element.textContent = targetValue;
            return;
        }
        
        let current = currentValue;
        const timer = setInterval(() => {
            current += increment;
            
            if ((increment > 0 && current >= targetValue) || (increment < 0 && current <= targetValue)) {
                element.textContent = targetValue;
                clearInterval(timer);
            } else {
                element.textContent = current;
            }
        }, 50);
    }
    
    initializeCharts() {
        this.createBookStatusChart('bar');
        this.createMonthlyTrendsChart();
        this.createStudentsProgramChart();
        this.createGenderChart();
        this.createCampusChart();
        this.createResourceTypesChart();
    }
    
    createBookStatusChart(chartType = 'pie') {
        const ctx = document.getElementById('book-status-chart');
        if (!ctx) return;
        const summary = document.getElementById('book-status-summary');
        const booksByCourse = this.dashboardData.books_by_course || {};
        const entries = Object.entries(booksByCourse)
            .map(([label, value]) => [this.formatProgramLabel(label || 'General'), Number(value) || 0])
            .sort((a, b) => b[1] - a[1]);
        const labels = entries.map(([label]) => label);
        const values = entries.map(([, value]) => value);
        const totalBooks = values.reduce((sum, value) => sum + value, 0);
        const colors = [
            '#f43f5e',
            '#3b82f6',
            '#f59e0b',
            '#14b8a6',
            '#8b5cf6',
            '#f97316',
            '#22c55e',
            '#06b6d4'
        ];
        
        if (this.charts.bookStatus) {
            this.charts.bookStatus.destroy();
        }

        if (summary) {
            summary.textContent = `Total books: ${totalBooks} across ${labels.length} program${labels.length === 1 ? '' : 's'}.`;
        }

        if (labels.length === 0) {
            const context = ctx.getContext('2d');
            context.clearRect(0, 0, ctx.width, ctx.height);
            context.font = '16px system-ui';
            context.fillStyle = '#9ca3af';
            context.textAlign = 'center';
            context.textBaseline = 'middle';
            context.fillText('No course distribution data available', ctx.width / 2, ctx.height / 2);
            return;
        }

        try {
            this.charts.bookStatus = new Chart(ctx, {
                type: chartType,
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: labels.map((_, index) => colors[index % colors.length]),
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        borderRadius: chartType === 'bar' ? 10 : 0,
                        borderSkipped: false,
                        maxBarThickness: 44
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: chartType === 'bar' ? 'y' : 'x',
                    plugins: {
                        legend: {
                            display: chartType !== 'bar',
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = chartType === 'bar' ? context.parsed.x : context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${label}: ${value} books (${percentage}%)`;
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    scales: chartType === 'bar' ? {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: '#6b7280'
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            },
                            title: {
                                display: true,
                                text: 'Number of Books',
                                color: '#374151',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            }
                        },
                        y: {
                            ticks: {
                                color: '#374151',
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            },
                            grid: {
                                display: false,
                                drawBorder: false
                            }
                        }
                    } : {}
                }
            });
        } catch (error) {
            
        }
    }

    createMonthlyTrendsChart() {
        const ctx = document.getElementById('monthly-trends-chart');
        if (!ctx) return;

        const data = this.dashboardData.monthly_trends || [];
        
        // Prepare data for Chart.js
        const labels = [];
        const values = [];
        
        // Generate last 12 months labels
        for (let i = 11; i >= 0; i--) {
            const date = new Date();
            date.setMonth(date.getMonth() - i);
            labels.push(date.toLocaleDateString('en-US', { year: 'numeric', month: 'short' }));
            values.push(0); // Default to 0
        }
        
        // Fill in actual data
        data.forEach(item => {
            const itemDate = new Date(item.date + '-01');
            const monthLabel = itemDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short' });
            const index = labels.indexOf(monthLabel);
            if (index !== -1) {
                values[index] = item.count;
            }
        });
        
        // Check if there's any data
        const hasData = values.some(value => value > 0);
        
        // Destroy existing chart
        if (this.charts.monthlyTrends) {
            this.charts.monthlyTrends.destroy();
        }

        // If no data, show a message
        if (!hasData) {
            ctx.getContext('2d').clearRect(0, 0, ctx.width, ctx.height);
            ctx.getContext('2d').font = '16px system-ui';
            ctx.getContext('2d').fillStyle = '#9ca3af';
            ctx.getContext('2d').textAlign = 'center';
            ctx.getContext('2d').textBaseline = 'middle';
            ctx.getContext('2d').fillText('No borrowing data available for the selected period', ctx.width / 2, ctx.height / 2);
            return;
        }

        this.charts.monthlyTrends = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Books Borrowed',
                    data: values,
                    backgroundColor: '#3b82f6',
                    borderColor: '#2563eb',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                    hoverBackgroundColor: '#2563eb',
                    hoverBorderColor: '#1d4ed8',
                    hoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                size: 14,
                                weight: 'bold'
                            },
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: '#3b82f6',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return `Books Borrowed: ${context.parsed.y}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 12
                            },
                            color: '#6b7280'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 12
                            },
                            color: '#6b7280'
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }

    createStudentsProgramChart() {
        const ctx = document.getElementById('students-program-chart');
        if (!ctx) return;

        const data = this.dashboardData.course_stats || [];
        
        if (!data.length) {
            const container = ctx.parentElement;
            container.innerHTML = '<div class="flex justify-center items-center h-72"><i class="fas fa-chart-bar text-2xl text-gray-400 mr-3"></i><span class="text-gray-600">No student program data available</span></div>';
            return;
        }

        const labels = data.map(item => this.formatProgramLabel(item.program || item.course || 'Unknown'));
        const values = data.map(item => Number(item.student_count) || 0);

        if (this.charts.studentsProgram) {
            this.charts.studentsProgram.destroy();
        }

        this.charts.studentsProgram = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Students',
                    data: values,
                    backgroundColor: [
                        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'
                    ],
                    borderColor: [
                        '#2563eb', '#059669', '#d97706', '#dc2626', '#7c3aed'
                    ],
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: (context) => {
                                return `${context.label}: ${context.raw} students`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                size: 12
                            },
                            stepSize: 1
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }

    createGenderChart() {
        const ctx = document.getElementById('gender-chart');
        if (!ctx) return;

        const data = this.dashboardData.gender_distribution || [];

        if (!data.length) {
            const container = ctx.parentElement;
            container.innerHTML = '<div class="flex justify-center items-center h-72"><i class="fas fa-venus-mars text-2xl text-gray-400 mr-3"></i><span class="text-gray-600">No gender data available</span></div>';
            return;
        }

        const labels = data.map(item => this.formatGenderLabel(item.gender));
        const values = data.map(item => Number(item.count) || 0);
        const colors = ['#3b82f6', '#ec4899', '#94a3b8', '#10b981'];

        if (this.charts.gender) {
            this.charts.gender.destroy();
        }

        this.charts.gender = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: labels.map((_, index) => colors[index % colors.length]),
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 16,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const total = context.dataset.data.reduce((sum, value) => sum + value, 0);
                                const value = context.raw || 0;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${context.label}: ${value} students (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    createCampusChart() {
        const ctx = document.getElementById('campus-chart');
        if (!ctx) return;

        const data = this.dashboardData.campus_distribution || [];

        if (!data.length) {
            const container = ctx.parentElement;
            container.innerHTML = '<div class="flex justify-center items-center h-72"><i class="fas fa-school text-2xl text-gray-400 mr-3"></i><span class="text-gray-600">No campus data available</span></div>';
            return;
        }

        const labels = data.map(item => item.campus || 'Unassigned');
        const values = data.map(item => Number(item.count) || 0);
        const colors = ['#14b8a6', '#3b82f6', '#f59e0b', '#8b5cf6', '#ef4444', '#10b981'];

        if (this.charts.campus) {
            this.charts.campus.destroy();
        }

        this.charts.campus = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Students',
                    data: values,
                    backgroundColor: labels.map((_, index) => colors[index % colors.length]),
                    borderColor: labels.map((_, index) => colors[index % colors.length]),
                    borderWidth: 1,
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 56
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.label}: ${context.raw} students`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    }

    createResourceTypesChart() {
        const ctx = document.getElementById('categories-chart');
        if (!ctx) return;

        const data = this.dashboardData.popular_categories || [];

        if (!data.length) {
            const container = ctx.parentElement;
            container.innerHTML = '<div class="flex justify-center items-center h-72"><i class="fas fa-chart-pie text-2xl text-gray-400 mr-3"></i><span class="text-gray-600">No resource type data available</span></div>';
            return;
        }

        const labels = data.map(item => item.category || 'Unknown');
        const values = data.map(item => Number(item.count) || 0);
        const colors = ['#14b8a6', '#f59e0b', '#3b82f6', '#8b5cf6', '#ef4444'];

        if (this.charts.resourceTypes) {
            this.charts.resourceTypes.destroy();
        }

        this.charts.resourceTypes = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: labels.map((_, index) => colors[index % colors.length]),
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 16,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const total = context.dataset.data.reduce((sum, value) => sum + value, 0);
                                const value = context.raw || 0;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${context.label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    formatProgramLabel(program) {
        if (!program) return 'General';
        const programMap = {
            'BST': 'BSTM',
            'ALL PROGRAM': 'General',
            'ALL PROGRAMS': 'General'
        };
        const normalized = String(program).trim();
        return programMap[normalized.toUpperCase()] || normalized;
    }

    formatGenderLabel(gender) {
        const normalized = String(gender || 'not specified').trim().toLowerCase();
        const genderMap = {
            male: 'Male',
            female: 'Female',
            'not specified': 'Not Specified'
        };

        return genderMap[normalized] || normalized.replace(/\b\w/g, (char) => char.toUpperCase());
    }

    resizeCharts() {
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.resize === 'function') {
                chart.resize();
            }
        });
    }

    printReport() {
        // Get current date and time
        const now = new Date();
        const dateStr = now.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
        });

        // Get dashboard data
        const dashboardData = window.librarianDashboard ? window.librarianDashboard.dashboardData : {};
        const monthTitle = now.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }).toUpperCase();
        const monthlyBorrowed = Number(dashboardData.recent_activity?.borrows || 0);
        const monthlyReturned = Number(dashboardData.recent_activity?.returns || 0);
        const monthlyRegistrations = Number(dashboardData.recent_activity?.registrations || 0);
        const monthlyActivityTotal = monthlyBorrowed + monthlyReturned + monthlyRegistrations;

        const programStats = Array.isArray(dashboardData.course_stats) ? dashboardData.course_stats : [];
        const topProgram = programStats.reduce((top, item) => {
            const count = Number(item.student_count || 0);
            if (!top || count > top.count) {
                return {
                    label: this.formatProgramLabel(item.program || item.course || 'Unassigned'),
                    count
                };
            }
            return top;
        }, null);

        const genderStats = Array.isArray(dashboardData.gender_distribution) ? dashboardData.gender_distribution : [];
        const topGender = genderStats.reduce((top, item) => {
            const count = Number(item.count || 0);
            if (!top || count > top.count) {
                return {
                    label: this.formatGenderLabel(item.gender),
                    count
                };
            }
            return top;
        }, null);

        const totalStudents = Number(dashboardData.basic_stats?.total_students || 0);
        const topGenderPercentage = totalStudents > 0 && topGender
            ? ((topGender.count / totalStudents) * 100).toFixed(1)
            : '0.0';
        const totalBooks = Number(dashboardData.basic_stats?.total_books || 0);
        const activeBorrows = Number(dashboardData.basic_stats?.active_borrows || 0);
        const totalLoans = Number(dashboardData.basic_stats?.total_loans || 0);
        const booksByProgram = dashboardData.books_by_course || {};
        const totalBooksDistributed = Object.values(booksByProgram).reduce((sum, value) => sum + (Number(value) || 0), 0);

        const booksPercentageList = Object.entries(booksByProgram)
            .map(([program, count]) => ({
                label: this.formatProgramLabel(program || 'General'),
                count: Number(count) || 0
            }))
            .filter(item => item.count > 0)
            .sort((a, b) => b.count - a.count)
            .map(item => `<li><strong>${item.label}</strong>: ${item.count} books (${totalBooksDistributed > 0 ? ((item.count / totalBooksDistributed) * 100).toFixed(1) : '0.0'}%)</li>`)
            .join('');

        const studentsPercentageList = programStats
            .map(item => ({
                label: this.formatProgramLabel(item.program || item.course || 'Unassigned'),
                count: Number(item.student_count || 0)
            }))
            .filter(item => item.count > 0)
            .sort((a, b) => b.count - a.count)
            .map(item => `<li><strong>${item.label}</strong>: ${item.count} students (${totalStudents > 0 ? ((item.count / totalStudents) * 100).toFixed(1) : '0.0'}%)</li>`)
            .join('');

        const genderPercentageList = genderStats
            .map(item => ({
                label: this.formatGenderLabel(item.gender),
                count: Number(item.count || 0)
            }))
            .filter(item => item.count > 0)
            .sort((a, b) => b.count - a.count)
            .map(item => `<li><strong>${item.label}</strong>: ${item.count} students (${totalStudents > 0 ? ((item.count / totalStudents) * 100).toFixed(1) : '0.0'}%)</li>`)
            .join('');

        // Capture chart images
        const bookStatusCanvas = document.getElementById('book-status-chart');
        const monthlyTrendsCanvas = document.getElementById('monthly-trends-chart');
        const studentsProgramCanvas = document.getElementById('students-program-chart');
        const genderCanvas = document.getElementById('gender-chart');

        let bookStatusImage = '';
        let monthlyTrendsImage = '';
        let studentsProgramImage = '';
        let genderImage = '';

        if (bookStatusCanvas) {
            bookStatusImage = bookStatusCanvas.toDataURL('image/png');
        }
        if (monthlyTrendsCanvas) {
            monthlyTrendsImage = monthlyTrendsCanvas.toDataURL('image/png');
        }
        if (studentsProgramCanvas) {
            studentsProgramImage = studentsProgramCanvas.toDataURL('image/png');
        }
        if (genderCanvas) {
            genderImage = genderCanvas.toDataURL('image/png');
        }

        // Create a new window for printing
        const printWindow = window.open('', '_blank', 'width=1200,height=800');

        // Create printable HTML content
        const printContent = `
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Library Dashboard Report - ${dateStr}</title>
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
            <style>
                @media print {
                    body { margin: 0; padding: 0; }
                    .no-print { display: none; }
                    .page-break { page-break-before: always; }
                }

                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.5;
                    color: #1f2937;
                    background: #dfead8;
                    margin: 0;
                }

                .report-shell {
                    max-width: 1100px;
                    margin: 0 auto;
                    padding: 18px;
                }

                .header {
                    background: linear-gradient(135deg, #d9ecd0 0%, #c5e0bf 100%);
                    border: 1px solid #a7c6a1;
                    border-radius: 18px;
                    padding: 22px 24px 18px;
                    margin-bottom: 16px;
                    text-align: center;
                    box-shadow: 0 8px 18px rgba(46, 91, 59, 0.08);
                }

                .header .eyebrow {
                    color: #21584a;
                    font-size: 13px;
                    font-weight: 700;
                    letter-spacing: 0.12em;
                    text-transform: uppercase;
                    margin-bottom: 4px;
                }

                .header h1 {
                    color: #123f38;
                    font-size: 32px;
                    margin: 0;
                    font-weight: 800;
                    letter-spacing: 0.04em;
                }

                .header h2 {
                    color: #1d6f5f;
                    font-size: 18px;
                    margin: 4px 0 10px;
                    font-weight: 800;
                    text-transform: uppercase;
                }

                .header p {
                    color: #365b50;
                    margin: 0;
                    font-size: 13px;
                }

                .title-banner {
                    background: #edf6e8;
                    border: 1px solid #bfd6b9;
                    border-radius: 16px;
                    padding: 14px 18px;
                    margin-bottom: 16px;
                }

                .title-banner h3 {
                    margin: 0;
                    color: #0f5a50;
                    font-size: 18px;
                    font-weight: 800;
                    letter-spacing: 0.03em;
                    text-transform: uppercase;
                }

                .title-banner p {
                    margin: 8px 0 0;
                    color: #3f5f57;
                    font-size: 13px;
                }

                .summary-section,
                .chart-container,
                .insight-card {
                    background: #f8fcf6;
                    border: 1px solid #bfd6b9;
                    border-radius: 16px;
                    box-shadow: 0 6px 14px rgba(46, 91, 59, 0.06);
                }

                .summary-section {
                    padding: 18px;
                    margin-bottom: 16px;
                }

                .section-title {
                    margin: 0 0 14px;
                    color: #185a50;
                    font-size: 16px;
                    font-weight: 800;
                    text-transform: uppercase;
                    letter-spacing: 0.02em;
                }

                .summary-grid {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 12px;
                }

                .summary-item {
                    background: linear-gradient(180deg, #ffffff 0%, #eef8ea 100%);
                    border: 1px solid #d4e4ce;
                    border-radius: 14px;
                    padding: 16px 14px;
                    text-align: center;
                }

                .summary-item .value {
                    font-size: 30px;
                    font-weight: 800;
                    margin: 0;
                }

                .summary-item.blue .value { color: #0f766e; }
                .summary-item.green .value { color: #2f855a; }
                .summary-item.orange .value { color: #d97706; }

                .summary-item .label {
                    font-size: 12px;
                    color: #4b635b;
                    margin-top: 6px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.03em;
                }

                .overview-grid,
                .charts-section {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 16px;
                    margin-bottom: 16px;
                }

                .insight-card,
                .chart-container {
                    padding: 16px;
                    break-inside: avoid;
                }

                .insight-card p {
                    margin: 0;
                    color: #425c53;
                    font-size: 13px;
                }

                .insight-card strong {
                    color: #0f5a50;
                }

                .chart-container h3 {
                    font-size: 16px;
                    color: #0f5a50;
                    margin: 0 0 12px 0;
                    font-weight: 800;
                    text-align: left;
                    text-transform: uppercase;
                    letter-spacing: 0.02em;
                }

                .chart-image {
                    width: 100%;
                    height: auto;
                    max-height: 220px;
                    object-fit: contain;
                    background: #ffffff;
                    border: 1px solid #d8e5d4;
                    border-radius: 12px;
                    padding: 8px;
                }

                .chart-placeholder {
                    min-height: 210px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    text-align: center;
                    color: #6b7280;
                    background: #ffffff;
                    border: 1px dashed #bfd6b9;
                    border-radius: 12px;
                    font-size: 13px;
                    padding: 16px;
                }

                .chart-meta {
                    margin-top: 12px;
                    padding: 10px 12px;
                    background: #edf6e8;
                    border: 1px solid #d4e4ce;
                    border-radius: 12px;
                }

                .chart-meta-title {
                    margin: 0 0 6px;
                    color: #185a50;
                    font-size: 12px;
                    font-weight: 800;
                    text-transform: uppercase;
                    letter-spacing: 0.03em;
                }

                .chart-meta ul {
                    margin: 0;
                    padding-left: 18px;
                    color: #425c53;
                    font-size: 12px;
                }

                .chart-meta li + li {
                    margin-top: 4px;
                }

                .report-note {
                    background: #edf7eb;
                    border: 1px solid #c8ddc2;
                    border-radius: 16px;
                    padding: 14px 16px;
                    margin-bottom: 16px;
                    color: #3e5e54;
                    font-size: 13px;
                }

                .report-note strong {
                    color: #0f5a50;
                }

                .footer {
                    text-align: center;
                    color: #4d665d;
                    font-size: 12px;
                    border-top: 1px solid #b9d0b2;
                    padding-top: 16px;
                    margin-top: 20px;
                }

                @media print {
                    body {
                        margin: 0;
                        padding: 0;
                    }

                    .report-shell {
                        padding: 12px;
                    }

                    .summary-grid {
                        grid-template-columns: repeat(3, 1fr);
                        gap: 12px;
                    }

                    .overview-grid,
                    .charts-section {
                        grid-template-columns: 1fr 1fr;
                        gap: 14px;
                    }

                    .insight-card,
                    .chart-container,
                    .summary-section {
                        break-inside: avoid;
                    }

                    .header {
                        margin-bottom: 12px;
                        padding: 16px 18px 14px;
                    }

                    .title-banner,
                    .summary-section,
                    .report-note,
                    .overview-grid,
                    .charts-section {
                        margin-bottom: 12px;
                    }

                    .chart-container {
                        padding: 12px;
                    }

                    .chart-container h3 {
                        font-size: 14px;
                        margin-bottom: 10px;
                    }

                    .chart-image {
                        max-height: 175px;
                    }

                    .chart-meta {
                        margin-top: 10px;
                        padding: 8px 10px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="report-shell">
                <div class="header">
                    <div class="eyebrow">Monthly Library Report</div>
                    <h1>JHSCS KNOWLY</h1>
                    <h2>College Library</h2>
                    <p>Generated on ${dateStr}</p>
                </div>

                <div class="title-banner">
                    <h3>${monthTitle} Statistics</h3>
                    <p>This monthly report presents borrowing activity, student distribution by program, gender profile, and collection usage trends based on the latest dashboard data.</p>
                </div>

                <div class="summary-section">
                    <h3 class="section-title">Monthly Summary</h3>
                    <div class="summary-grid">
                        <div class="summary-item blue">
                            <p class="value">${monthlyBorrowed}</p>
                            <p class="label">Borrowed</p>
                        </div>
                        <div class="summary-item green">
                            <p class="value">${monthlyReturned}</p>
                            <p class="label">Returned</p>
                        </div>
                        <div class="summary-item orange">
                            <p class="value">${monthlyRegistrations}</p>
                            <p class="label">Registrations</p>
                        </div>
                    </div>
                </div>

                <div class="overview-grid">
                    <div class="insight-card">
                        <h3 class="section-title">Report Highlights</h3>
                        <p><strong>${monthlyActivityTotal}</strong> total monthly transactions were recorded from borrowed resources, returned resources, and new student registrations.</p>
                        <p style="margin-top:10px;"><strong>${totalBooks}</strong> total resources are currently tracked in the collection, with <strong>${activeBorrows}</strong> active borrows and <strong>${totalLoans}</strong> total loan records.</p>
                    </div>
                    <div class="insight-card">
                        <h3 class="section-title">Demographic Notes</h3>
                        <p>${topProgram ? `<strong>${topProgram.label}</strong> has the highest student count in the current dashboard dataset with <strong>${topProgram.count}</strong> students.` : 'Program distribution data is not available for this month.'}</p>
                        <p style="margin-top:10px;">${topGender ? `<strong>${topGender.label}</strong> represents the largest gender group with <strong>${topGender.count}</strong> students, or approximately <strong>${topGenderPercentage}%</strong> of all registered students.` : 'Gender distribution data is not available for this month.'}</p>
                    </div>
                </div>

                <div class="charts-section">
                    <div class="chart-container">
                        <h3>Books Distribution by Program</h3>
                        ${bookStatusImage ? `<img src="${bookStatusImage}" alt="Book Status Chart" class="chart-image">` : '<div class="chart-placeholder"><i class="fas fa-chart-pie fa-2x"></i><br><br>No chart data available</div>'}
                        ${booksPercentageList ? `<div class="chart-meta"><p class="chart-meta-title">Percentage Breakdown</p><ul>${booksPercentageList}</ul></div>` : ''}
                    </div>
                    <div class="chart-container">
                        <h3>Students per Program</h3>
                        ${studentsProgramImage ? `<img src="${studentsProgramImage}" alt="Students per Program Chart" class="chart-image">` : '<div class="chart-placeholder"><i class="fas fa-chart-bar fa-2x"></i><br><br>No chart data available</div>'}
                        ${studentsPercentageList ? `<div class="chart-meta"><p class="chart-meta-title">Percentage Breakdown</p><ul>${studentsPercentageList}</ul></div>` : ''}
                    </div>
                </div>

                <div class="charts-section">
                    <div class="chart-container">
                        <h3>Monthly Borrowing Trends</h3>
                        ${monthlyTrendsImage ? `<img src="${monthlyTrendsImage}" alt="Monthly Trends Chart" class="chart-image">` : '<div class="chart-placeholder"><i class="fas fa-chart-line fa-2x"></i><br><br>No chart data available</div>'}
                    </div>
                    <div class="chart-container">
                        <h3>Student Gender Distribution</h3>
                        ${genderImage ? `<img src="${genderImage}" alt="Gender Distribution Chart" class="chart-image">` : '<div class="chart-placeholder"><i class="fas fa-venus-mars fa-2x"></i><br><br>No chart data available</div>'}
                        ${genderPercentageList ? `<div class="chart-meta"><p class="chart-meta-title">Percentage Breakdown</p><ul>${genderPercentageList}</ul></div>` : ''}
                    </div>
                </div>

                <div class="report-note">
                    <strong>Interpretation:</strong> This report is intended for monthly library monitoring. It summarizes collection distribution, student participation by program, gender profile, and transaction trends to support planning, resource allocation, and service improvements.
                </div>

                <div class="footer">
                    <p>This report was generated automatically by the JHSCS KNOWLY Library Management System.</p>
                    <p>Report Date: ${dateStr}</p>
                </div>
            </div>
        </body>
        </html>
        `;

        // Write content to the new window
        printWindow.document.write(printContent);
        printWindow.document.close();

        // Wait for content to load, then print
        printWindow.onload = function() {
            printWindow.print();
            // Optionally close the window after printing
            // printWindow.close();
        };
    }

    loadRecentActivities() {
        const container = document.getElementById('recent-activities');
        if (!container) return;

        // Show loading state
        container.innerHTML = `
            <div class="flex justify-center items-center py-12">
                <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mr-3"></i>
                <span class="text-gray-600">Loading activities...</span>
            </div>
        `;

        // Use dashboard data if available
        if (this.dashboardData.most_borrowed_books && this.dashboardData.most_borrowed_books.length > 0) {
            this.renderRecentActivities(this.dashboardData.most_borrowed_books.slice(0, 10));
        } else {
            container.innerHTML = `
                <div class="flex justify-center items-center py-12">
                    <i class="fas fa-info-circle text-2xl text-gray-400 mr-3"></i>
                    <span class="text-gray-600">No recent activities available</span>
                </div>
            `;
        }
    }

    renderRecentActivities(activities) {
        const container = document.getElementById('recent-activities');
        if (!container) return;

        if (!activities || activities.length === 0) {
            container.innerHTML = `
                <div class="flex justify-center items-center py-12">
                    <i class="fas fa-info-circle text-2xl text-gray-400 mr-3"></i>
                    <span class="text-gray-600">No recent activities available</span>
                </div>
            `;
            return;
        }

        const html = activities.map((activity, index) => `
            <div class="flex items-center p-4 hover:bg-gray-50 transition-colors ${index !== activities.length - 1 ? 'border-b border-gray-100' : ''}">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-book text-blue-600 text-sm"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900">${activity.title || 'Unknown Book'}</p>
                    <p class="text-xs text-gray-500">Borrowed ${activity.borrow_count || 0} times</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-gray-400">#${index + 1}</span>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    loadMostBorrowedBooks() {
        const container = document.getElementById('most-borrowed-books');
        if (!container) return;

        // Show loading state
        container.innerHTML = `
            <div class="flex justify-center items-center py-12">
                <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mr-3"></i>
                <span class="text-gray-600">Loading books...</span>
            </div>
        `;

        // Use dashboard data if available
        const books = this.dashboardData.most_borrowed_books || [];
        
        if (books.length > 0) {
            this.renderMostBorrowedBooks(books);
        } else {
            container.innerHTML = `
                <div class="flex justify-center items-center py-12">
                    <i class="fas fa-info-circle text-2xl text-gray-400 mr-3"></i>
                    <span class="text-gray-600">No borrowed books data available</span>
                </div>
            `;
        }
    }

    renderMostBorrowedBooks(books) {
        const container = document.getElementById('most-borrowed-books');
        if (!container) return;

        if (!books || books.length === 0) {
            container.innerHTML = `
                <div class="flex justify-center items-center py-12">
                    <i class="fas fa-info-circle text-2xl text-gray-400 mr-3"></i>
                    <span class="text-gray-600">No borrowed books data available</span>
                </div>
            `;
            return;
        }

        const html = books.map((book, index) => `
            <div class="flex items-center p-3 hover:bg-gray-50 transition-colors ${index !== books.length - 1 ? 'border-b border-gray-100' : ''}">
                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mr-3 text-white font-bold text-sm">
                    ${index + 1}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">${book.title || 'Unknown Title'}</p>
                    <p class="text-xs text-gray-500 truncate">${book.author || 'Unknown Author'}</p>
                </div>
                <div class="text-right ml-2">
                    <p class="text-sm font-semibold text-blue-600">${book.borrow_count || 0}</p>
                    <p class="text-xs text-gray-400">borrows</p>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    updateTodaysSummary() {
        const recentActivity = this.dashboardData.recent_activity || {};
        
        this.animateCounter('today-borrows', recentActivity.borrows || 0);
        this.animateCounter('today-returns', recentActivity.returns || 0);
        this.animateCounter('today-registrations', recentActivity.registrations || 0);
    }

    setupNotifications() {
        // Initialize notification system
        this.notifications = [];
        this.unreadCount = 0;
    }

    setupDropdowns() {
        // Setup dropdown functionality
        this.dropdowns = {
            notifications: document.getElementById('notifications-dropdown'),
            export: document.getElementById('export-dropdown')
        };
    }

    toggleNotificationsDropdown() {
        const dropdown = this.dropdowns.notifications;
        if (!dropdown) return;

        const isHidden = dropdown.classList.contains('hidden');
        
        // Close other dropdowns
        this.closeExportDropdown();
        
        if (isHidden) {
            dropdown.classList.remove('hidden');
            dropdown.classList.add('show');
            // Mark notifications as read when opened
            this.markAllNotificationsAsRead();
        } else {
            this.closeNotificationsDropdown();
        }
    }

    closeNotificationsDropdown() {
        const dropdown = this.dropdowns.notifications;
        if (!dropdown) return;
        
        dropdown.classList.add('hidden');
        dropdown.classList.remove('show');
    }

    toggleExportDropdown() {
        const dropdown = this.dropdowns.export;
        if (!dropdown) return;

        const isHidden = dropdown.classList.contains('hidden');
        
        // Close other dropdowns
        this.closeNotificationsDropdown();
        
        if (isHidden) {
            dropdown.classList.remove('hidden');
            dropdown.classList.add('show');
        } else {
            this.closeExportDropdown();
        }
    }

    closeExportDropdown() {
        const dropdown = this.dropdowns.export;
        if (!dropdown) return;
        
        dropdown.classList.add('hidden');
        dropdown.classList.remove('show');
    }

    checkSessionValidity() {
        // Check if session is still valid by making a simple request
        this.makeAuthenticatedRequest('/librarian/dashboard/stats')
            .catch(error => {
                if (error.message === 'Authentication required') {
                    
                    this.stopPolling();
                }
            });
    }

    stopPolling() {
        if (this.notificationsInterval) {
            clearInterval(this.notificationsInterval);
            this.notificationsInterval = null;
        }
    }

    getCsrfToken() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) {
            
            return null;
        }
        return token;
    }

    async makeAuthenticatedRequest(url, options = {}) {
        const defaultHeaders = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': this.getCsrfToken()
        };

        const response = await fetch(url, {
            ...options,
            headers: {
                ...defaultHeaders,
                ...options.headers
            }
        });

        // Handle authentication errors
        if (response.status === 401) {
            window.location.href = '/librarian/login';
            throw new Error('Authentication required');
        }

        // Handle CSRF errors
        if (response.status === 419) {
            
            window.location.reload();
            throw new Error('CSRF token expired');
        }

        return response;
    }

    async loadNotifications() {
        try {
            const response = await this.makeAuthenticatedRequest('/librarian/dashboard/alerts');

            if (!response.ok) {
                throw new Error('Failed to load notifications');
            }

            const data = await response.json();
            this.notifications = data.notifications || [];
            this.unreadCount = data.unread_count || this.notifications.filter(n => !n.read && !n.is_read).length;
            
            this.renderNotifications();
            this.updateNotificationBadge();
            
        } catch (error) {
            this.notifications = [];
            this.unreadCount = 0;
            this.renderNotifications('Unable to load notifications right now.');
            this.updateNotificationBadge();
        }
    }

    renderNotifications(emptyMessage = 'No notifications') {
        const container = document.getElementById('notifications-list');
        const badge = document.getElementById('notification-badge');
        
        if (!container) return;

        if (!this.notifications.length) {
            container.innerHTML = `
                <div class="p-4 text-center text-gray-500">
                    <i class="fas fa-bell-slash text-2xl mb-2"></i>
                    <p class="text-sm">${emptyMessage}</p>
                </div>
            `;
            return;
        }

        const html = this.notifications.map(notification => `
            <div class="notification-item p-4 border-b hover:bg-gray-50 cursor-pointer transition-colors ${!notification.read && !notification.is_read ? 'bg-blue-50' : ''}" 
                 data-id="${notification.id}"
                 data-action-url="${notification.data?.action_url || ''}">
                <div class="flex items-start">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3 flex-shrink-0
                        ${notification.type === 'warning' ? 'bg-orange-100 text-orange-600' : 
                          notification.type === 'error' ? 'bg-red-100 text-red-600' : 
                          notification.type === 'success' ? 'bg-green-100 text-green-600' : 
                          'bg-blue-100 text-blue-600'}">
                        <i class="fas fa-${this.getNotificationIcon(notification.type)} text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">${notification.message || notification.title || 'Notification'}</p>
                        <p class="text-xs text-gray-600 mt-1">${notification.description || ''}</p>
                        <p class="text-xs text-gray-400 mt-2">${this.formatNotificationTime(notification.created_at)}</p>
                    </div>
                    ${!notification.read && !notification.is_read ? '<div class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 mt-2"></div>' : ''}
                </div>
            </div>
        `).join('');

        container.innerHTML = html;

        // Add click handlers to notifications
        container.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', async () => {
                const id = parseInt(item.dataset.id);
                const actionUrl = item.dataset.actionUrl;
                await this.markNotificationAsRead(id);

                if (actionUrl) {
                    window.location.href = actionUrl;
                }
            });
        });
    }

    getNotificationIcon(type) {
        const icons = {
            'info': 'info-circle',
            'warning': 'exclamation-triangle',
            'error': 'times-circle',
            'success': 'check-circle',
            'system': 'cog'
        };
        return icons[type] || 'info-circle';
    }

    formatNotificationTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
        if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
        if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
        
        return date.toLocaleDateString();
    }

    updateNotificationBadge() {
        const badge = document.getElementById('notification-badge');
        if (!badge) return;

        if (this.unreadCount > 0) {
            badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    async markNotificationAsRead(id) {
        try {
            const response = await this.makeAuthenticatedRequest(`/librarian/dashboard/notifications/${id}/read`, {
                method: 'POST'
            });

            if (response.ok) {
                const notification = this.notifications.find(n => n.id === id);
                if (notification) {
                    notification.read = true;
                    notification.is_read = true;
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                    this.renderNotifications();
                    this.updateNotificationBadge();
                }
            }
        } catch (error) {
            
            // Fallback: update locally
            const notification = this.notifications.find(n => n.id === id);
            if (notification) {
                notification.read = true;
                notification.is_read = true;
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                this.renderNotifications();
                this.updateNotificationBadge();
            }
        }
    }

    async markAllNotificationsAsRead() {
        if (this.unreadCount === 0) return;

        try {
            const response = await this.makeAuthenticatedRequest('/librarian/dashboard/notifications/mark-all-read', {
                method: 'POST'
            });

            if (response.ok) {
                this.notifications.forEach(n => {
                    n.read = true;
                    n.is_read = true;
                });
                this.unreadCount = 0;
                this.renderNotifications();
                this.updateNotificationBadge();
            }
        } catch (error) {
            
            // Fallback: update locally
            this.notifications.forEach(n => {
                n.read = true;
                n.is_read = true;
            });
            this.unreadCount = 0;
            this.renderNotifications();
            this.updateNotificationBadge();
        }
    }

    async exportData(type) {
        try {
            this.showNotification('Preparing export...', 'success');
            
            const response = await this.makeAuthenticatedRequest(`/librarian/dashboard/export?type=${type}`);

            if (!response.ok) {
                throw new Error('Failed to export data');
            }

            const data = await response.json();
            
            if (data.download_url) {
                // Create download link
                const link = document.createElement('a');
                link.href = data.download_url;
                link.download = data.filename || `export-${type}-${Date.now()}.csv`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                this.showNotification('Export downloaded successfully', 'success');
            } else if (data.data) {
                // Direct data export
                this.downloadCSV(data.data, type);
                this.showNotification('Export completed successfully', 'success');
            }
            
        } catch (error) {
            
            this.showNotification('Failed to export data', 'error');
        }
        
        // Close export dropdown
        this.closeExportDropdown();
    }

    downloadCSV(data, type) {
        let csvContent = '';
        let filename = `export-${type}-${Date.now()}.csv`;
        
        if (type === 'statistics') {
            csvContent = 'Category,Count\n';
            if (this.dashboardData.basic_stats) {
                csvContent += `Total Books,${this.dashboardData.basic_stats.total_books || 0}\n`;
                csvContent += `Total Students,${this.dashboardData.basic_stats.total_students || 0}\n`;
                csvContent += `Active Borrows,${this.dashboardData.basic_stats.active_borrows || 0}\n`;
                csvContent += `Total Loans,${this.dashboardData.basic_stats.total_loans || 0}\n`;
            }
        } else if (type === 'overview') {
            csvContent = 'Report,Value\n';
            if (this.dashboardData.basic_stats) {
                csvContent += `Total Books,${this.dashboardData.basic_stats.total_books || 0}\n`;
                csvContent += `Total Students,${this.dashboardData.basic_stats.total_students || 0}\n`;
                csvContent += `Active Borrows,${this.dashboardData.basic_stats.active_borrows || 0}\n`;
                csvContent += `Available Books,${this.dashboardData.basic_stats.available_books || 0}\n`;
            }
            
            if (this.dashboardData.recent_activity) {
                csvContent += `Recent Borrows (30 days),${this.dashboardData.recent_activity.borrows || 0}\n`;
                csvContent += `Recent Returns (30 days),${this.dashboardData.recent_activity.returns || 0}\n`;
                csvContent += `Recent Registrations (30 days),${this.dashboardData.recent_activity.registrations || 0}\n`;
            }
        }
        
        // Create download
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    async clearAllNotifications() {
        try {
            // Try to clear on server first
            const response = await this.makeAuthenticatedRequest('/librarian/dashboard/notifications', {
                method: 'DELETE'
            });

            // Clear locally regardless of server response
            this.notifications = [];
            this.unreadCount = 0;
            this.renderNotifications();
            this.updateNotificationBadge();
            
            this.showNotification('All notifications cleared', 'success');
            
        } catch (error) {
            
            // Fallback: clear locally
            this.notifications = [];
            this.unreadCount = 0;
            this.renderNotifications();
            this.updateNotificationBadge();
            this.showNotification('All notifications cleared', 'success');
        }
    }

    handleChartToggle(e, toggle) {
        const chartType = toggle.dataset.chart;
        const targetChart = toggle.dataset.target;
        
        if (!targetChart) return;
        
        // Update active button styling
        const container = toggle.parentElement;
        container.querySelectorAll('.chart-toggle').forEach(btn => {
            btn.classList.remove('active');
        });
        toggle.classList.add('active');
        
        // Recreate chart with new type
        if (targetChart === 'book-status-chart') {
            this.createBookStatusChart(chartType);
        }
    }

    showCustomReportModal() {
        const modal = document.getElementById('customReportModal');
        if (!modal) return;
        
        modal.classList.remove('hidden');
        
        // Set default date range (last 30 days)
        const today = new Date();
        const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
        
        const dateFromInput = modal.querySelector('input[name="date_from"]');
        const dateToInput = modal.querySelector('input[name="date_to"]');
        
        if (dateFromInput) {
            dateFromInput.value = thirtyDaysAgo.toISOString().split('T')[0];
        }
        if (dateToInput) {
            dateToInput.value = today.toISOString().split('T')[0];
        }
        
        // Setup form submission
        this.setupCustomReportForm();
    }

    hideCustomReportModal() {
        const modal = document.getElementById('customReportModal');
        if (!modal) return;
        
        modal.classList.add('hidden');
    }

    setupCustomReportForm() {
        const form = document.getElementById('customReportForm');
        if (!form) return;
        
        // Remove existing listener to avoid duplicates
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        
        newForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.generateCustomReport(newForm);
        });
    }

    async generateCustomReport(form) {
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        
        try {
            this.showNotification('Generating report...', 'success');
            
            // Close the custom report form modal
            this.hideCustomReportModal();
            
            // Show the report display modal
            this.showReportModal();
            
            // Fetch report data
            const response = await this.makeAuthenticatedRequest(`/librarian/reports/generate?${params.toString()}`, {
                headers: {
                    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to generate report');
            }
            
            const reportHTML = await response.text();
            
            // Update report content
            this.updateReportContent(reportHTML, formData);
            
            this.showNotification('Report generated successfully', 'success');
            
        } catch (error) {
            
            this.hideReportModal();
            this.showNotification('Failed to generate report', 'error');
        }
    }

    showReportModal() {
        const modal = document.getElementById('reportDisplayModal');
        if (!modal) return;
        
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }

    hideReportModal() {
        const modal = document.getElementById('reportDisplayModal');
        if (!modal) return;
        
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto'; // Restore background scrolling
    }

    updateReportContent(html, formData) {
        const content = document.getElementById('reportContent');
        const title = document.getElementById('reportTitle');
        const subtitle = document.getElementById('reportSubtitle');
        
        if (!content) return;
        
        // Update title based on report type
        const reportType = formData.get('type');
        const reportTitles = {
            'borrowing-statistics': 'Borrowing Statistics Report',
            'student-activity': 'Student Activity Report',
            'book-usage': 'Book Usage Analysis',
            'popular-books': 'Popular Books Report',
            'course-analysis': 'Course Analysis Report',
            'monthly-summary': 'Monthly Summary Report'
        };
        
        if (title) {
            title.textContent = reportTitles[reportType] || 'Generated Report';
        }
        
        if (subtitle) {
            const dateFrom = formData.get('date_from');
            const dateTo = formData.get('date_to');
            const course = formData.get('course');
            
            let subtitleText = 'Custom report';
            if (dateFrom && dateTo) {
                subtitleText += ` from ${new Date(dateFrom).toLocaleDateString()} to ${new Date(dateTo).toLocaleDateString()}`;
            }
            if (course) {
                subtitleText += ` for ${course}`;
            }
            subtitle.textContent = subtitleText;
        }
        
        // Parse and extract the main content from the HTML response
        // Remove head, nav, footer, and other unwanted elements
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // Try to find the main content area
        const mainContent = doc.querySelector('main, .container, .content, #content, body');
        if (mainContent) {
            content.innerHTML = mainContent.innerHTML;
        } else {
            // Fallback: show the entire HTML
            content.innerHTML = html;
        }
        
        // Apply some styling to make it look better in the modal
        this.styleReportContent();
    }

    styleReportContent() {
        const content = document.getElementById('reportContent');
        if (!content) return;
        
        // Add custom styles for report content
        const style = document.createElement('style');
        style.textContent = `
            #reportContent table {
                width: 100%;
                border-collapse: collapse;
                margin: 1rem 0;
            }
            #reportContent th, #reportContent td {
                border: 1px solid #e5e7eb;
                padding: 0.75rem;
                text-align: left;
            }
            #reportContent th {
                background-color: #f9fafb;
                font-weight: 600;
            }
            #reportContent tr:nth-child(even) {
                background-color: #f9fafb;
            }
            #reportContent h1, #reportContent h2, #reportContent h3 {
                margin-top: 1.5rem;
                margin-bottom: 0.5rem;
                color: #1f2937;
            }
            #reportContent p {
                margin-bottom: 1rem;
                color: #4b5563;
            }
        `;
        
        if (!document.querySelector('#report-content-styles')) {
            style.id = 'report-content-styles';
            document.head.appendChild(style);
        }
    }

    printReportContent() {
        const content = document.getElementById('reportContent');
        const title = document.getElementById('reportTitle');
        
        if (!content) return;
        
        const printWindow = window.open('', '_blank', 'width=1200,height=800');
        
        const printHTML = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>${title ? title.textContent : 'Report'}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f5f5f5; font-weight: bold; }
                    tr:nth-child(even) { background-color: #f9f9f9; }
                    h1, h2, h3 { color: #333; }
                    @media print { body { margin: 10px; } }
                </style>
            </head>
            <body>
                <h1>${title ? title.textContent : 'Report'}</h1>
                ${content.innerHTML}
            </body>
            </html>
        `;
        
        printWindow.document.write(printHTML);
        printWindow.document.close();
        printWindow.print();
    }

    downloadReport() {
        const content = document.getElementById('reportContent');
        const title = document.getElementById('reportTitle');
        
        if (!content) return;
        
        // Create a temporary blob and download
        const htmlContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>${title ? title.textContent : 'Report'}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f5f5f5; font-weight: bold; }
                    tr:nth-child(even) { background-color: #f9f9f9; }
                    h1, h2, h3 { color: #333; }
                </style>
            </head>
            <body>
                <h1>${title ? title.textContent : 'Report'}</h1>
                ${content.innerHTML}
            </body>
            </html>
        `;
        
        const blob = new Blob([htmlContent], { type: 'text/html' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `${title ? title.textContent.toLowerCase().replace(/\s+/g, '-') : 'report'}-${Date.now()}.html`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        
        this.showNotification('Report downloaded successfully', 'success');
    }

    showNotification(message, type = 'success') {
        // Simple notification system (can be enhanced)
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg border z-50 ${type === 'success' ? 'bg-white text-blue-600 border-blue-200' : 'bg-red-500 text-white border-red-500'}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

}

        // Initialize the dashboard when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            window.librarianDashboard = new LibrarianDashboard();
        });

        // Global functions for HTML onclick handlers
        window.printDashboard = function() {
            if (window.librarianDashboard) {
                window.librarianDashboard.printReport();
            }
        };

        window.exportData = function(type) {
            if (window.librarianDashboard) {
                window.librarianDashboard.exportData(type);
            }
        };

        window.generateCustomReport = function() {
            if (window.librarianDashboard) {
                window.librarianDashboard.showCustomReportModal();
            }
        };

        window.closeCustomReportModal = function() {
            if (window.librarianDashboard) {
                window.librarianDashboard.hideCustomReportModal();
            }
        };

        window.closeReportModal = function() {
            if (window.librarianDashboard) {
                window.librarianDashboard.hideReportModal();
            }
        };

        window.printReportContent = function() {
            if (window.librarianDashboard) {
                window.librarianDashboard.printReportContent();
            }
        };

        window.downloadReport = function() {
            if (window.librarianDashboard) {
                window.librarianDashboard.downloadReport();
            }
        };

        // Export for potential module use
        if (typeof module !== 'undefined' && module.exports) {
            module.exports = LibrarianDashboard;
        }
