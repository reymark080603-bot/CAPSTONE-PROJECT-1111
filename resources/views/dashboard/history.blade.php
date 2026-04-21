<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>History - Knowly Library</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('css/student-dashboard.css') }}?v={{ filemtime(public_path('css/student-dashboard.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/history.css') }}?v={{ filemtime(public_path('css/history.css')) }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="header sidebar-expanded bg-green-600 shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 w-full">
            <div class="flex items-center space-x-4 flex-shrink-0">
                <!-- Sidebar Toggle Button -->
                <button id="sidebar-toggle" class="sidebar-toggle text-white hover:bg-white/10 p-2 rounded-lg transition-all">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                
                <div class="flex items-center">
                    <i class="fas fa-book-open text-white text-2xl mr-3"></i>
                    <h1 class="text-white text-2xl font-bold">Knowly</h1>
                </div>
            </div>
            
            <div class="flex items-center space-x-4 flex-shrink-0">
                
                <!-- User Profile -->
                <div class="flex items-center space-x-3">
                    <div class="relative group" data-profile-wrapper>
                        <button type="button" data-profile-toggle class="w-10 h-10 bg-white text-green-700 border-2 border-white/60 rounded-full flex items-center justify-center cursor-pointer shadow-sm hover:shadow-md transition-all">
                            <span class="font-semibold text-sm text-green-700">{{ substr($user->firstname ?? '', 0, 1) }}{{ substr($user->lastname ?? '', 0, 1) }}</span>
                        </button>
                        <!-- Dropdown tooltip -->
                        <div data-profile-menu class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg p-3 hidden z-50">
                            <div class="text-sm text-gray-700">
                                <p class="font-semibold">{{ $user->firstname ?? 'User' }} {{ $user->lastname ?? '' }}</p>
                                <p class="text-gray-500">{{ $user->email ?? '' }}</p>
                                <hr class="my-2">
                                <form action="{{ route('logout') }}" method="POST" class="block">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-2 py-1 hover:bg-gray-100 rounded text-red-600">Logout</button>
                                </form>
                            </div>
                        </div>
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
                <div class="sidebar-welcome text-white mb-6">
                    <h2 class="font-semibold text-lg">Knowly</h2>
                    <p class="text-gray-400 text-sm">Welcome, {{ $user->firstname }}</p>
                </div>
                
                <nav class="space-y-2">
                    <a href="{{ route('dashboard') }}" class="sidebar-link flex items-center space-x-3 text-gray-300 px-4 py-3 rounded-lg transition-all hover:text-white">
                        <i class="fas fa-home sidebar-icon"></i>
                        <span class="sidebar-text">Home</span>
                    </a>
                    <a href="{{ route('student.books') }}" class="sidebar-link flex items-center space-x-3 text-gray-300 px-4 py-3 rounded-lg transition-all hover:text-white">
                        <i class="fas fa-book sidebar-icon"></i>
                        <span class="sidebar-text">Library</span>
                    </a>
                    <a href="{{ route('student.loans') }}" class="sidebar-link flex items-center space-x-3 text-gray-300 px-4 py-3 rounded-lg transition-all hover:text-white">
                        <i class="fas fa-hand-holding sidebar-icon"></i>
                        <span class="sidebar-text">Borrowed E-Resource</span>
                    </a>
                    <a href="{{ route('student.history') }}" class="sidebar-link active flex items-center space-x-3 text-white px-4 py-3 rounded-lg transition-all">
                        <i class="fas fa-history sidebar-icon"></i>
                        <span class="sidebar-text">History</span>
                    </a>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="sidebar-link flex items-center space-x-3 text-gray-300 px-4 py-3 rounded-lg transition-all hover:text-white w-full text-left">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="sidebar-text">Logout</span>
                        </button>
                    </form>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content p-4 sm:p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Borrowing History</h1>
            </div>
            <!-- Filter Controls -->
            <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-6 relative">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="history-search-wrap flex-1 min-w-[220px] sm:min-w-[280px]">
                        <div class="relative">
                            <input type="text" 
                                   id="search-history" 
                                   placeholder="Search by book title or author..." 
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>

                    <div class="history-filter-wrap relative w-full sm:w-auto">
                        <button id="history-filter-btn" class="history-filter-btn flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium shadow-sm w-full sm:w-auto">
                            <i class="fas fa-filter"></i>
                            <span>Filters</span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>

                        <div id="history-filter-dropdown" class="absolute right-0 mt-2 w-72 sm:w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50 hidden">
                            <div class="p-4 space-y-4">
                                <div>
                                    <label for="date-from" class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                                    <input type="date" 
                                           id="date-from" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="date-to" class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                                    <input type="date" 
                                           id="date-to" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="status-filter" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <select id="status-filter" class="w-full border border-gray-300 rounded-lg py-2 pl-3 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">All Status</option>
                                        <option value="borrowed">Currently Borrowed</option>
                                        <option value="returned">Returned</option>
                                    </select>
                                </div>

                                <div class="flex gap-2 pt-2 border-t border-gray-200">
                                    <button id="apply-history-filters" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium text-sm">
                                        Apply Filters
                                    </button>
                                    <button id="clear-filters" class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors font-medium text-sm">
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <div id="history-active-filters" class="flex flex-wrap gap-2">
                            <!-- Active filter tags will be inserted here -->
                        </div>
                    </div>
                </div>
            </div>


            <!-- History Table -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">Recent History</h3>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500" id="results-count">Ready</span>
                            <button id="delete-history-btn" class="hidden px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition-colors">
                                <i class="fas fa-trash mr-1"></i> Delete History
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Loading Indicator (Hidden by default) -->
                <div id="loading-indicator" class="hidden p-8 text-center">
                    <div class="animate-pulse">
                        <div class="h-4 bg-gray-200 rounded w-3/4 mx-auto mb-4"></div>
                        <div class="h-4 bg-gray-200 rounded w-1/2 mx-auto mb-4"></div>
                        <div class="h-4 bg-gray-200 rounded w-2/3 mx-auto"></div>
                    </div>
                </div>

                <!-- History Table Content -->
                <div id="history-table-container" class="hidden">
                    <div class="history-table-wrapper overflow-x-auto">
                        <table class="history-table w-full min-w-[720px]">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="history-tbody" class="bg-white divide-y divide-gray-200">
                                <!-- Dynamic content will be inserted here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Empty State -->
                <div id="empty-state" class="p-8 text-center">
                    <div class="empty-state-icon text-6xl text-gray-300 mb-4">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No History Found</h3>
                    <p class="text-gray-600 mb-4">You haven't borrowed any books yet. Start exploring our library!</p>
                    <a href="{{ route('student.books') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-book mr-2"></i>Browse Books
                    </a>
                </div>

                <!-- Pagination -->
                <div id="pagination-container" class="px-6 py-4 border-t border-gray-200">
                    <!-- Pagination controls will be inserted here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Book Detail Modal -->
    <div id="book-detail-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
        <div class="modal-content bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Modal content will be dynamically inserted -->
        </div>
    </div>

    <!-- Renew Confirmation Modal -->
    <div id="renew-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="text-center">
                <div class="w-12 h-12 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-refresh text-blue-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Renew Book</h3>
                <p class="text-gray-600 mb-6" id="renew-message">Are you sure you want to renew this book?</p>
                <div class="flex gap-3">
                    <button id="confirm-renew" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Yes, Renew
                    </button>
                    <button id="cancel-renew" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Return Confirmation Modal -->
    <div id="return-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="text-center">
                <div class="w-12 h-12 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-undo text-green-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Return Book</h3>
                <p class="text-gray-600 mb-6" id="return-message">Are you sure you want to return this book?</p>
                <div class="flex gap-3">
                    <button id="confirm-return" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        Yes, Return
                    </button>
                    <button id="cancel-return" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    window.historyPageRoutes = {
        historyApi: @json(route('student.history.api')),
        historyClear: @json(route('student.history.clear')),
        historyExport: @json(route('student.history.export')),
        booksBase: @json(url('student/books')),
        renewBase: @json(url('student/borrow-records')),
    };
    </script>
    <script src="{{ asset('js/student-dashboard.js') }}?v={{ filemtime(public_path('js/student-dashboard.js')) }}"></script>
    <script src="{{ asset('js/history.js') }}?v={{ filemtime(public_path('js/history.js')) }}"></script>
</body>
</html>
