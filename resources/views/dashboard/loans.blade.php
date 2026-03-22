<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Borrowed Books - Knowly</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('css/student-dashboard.css') }}" rel="stylesheet">
    <link href="{{ asset('css/loans.css') }}" rel="stylesheet">
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
                <!-- Quick Search Bar -->
                <form action="{{ route('student.books') }}" method="GET" class="quick-search-form relative">
                    <input type="text"
                           id="header-search"
                           name="search"
                           placeholder="Quick search books..."
                           class="bg-white text-gray-800 placeholder-gray-600 border border-gray-300 rounded-full px-4 py-2 pr-10 w-48 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm">
                    <button type="submit" id="header-search-btn" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-600 hover:text-gray-800 transition-colors">
                        <i class="fas fa-search"></i>
                    </button>
                    <div id="header-search-results" class="quick-search-results hidden absolute right-0 mt-2 w-96 max-h-96 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-xl z-[60]"></div>
                </form>
                
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
                    <a href="{{ route('student.loans') }}" class="sidebar-link active flex items-center space-x-3 text-white px-4 py-3 rounded-lg transition-all">
                        <i class="fas fa-hand-holding sidebar-icon"></i>
                        <span class="sidebar-text">Borrowed E-Resource</span>
                    </a>
                    <a href="{{ route('student.history') }}" class="sidebar-link flex items-center space-x-3 text-gray-300 px-4 py-3 rounded-lg transition-all hover:text-white">
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
                <h1 class="text-3xl font-bold text-gray-900">My Borrowed Books</h1>
                <p class="text-gray-600 mt-2">Manage your current book loans and track due dates</p>
            </div>

            <!-- Quick Stats removed as loans auto-return after 5 days -->

            <!-- Loans Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-900" id="loans-count">Loading loans...</h2>
                <div class="flex w-full sm:w-auto space-x-3">
                    <!-- Combined Filter & Sort Dropdown -->
                    <div class="relative w-full sm:w-auto">
                        <button id="filter-sort-btn" class="w-full sm:w-auto px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center">
                            <i class="fas fa-sliders-h mr-2"></i>Filter & Sort
                            <i class="fas fa-chevron-down ml-2 text-xs"></i>
                        </button>
                        <div id="filter-sort-dropdown" class="absolute right-0 mt-2 w-full sm:w-80 bg-white border border-gray-200 rounded-lg shadow-lg z-10 hidden">
                            <!-- Tab Navigation -->
                            <div class="border-b border-gray-200">
                                <div class="flex">
                                    <button class="filter-sort-tab active flex-1 py-3 px-4 text-sm font-medium text-blue-600 border-b-2 border-blue-500 bg-blue-50" data-tab="filter">
                                        <i class="fas fa-filter mr-2"></i>Filter
                                    </button>
                                    <button class="filter-sort-tab flex-1 py-3 px-4 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300" data-tab="sort">
                                        <i class="fas fa-sort mr-2"></i>Sort
                                    </button>
                                </div>
                            </div>

                            <!-- Filter Tab Content -->
                            <div id="filter-tab-content" class="filter-sort-content">
                                <form id="filter-form" class="p-4">
                                    <div class="mb-4">
                                        <label for="status-filter" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                        <select id="status-filter" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                                            <option value="">All Status</option>
                                            <option value="active">Active</option>
                                            <option value="due-soon">Due Soon</option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label for="category-filter" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                        <select id="category-filter" name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                                            <option value="">All Categories</option>
                                            <!-- Categories will be populated by JS -->
                                        </select>
                                    </div>
                                    <div class="flex justify-between">
                                        <button type="button" id="apply-filters" class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">Apply</button>
                                        <button type="button" id="clear-filters" class="px-4 py-2 bg-gray-300 text-gray-700 text-sm rounded hover:bg-gray-400">Clear</button>
                                    </div>
                                </form>
                            </div>

                            <!-- Sort Tab Content -->
                            <div id="sort-tab-content" class="filter-sort-content hidden">
                                <div class="p-4">
                                    <label for="sort-select" class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                                    <select id="sort-select" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                                        <option value="due-date-asc">Due Date (Earliest First)</option>
                                        <option value="due-date-desc">Due Date (Latest First)</option>
                                        <option value="borrowed-date-desc">Borrowed Date (Newest)</option>
                                        <option value="borrowed-date-asc">Borrowed Date (Oldest)</option>
                                        <option value="title-asc">Title (A-Z)</option>
                                        <option value="title-desc">Title (Z-A)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading Indicator -->
            <div id="loading-indicator" class="text-center py-12">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
                <p class="text-gray-600 text-lg">Loading your borrowed books...</p>
            </div>

            <!-- Loans Container -->
            <div id="loans-container" class="hidden">
                <div id="loans-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 animate-fade-in-up">
                    <!-- Loan cards will be dynamically inserted here -->
                </div>
            </div>

            <!-- Empty State -->
            <div id="empty-state" class="hidden text-center py-12">
                <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-book-open text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Active Loans</h3>
                <p class="text-gray-600 mb-6">You haven't borrowed any books yet. Start exploring our collection!</p>
                <a href="{{ route('student.books') }}" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    <i class="fas fa-search mr-2"></i>Browse Books
                </a>
            </div>
        </div>
    </div>

    <!-- Renew Confirmation Modal -->
    <div id="renew-modal" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center p-4 z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 mx-auto">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-refresh text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Renew Book</h3>
                <p class="text-gray-600 mb-6" id="renew-message">Are you sure you want to renew this book?</p>
                <div class="flex justify-center gap-3">
                    <button id="cancel-renew" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                    <button id="confirm-renew" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Yes, Renew
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Return Confirmation Popup (same style as Borrow) -->
    <div id="returnPopup" class="fixed inset-0 hidden items-center justify-center z-[9999] bg-black/40 backdrop-blur-[2px]">
        <div id="returnPopupCard" class="bg-white rounded-xl border shadow-2xl max-w-md w-[90%] p-6 transform transition-all duration-200 scale-95 opacity-0 mx-auto">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-undo text-green-600"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="text-lg font-semibold text-gray-900">Return Book</h3>
                    <p class="text-sm text-gray-500">Are you sure you want to return this book now?</p>
                    <div class="bg-gray-50 rounded-lg p-3 mt-3">
                        <p class="text-sm text-gray-700 truncate" id="returnPopupTitle"></p>
                    </div>
                </div>
            </div>
            <div class="flex justify-center gap-3 mt-5">
                <button id="returnPopupCancel" class="px-4 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Cancel</button>
                <button id="returnPopupConfirm" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                    <span id="returnPopupConfirmText">Confirm</span>
                </button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/student-dashboard.js') }}"></script>
    <script src="{{ asset('js/loans.js') }}"></script>
</body>
</html>
