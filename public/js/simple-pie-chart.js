// Complete dashboard with working pie chart
class SimplePieChart {
    constructor() {
        this.charts = {};
        this.init();
    }

    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.loadDashboardData());
        } else {
            this.loadDashboardData();
        }
    }

    async loadDashboardData() {
        try {
            // Get CSRF token
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            console.log('Making API request to /librarian/dashboard/stats');
            console.log('CSRF token:', token);
            
            const response = await fetch('/librarian/dashboard/stats', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                credentials: 'same-origin'
            });

            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Response error text:', errorText);
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }

            const data = await response.json();
            console.log('Dashboard data received:', data);
            
            if (!data || !data.borrowed_books_by_course) {
                console.error('No course data in response:', data);
                this.showErrorMessage('No course data available');
                return;
            }
            
            this.createPieChart(data.borrowed_books_by_course);
            
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            this.showErrorMessage(`Error: ${error.message}`);
        }
    }

    showErrorMessage(message) {
        const ctx = document.getElementById('book-status-chart');
        if (!ctx) return;
        
        const context = ctx.getContext('2d');
        context.clearRect(0, 0, ctx.width, ctx.height);
        context.font = '16px system-ui';
        context.fillStyle = '#ef4444';
        context.textAlign = 'center';
        context.textBaseline = 'middle';
        context.fillText(message, ctx.width / 2, ctx.height / 2);
    }

    createPieChart(courseData) {
        const ctx = document.getElementById('book-status-chart');
        if (!ctx) return;

        // Clear any existing charts on this canvas
        Chart.helpers.each(Chart.instances, function(instance) {
            if (instance.canvas.id === 'book-status-chart') {
                instance.destroy();
            }
        });

        // Define all 6 courses we want to show
        const allCourses = ['BSIT', 'BSN', 'BSHM', 'BSTM', 'BSED', 'BSEntrep'];
        
        // Create data for all courses (zero for missing ones)
        const labels = [];
        const data = [];
        const colors = [
            '#3b82f6', // BSIT - Blue
            '#ef4444', // BSN - Red  
            '#f59e0b', // BSHM - Yellow
            '#10b981', // BSTM - Green
            '#8b5cf6', // BSED - Purple
            '#06b6d4'  // BSEntrep - Cyan
        ];

        allCourses.forEach(course => {
            labels.push(course);
            data.push(courseData[course] || 0);
        });

        console.log('Final chart data:', { labels, data });

        // Create new chart
        this.charts.bookStatus = new Chart(ctx, {
            type: 'doughnut', // Changed from 'pie' to 'doughnut'
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
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
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} books (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        console.log('Pie chart created successfully with all 6 courses!');
    }
}

// Initialize immediately
new SimplePieChart();
