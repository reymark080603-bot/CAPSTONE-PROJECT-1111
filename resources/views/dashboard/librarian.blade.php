<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Knowly - Librarian Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/librarian-dashboard.css') }}" rel="stylesheet">
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
                    <i class="fas fa-user-bars text-white text-2xl mr-3"></i>
                    <div>
                        <h1 class="text-white text-2xl font-bold">Knowly</h1>
                        <p class="text-blue-100 text-sm">Librarian Dashboard</p>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <!-- Notifications -->
                <div class="relative">
                    <button id="notifications-btn" class="text-white hover:bg-white/10 p-2 rounded-lg transition-all relative">
                        <i class="fas fa-bell text-lg"></i>
                        <span id="notification-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 hidden">0</span>
                    </button>
                    <div id="notifications-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border z-50">
                        <div class="p-4 border-b flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900">System Alerts</h3>
                            <div class="flex items-center gap-2">
                                <button id="notifications-mark-all" class="text-xs text-blue-600 hover:text-blue-700 px-2 py-1 rounded hover:bg-blue-50" title="Mark all as read">
                                    <i class="fas fa-check-double"></i>
                                </button>
                                <button id="notifications-clear-all" class="text-xs text-red-600 hover:text-red-700 px-2 py-1 rounded hover:bg-red-50" title="Clear notifications">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div id="notifications-list" class="max-h-64 overflow-y-auto">
                            <!-- Notifications will be populated here -->
                        </div>
                    </div>
                </div>
                
                <!-- Export Menu -->
                <div class="relative">
                    <button id="export-btn" class="text-white hover:bg-white/10 p-2 rounded-lg transition-all">
                        <i class="fas fa-download text-lg"></i>
                    </button>
                    <div id="export-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border z-50">
                        <div class="py-2">
                            <a href="{{ route('librarian.reports.print', 'monthly-summary') }}?year={{ now()->year }}&month={{ now()->month }}" target="_blank" class="w-full block text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-print mr-2"></i>Print Monthly Report
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- User Profile -->
                  <div class="flex items-center space-x-3">
                    <div class="text-right">
                        <p class="text-white font-medium">{{ $user->name ?? $user->firstname ?? 'Librarian' }}</p>
                        <p class="text-blue-100 text-sm">Librarian</p>
                    </div>
                    <div class="w-10 h-10 bg-white text-blue-700 border-2 border-white/60 rounded-full flex items-center justify-center">
                        <span class="text-blue-700 font-medium flex items-center justify-center w-full h-full">{{ substr($user->name ?? $user->firstname ?? 'L', 0, 1) }}</span>
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
                            <h2 class="font-semibold text-lg">{{ $user->firstname }} {{ $user->lastname }}</h2>
                            <p class="text-blue-200 text-xs font-medium">LIBRARIAN</p>
                        </div>
                    </div>
                    <div class="bg-gray-700/50 rounded-lg p-3">
                        <p class="text-gray-300 text-sm font-medium mb-1">Library Management System</p>
                        <p class="text-gray-400 text-xs">Administrative Access</p>
                    </div>
                </div>
                
                <nav class="space-y-2">
                    <a href="{{ route('librarian.dashboard') }}" class="sidebar-link active flex items-center space-x-3 text-white px-4 py-3 rounded-lg transition-all">
                        <i class="fas fa-chart-pie sidebar-icon"></i>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                    <a href="{{ route('librarian.books.index') }}" class="sidebar-link flex items-center space-x-3 text-gray-300 px-4 py-3 rounded-lg transition-all hover:text-white">
                        <i class="fas fa-book sidebar-icon"></i>
                        <span class="sidebar-text">Manage E-Resource</span>
                    </a>
                    <a href="{{ route('librarian.books.bulk.upload') }}" class="sidebar-link flex items-center space-x-3 {{ request()->routeIs('librarian.books.bulk.*') ? 'text-white active' : 'text-gray-300' }} px-4 py-3 rounded-lg transition-all hover:text-white">
                        <i class="fas fa-file-csv sidebar-icon"></i>
                        <span class="sidebar-text">Bulk Upload</span>
                    </a>
                    <a href="{{ route('librarian.students.index') }}" class="sidebar-link flex items-center space-x-3 text-gray-300 px-4 py-3 rounded-lg transition-all hover:text-white">
                        <i class="fas fa-users sidebar-icon"></i>
                        <span class="sidebar-text">Manage Students</span>
                    </a>
                     <a href="{{ route('librarian.loans.index') }}" class="sidebar-link flex items-center space-x-3 text-gray-300 px-4 py-3 rounded-lg transition-all hover:text-white">
                        <i class="fas fa-hand-holding sidebar-icon"></i>
                        <span class="sidebar-text">E-Resource Monitoring</span>
                    </a> 
                    <a href="{{ route('librarian.reports.index') }}" class="sidebar-link flex items-center space-x-3 text-gray-300 px-4 py-3 rounded-lg transition-all hover:text-white">
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
            <!-- Dashboard Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Library Dashboard</h1>
                <p class="text-gray-600 mt-2">Comprehensive overview of library operations and statistics</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="stat-card bg-white p-6 rounded-lg shadow-sm border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Total Books</p>
                            <p class="text-3xl font-bold text-blue-600" id="total-books-count">0</p>
                        </div>
                        <div class="w-14 h-14 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-book text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white p-6 rounded-lg shadow-sm border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Total Students</p>
                            <p class="text-3xl font-bold text-green-600" id="total-students-count">0</p>
                        </div>
                        <div class="w-14 h-14 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white p-6 rounded-lg shadow-sm border-l-4 border-orange-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Active Borrows</p>
                            <p class="text-3xl font-bold text-orange-600" id="active-borrows-count">0</p>
                        </div>
                        <div class="w-14 h-14 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-hand-holding text-orange-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white p-6 rounded-lg shadow-sm border-l-4 border-red-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Total Borrowed Books</p>
                            <p class="text-3xl font-bold text-red-600" id="total-loans-count">0</p>
                        </div>
                        <div class="w-14 h-14 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-bar text-red-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Primary Content Row -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
                <!-- Book Status Chart - Large -->
                <div class="lg:col-span-2 bg-white p-8 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">Books Distribution by Course</h3>
                            <p id="book-status-summary" class="text-sm text-gray-500 mt-1">Loading distribution data...</p>
                        </div>
                        <div class="flex space-x-2">
                            <button class="chart-toggle px-3 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" data-chart="pie" data-target="book-status-chart">
                                <i class="fas fa-chart-pie mr-1"></i> Pie
                            </button>
                            <button class="chart-toggle active px-3 py-2 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors" data-chart="bar" data-target="book-status-chart">
                                <i class="fas fa-chart-bar mr-1"></i> Bar
                            </button>
                        </div>
                    </div>
                    <div class="h-96">
                        <canvas id="book-status-chart"></canvas>
                    </div>
                </div>

                <!-- Right Column - Quick Actions & Today's Summary -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                        </div>
                    <div class="p-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <button onclick="window.location.href='{{ route('librarian.books.create') }}'" class="quick-action-btn bg-blue-600 text-white p-4 rounded-lg hover:bg-blue-700 transition-colors transform hover:scale-105 flex flex-col items-center space-y-2">
                                    <i class="fas fa-plus text-2xl"></i>
                                    <span class="text-sm font-medium">Add Book</span>
                                </button>
                                <button onclick="generateCustomReport()" class="quick-action-btn bg-purple-600 text-white p-4 rounded-lg hover:bg-purple-700 transition-colors transform hover:scale-105 flex flex-col items-center space-y-2">
                                    <i class="fas fa-chart-bar text-2xl"></i>
                                    <span class="text-sm font-medium">Generate Report</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Summary -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Today's Summary</h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-center today-summary-item">
                                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                                        <i class="fas fa-hand-holding text-blue-600"></i>
                                    </div>
                                    <p class="text-2xl font-bold text-blue-600" id="today-borrows">0</p>
                                    <p class="text-xs text-gray-500">Borrowed</p>
                                </div>
                                <div class="text-center today-summary-item">
                                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                                        <i class="fas fa-undo text-green-600"></i>
                                    </div>
                                    <p class="text-2xl font-bold text-green-600" id="today-returns">0</p>
                                    <p class="text-xs text-gray-500">Returned</p>
                                </div>
                                <div class="text-center today-summary-item">
                                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                                        <i class="fas fa-user-plus text-orange-600"></i>
                                    </div>
                                    <p class="text-2xl font-bold text-orange-600" id="today-registrations">0</p>
                                    <p class="text-xs text-gray-500">Registrations</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Trends Chart - Full Width -->
            <div class="mb-8">
                <div class="bg-white p-8 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-semibold text-gray-900">Monthly Borrowing Trends</h3>
                        <select id="trend-period" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="12">Last 12 Months</option>
                            <option value="6">Last 6 Months</option>
                            <option value="3">Last 3 Months</option>
                        </select>
                    </div>
                    <div class="h-80">
                        <canvas id="monthly-trends-chart"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white p-8 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">Students per Program</h3>
                            <p class="text-sm text-gray-500 mt-1">Registered student distribution by program</p>
                        </div>
                    </div>
                    <div class="h-80">
                        <canvas id="students-program-chart"></canvas>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">Student Gender Distribution</h3>
                            <p class="text-sm text-gray-500 mt-1">Registered student distribution by gender</p>
                        </div>
                    </div>
                    <div class="h-80">
                        <canvas id="gender-chart"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white p-8 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">Registered Students by Campus</h3>
                            <p class="text-sm text-gray-500 mt-1">Total registered students for each campus</p>
                        </div>
                    </div>
                    <div class="h-80">
                        <canvas id="campus-chart"></canvas>
                    </div>
                </div>
                <div class="bg-white p-8 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">Resource Type by Borrowing Activity</h3>
                            <p class="text-sm text-gray-500 mt-1">Borrowing counts grouped by resource types</p>
                        </div>
                    </div>
                    <div class="h-80">
                        <canvas id="categories-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Activities Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="flex items-center justify-between p-6 border-b border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-900">Recent Activities</h3>
                        <button id="refresh-activities" class="text-blue-600 hover:text-blue-700 text-sm font-medium px-4 py-2 rounded-lg bg-blue-50 hover:bg-blue-100 transition-colors">
                            <i class="fas fa-sync-alt mr-1"></i>Refresh
                        </button>
                    </div>
                    <div id="recent-activities" class="max-h-96 overflow-y-auto">
                        <div class="flex justify-center items-center py-12">
                            <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mr-3"></i>
                            <span class="text-gray-600">Loading activities...</span>
                        </div>
                    </div>
                </div>
                <!-- Most Borrowed Books (Top 10) -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="flex items-center justify-between p-6 border-b border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-900">Most Borrowed Books (Top 10)</h3>
                    </div>
                    <div id="most-borrowed-books" class="max-h-96 overflow-y-auto">
                        <div class="flex justify-center items-center py-12">
                            <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mr-3"></i>
                            <span class="text-gray-600">Loading books...</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Custom Report Modal -->
    <div id="customReportModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Generate Custom Report</h3>
                    <button onclick="closeCustomReportModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="customReportForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="report_type" class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                            <select id="report_type" name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="borrowing-statistics">Borrowing Statistics</option>
                                <option value="student-activity">Student Activity</option>
                                <option value="book-usage">Book Usage</option>
                                <option value="popular-books">Popular Books</option>
                                <option value="course-analysis">Course Analysis</option>
                                <option value="monthly-summary">Monthly Summary</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="date" id="date_from" name="date_from" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="From">
                                <input type="date" id="date_to" name="date_to" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="To">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="course_filter" class="block text-sm font-medium text-gray-700 mb-2">Program Filter (Optional)</label>
                        <select id="course_filter" name="course" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Programs</option>
                            <option value="BSE">BSE</option>
                            <option value="BSHM">BSHM</option>
                            <option value="BSIT">BSIT</option>
                            <option value="BSN">BSN</option>
                            <option value="BSTM">BSTM</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeCustomReportModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Generate Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Report Display Modal -->
    <div id="reportDisplayModal" class="hidden fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-0 border w-11/12 md:w-4/5 lg:w-3/4 shadow-2xl rounded-lg bg-white max-h-screen overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-4 flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-chart-bar text-xl mr-3"></i>
                    <div>
                        <h3 class="text-lg font-bold" id="reportTitle">Generated Report</h3>
                        <p class="text-sm text-blue-100" id="reportSubtitle">Custom report with your selected parameters</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="printReportContent()" class="bg-white/20 hover:bg-white/30 text-white px-3 py-2 rounded-lg text-sm transition-colors">
                        <i class="fas fa-print mr-1"></i> Print
                    </button>
                    <button onclick="downloadReport()" class="bg-white/20 hover:bg-white/30 text-white px-3 py-2 rounded-lg text-sm transition-colors">
                        <i class="fas fa-download mr-1"></i> Download
                    </button>
                    <button onclick="closeReportModal()" class="bg-white/20 hover:bg-white/30 text-white px-3 py-2 rounded-lg text-sm transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="p-6 overflow-y-auto max-h-[calc(100vh-80px)]" id="reportContent">
                <div class="flex justify-center items-center py-12">
                    <i class="fas fa-spinner fa-spin text-3xl text-blue-500 mr-3"></i>
                    <span class="text-gray-600">Generating report...</span>
                </div>
            </div>
        </div>
    </div>


    <style>
        /* Chart toggle buttons */
        .chart-toggle.active {
            background-color: #dbeafe !important;
            color: #2563eb !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        
        /* Quick action buttons */
        .quick-action-btn {
            transition: all 0.3s ease;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        /* Stat cards */
        .stat-card {
            transition: all 0.3s ease;
            border-top: 4px solid transparent;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        }
        
        /* Enhanced containers */
        .bg-white {
            backdrop-filter: blur(10px);
        }
        
        /* Chart containers */
        canvas {
            border-radius: 12px;
        }
        
        /* Smooth loading animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        .stat-card, .bg-white {
            animation: fadeIn 0.6s ease-out;
        }
        
        .fa-spinner {
            animation: pulse 1.5s infinite;
        }
        
        /* Custom scrollbars */
        #recent-activities::-webkit-scrollbar,
        #most-borrowed-books::-webkit-scrollbar {
            width: 6px;
        }
        
        #recent-activities::-webkit-scrollbar-track,
        #most-borrowed-books::-webkit-scrollbar-track {
            background: #f8fafc;
            border-radius: 3px;
        }
        
        #recent-activities::-webkit-scrollbar-thumb,
        #most-borrowed-books::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        
        #recent-activities::-webkit-scrollbar-thumb:hover,
        #most-borrowed-books::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Today's Summary enhancements */
        .today-summary-item {
            transition: all 0.3s ease;
        }
        
        .today-summary-item:hover {
            transform: scale(1.05);
        }
        
        /* Responsive grid improvements */
        @media (max-width: 768px) {
            .quick-action-btn {
                padding: 12px;
            }
            
            .stat-card {
                margin-bottom: 1rem;
            }
        }
        
        /* Dashboard header improvements */
        .dashboard-header {
            background-color: #1f2937; /* solid gray-800 */
            color: white;
        }
    </style>
    
    <script src="{{ asset('js/librarian-dashboard.js') }}"></script>
</body>
</html>
