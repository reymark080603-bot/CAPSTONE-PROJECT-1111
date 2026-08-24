<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Knowly Librarian</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/librarian-dashboard.css') }}" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="header bg-blue-600 shadow-lg">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center space-x-4">
                <!-- Sidebar Toggle Button -->
                <button id="sidebar-toggle" class="sidebar-toggle text-white hover:bg-white/10 p-2 rounded-lg transition-all">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                
                <div class="flex items-center">
                    <i class="fas fa-user-tie text-white text-2xl mr-3"></i>
                    <div>
                        <h1 class="text-white text-2xl font-bold">Knowly</h1>
                        <p class="text-blue-100 text-sm">Librarian Dashboard</p>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                     <!-- Notifications -->
                <div class="relative">
                    <button id="layout-notifications-btn" class="text-white hover:bg-white/10 p-2 rounded-lg transition-all relative" type="button">
                        <i class="fas fa-bell text-lg"></i>
                        <span id="layout-notification-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
                    </button>
                    <div id="layout-notifications-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border z-50">
                        <div class="p-4 border-b flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900">System Alerts</h3>
                            <div class="flex items-center gap-2">
                                <button id="layout-mark-all" class="text-xs text-blue-600 hover:text-blue-700 px-2 py-1 rounded hover:bg-blue-50" title="Mark all as read">
                                    <i class="fas fa-check-double"></i>
                                </button>
                                <button id="layout-clear-all" class="text-xs text-red-600 hover:text-red-700 px-2 py-1 rounded hover:bg-red-50" title="Clear notifications">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div id="layout-notifications-list" class="max-h-64 overflow-y-auto"></div>
                    </div>
                </div>
                
                <!-- User Profile -->
                <div class="flex items-center space-x-3">
                    <div class="text-right">
                        <p class="text-white font-medium">{{ Auth::user()->name ?? 'Librarian' }}</p>
                        <p class="text-blue-100 text-sm">Librarian</p>
                    </div>
                    <div class="w-10 h-10 bg-white text-blue-700 border-2 border-white/60 rounded-full flex items-center justify-center">
                        <span class="text-blue-700 font-medium flex items-center justify-center w-full h-full">{{ substr(Auth::user()->name ?? 'L', 0, 1) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-container">
        <!-- Mobile Backdrop -->
        <div class="sidebar-backdrop" id="sidebar-backdrop"></div>
        
        <!-- Sidebar -->
        <div class="sidebar bg-gray-800 min-h-screen">
            <div class="p-4">
                <div class="sidebar-welcome text-white mb-8">
                    <div class="flex items-center mb-3">
                        <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-user-tie text-white text-lg"></i>
                        </div>
                        <div>
                            <h2 class="font-semibold text-lg">{{ Auth::user()->name ?? 'Librarian' }}</h2>
                            <p class="text-blue-200 text-xs font-medium">LIBRARIAN</p>
                        </div>
                    </div>
                    <div class="bg-gray-700/50 rounded-lg p-3">
                        <p class="text-gray-300 text-sm font-medium mb-1">Library Management System</p>
                        <p class="text-gray-400 text-xs">Administrative Access</p>
                    </div>
                </div>
                
                <nav class="space-y-2">
                    <a href="{{ route('librarian.dashboard') }}" class="sidebar-link flex items-center space-x-3 {{ request()->routeIs('librarian.dashboard') ? 'text-white active' : 'text-gray-300' }} px-4 py-3 rounded-lg transition-all hover:text-white">
                        <i class="fas fa-chart-pie sidebar-icon"></i>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                    <a href="{{ route('librarian.books.index') }}" class="sidebar-link flex items-center space-x-3 {{ request()->routeIs('librarian.books.*') && !request()->routeIs('librarian.books.bulk.*') ? 'text-white active' : 'text-gray-300' }} px-4 py-3 rounded-lg transition-all hover:text-white">
                        <i class="fas fa-book sidebar-icon"></i>
                        <span class="sidebar-text">Manage E-Resource</span>
                    </a>
                    <a href="{{ route('librarian.books.bulk.upload') }}" class="sidebar-link flex items-center space-x-3 {{ request()->routeIs('librarian.books.bulk.*') ? 'text-white active' : 'text-gray-300' }} px-4 py-3 rounded-lg transition-all hover:text-white">
                        <i class="fas fa-file-csv sidebar-icon"></i>
                        <span class="sidebar-text">Bulk Upload</span>
                    </a>
                    <a href="{{ route('librarian.students.index') }}" class="sidebar-link flex items-center space-x-3 {{ request()->routeIs('librarian.students.*') ? 'text-white active' : 'text-gray-300' }} px-4 py-3 rounded-lg transition-all hover:text-white">
                        <i class="fas fa-users sidebar-icon"></i>
                        <span class="sidebar-text">Manage Students</span>
                    </a>
                    <a href="{{ route('librarian.loans.index') }}" class="sidebar-link flex items-center space-x-3 {{ request()->routeIs('librarian.loans.*') ? 'text-white active' : 'text-gray-300' }} px-4 py-3 rounded-lg transition-all hover:text-white">
                        <i class="fas fa-hand-holding sidebar-icon"></i>
                        <span class="sidebar-text">E-Resource Monitoring</span>
                    </a>
                    <a href="{{ route('librarian.reports.index') }}" class="sidebar-link flex items-center space-x-3 {{ request()->routeIs('librarian.reports.*') ? 'text-white active' : 'text-gray-300' }} px-4 py-3 rounded-lg transition-all hover:text-white">
                        <i class="fas fa-chart-bar sidebar-icon"></i>
                        <span class="sidebar-text">Reports</span>
                    </a>
                    <form action="{{ route('librarian.logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="sidebar-link flex items-center space-x-3 text-red-300 px-4 py-3 rounded-lg transition-all hover:text-white hover:bg-red-600 w-full text-left">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="sidebar-text">Logout</span>
                        </button>
                    </form>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            @yield('content')
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Sidebar toggle functionality & notifications
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.querySelector('.sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            const container = document.querySelector('.dashboard-container');
            
            // Check if we're on mobile
            function isMobile() {
                return window.innerWidth <= 1024;
            }
            
            // Toggle sidebar
            function toggleSidebar() {
                if (isMobile()) {
                    // Mobile behavior
                    sidebar.classList.toggle('sidebar-open');
                    backdrop.classList.toggle('active');
                } else {
                    // Desktop behavior
                    container.classList.toggle('sidebar-closed');
                }
            }
            
            // Close sidebar
            function closeSidebar() {
                if (isMobile()) {
                    sidebar.classList.remove('sidebar-open');
                    backdrop.classList.remove('active');
                } else {
                    container.classList.add('sidebar-closed');
                }
            }
            
            // Sidebar toggle button
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleSidebar);
            }
            
            // Backdrop click (mobile only)
            if (backdrop) {
                backdrop.addEventListener('click', function() {
                    if (isMobile()) {
                        closeSidebar();
                    }
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (!isMobile()) {
                    // Reset mobile classes when switching to desktop
                    sidebar.classList.remove('sidebar-open');
                    backdrop.classList.remove('active');
                } else {
                    // Reset desktop classes when switching to mobile
                    container.classList.remove('sidebar-closed');
                }
            });
            
            // Initialize based on screen size
            if (isMobile()) {
                sidebar.classList.remove('sidebar-open');
                backdrop.classList.remove('active');
            }
            
            // Notifications logic (shared across pages)
            const btn = document.getElementById('layout-notifications-btn');
            const dd = document.getElementById('layout-notifications-dropdown');
            const list = document.getElementById('layout-notifications-list');
            const badge = document.getElementById('layout-notification-badge');
            
            async function loadAlerts() {
                try {
                    const res = await fetch('/librarian/dashboard/alerts', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        }
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    const alerts = data.notifications || [];
                    const unread = data.unread_count ?? alerts.filter(a => !a.is_read).length;

                    if (badge) {
                        if (unread > 0) { badge.textContent = unread; badge.classList.remove('hidden'); }
                        else { badge.classList.add('hidden'); }
                    }
                    if (list) {
                        if (alerts.length === 0) {
                            list.innerHTML = '<div class="p-4 text-center text-gray-500">No alerts</div>';
                        } else {
                            list.innerHTML = alerts.map(a => {
                                const isUnread = !a.is_read;
                                return (
                                `<div class="p-3 border-b last:border-b-0 ${isUnread ? '' : 'opacity-60'}">
                                    <div class="font-medium text-sm">${a.message || 'Notification'}</div>
                                    <div class="text-xs text-gray-600 mt-1">${a.description || ''}</div>
                                    <div class="text-xs text-gray-400 mt-1">${new Date(a.created_at).toLocaleString()}</div>
                                </div>`);
                            }).join('');
                        }
                    }
                } catch (e) {
                    // silent fail
                }
            }
            
            if (btn && dd) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dd.classList.toggle('hidden');
                    if (!dd.classList.contains('hidden')) loadAlerts();
                });
                document.addEventListener('click', function() { dd.classList.add('hidden'); });
                // initial load and polling
                loadAlerts();
                setInterval(loadAlerts, 60000);

                // Mark all as read
                const markAll = document.getElementById('layout-mark-all');
                const clearAll = document.getElementById('layout-clear-all');
                if (markAll) {
                    markAll.addEventListener('click', async function(e){
                        e.stopPropagation();
                        try {
                            await fetch('/librarian/dashboard/notifications/mark-all-read', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                                }
                            });
                        } catch (e) {
                            // keep UI responsive even if request fails
                        }
                        loadAlerts();
                    });
                }
                if (clearAll) {
                    clearAll.addEventListener('click', async function(e){
                        e.stopPropagation();
                        try {
                            await fetch('/librarian/dashboard/notifications', {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                                }
                            });
                        } catch (e) {
                            // keep UI responsive even if request fails
                        }
                        loadAlerts();
                    });
                }
            }
        });
    </script>

    @yield('scripts')
</body>
</html>
