<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Browse Books - Knowly</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/student-dashboard.css') }}?v={{ filemtime(public_path('css/student-dashboard.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/books.css') }}?v={{ filemtime(public_path('css/books.css')) }}" rel="stylesheet">
    @vite('resources/css/components/books.css')
</head>
<body class="bg-gray-50 min-h-screen">
    @php
        $pageTitle = match($resourceType ?? null) {
            'e_journal' => 'Browse E-Journals',
            'thesis' => 'Browse Theses',
            'book' => 'Browse Books',
            default => match($scope ?? null) {
                'recommended' => 'Browse Recommended Resources',
                'recent' => 'Browse Recently Added Resources',
                default => 'Browse Resources',
            },
        };

        $pageSubtitle = match($resourceType ?? null) {
            'e_journal' => 'Explore all academic e-journals in the library collection',
            'thesis' => 'Explore all thesis and capstone references in the library collection',
            'book' => 'Explore all books in the library collection',
            default => match($scope ?? null) {
                'recommended' => 'Explore all resources recommended for your program',
                'recent' => 'Explore the most recently added e-resources in the library collection',
                default => 'Discover books, e-journals, and theses across the full library collection',
            },
        };
    @endphp
    <!-- Header -->
    <div class="header sidebar-expanded bg-green-600 shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 w-full">
            <div class="flex items-center space-x-4 flex-shrink-0">
                <!-- Sidebar Toggle Button -->
                <button id="sidebar-toggle" class="sidebar-toggle text-white hover:bg-white/10 p-2 rounded-lg transition-all">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                
                <a href="{{ route('dashboard') }}" class="flex items-center">
                    <img src="{{ asset('images/jhcsclibrary-logo.png') }}" alt="Knowly logo" class="w-8 h-8 rounded-full object-cover mr-3" />
                    <h1 class="text-white text-2xl font-bold">Knowly</h1>
                </a>
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
        <div class="sidebar bg-gray-800 text-white min-h-screen w-64 fixed md:relative transition-all z-40">
            <div class="p-4">
                <div class="sidebar-welcome mb-6">
                    <a href="{{ route('dashboard') }}" class="font-semibold text-lg inline-block">Knowly</a>
                    <p class="text-gray-400 text-sm">Welcome, {{ $user->firstname ?? 'Student' }}</p>
                </div>
                
                <nav class="space-y-2">
                    <a href="{{ route('dashboard') }}" class="sidebar-link flex items-center space-x-3 text-gray-300 px-4 py-3 rounded-lg transition-all hover:text-white hover:bg-gray-700">
                        <i class="fas fa-home"></i>
                        <span class="sidebar-text">Home</span>
                    </a>
                    <a href="{{ route('student.books') }}" class="sidebar-link active flex items-center space-x-3 text-white px-4 py-3 rounded-lg transition-all bg-green-600">
                        <i class="fas fa-book"></i>
                        <span class="sidebar-text">Library</span>
                    </a>
                    <a href="{{ route('student.loans') }}" class="sidebar-link flex items-center space-x-3 text-gray-300 px-4 py-3 rounded-lg transition-all hover:text-white hover:bg-gray-700">
                        <i class="fas fa-hand-holding"></i>
                        <span class="sidebar-text">Borrowed E-Resource</span>
                    </a>
                    <a href="{{ route('student.history') }}" class="sidebar-link flex items-center space-x-3 text-gray-300 px-4 py-3 rounded-lg transition-all hover:text-white hover:bg-gray-700">
                        <i class="fas fa-history"></i>
                        <span class="sidebar-text">History</span>
                    </a>
                    <form action="{{ route('logout') }}" method="POST" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full sidebar-link flex items-center space-x-3 text-gray-300 px-4 py-3 rounded-lg transition-all hover:text-white hover:bg-red-700 text-left">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="sidebar-text">Logout</span>
                        </button>
                    </form>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-1 p-3 sm:p-4 md:p-6 transition-all duration-300">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">{{ $pageTitle }}</h1>
                
                @if(session('error'))
                    <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-red-500 mr-2 mt-0.5"></i>
                            <span class="text-red-700">{{ session('error') }}</span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Filter Controls -->
            <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-4 sm:mb-6 relative">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <!-- Search Bar -->
                    <div class="library-search-wrap flex-1 min-w-[220px] sm:min-w-[280px]">
                        <div class="relative">
                            <label for="search-books" class="sr-only">Search books by title, author</label>
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400" aria-hidden="true"></i>
                                <span class="sr-only">Search icon</span>
                            </div>
                            <input type="text" 
                                id="search-books"
                                name="search"
                                placeholder="Search books by title, author"
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                value="{{ request('search') }}"
                                aria-label="Search books by title, author">
                        </div>
                    </div>
                    
                    <!-- Unified Filter Dropdown -->
                    <div class="library-filter-wrap relative">
                        <button id="filter-dropdown-btn" class="library-filter-btn flex items-center gap-2 px-3 sm:px-4 py-2 text-sm sm:text-base bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium shadow-sm">
                            <i class="fas fa-filter"></i>
                            <span>Filters</span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        
                        <!-- Filter Dropdown Menu -->
                        <div id="filter-dropdown" class="absolute right-0 mt-2 w-64 sm:w-72 md:w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50 hidden">
                            <div class="p-3 sm:p-4 space-y-3 sm:space-y-4">
                                <!-- Type Filter -->
                                <div>
                                    <label for="filter-category" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Type</label>
                                    <select id="filter-category" class="w-full border border-gray-300 rounded-lg py-1.5 sm:py-2 pl-3 pr-8 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">All Types</option>
                                        <option value="book">Books</option>
                                        <option value="e_journal">E-Journal</option>
                                        <option value="thesis">E-Thesis</option>
                                    </select>
                                </div>
                                
                                <!-- Program Filter -->
                                <div>
                                    <label for="filter-program" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Program</label>
                                    <select id="filter-program" class="w-full border border-gray-300 rounded-lg py-1.5 sm:py-2 pl-3 pr-8 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">All Programs</option>
                                        <option value="BSE">BSE</option>
                                        <option value="BSHM">BSHM</option>
                                        <option value="BSIT">BSIT</option>
                                        <option value="BSN">BSN</option>
                                        <option value="BSTM">BSTM</option>
                                        <option value="All Program">All Program</option>
                                    </select>
                                </div>
                                
                                <!-- Year Filter -->
                                <div>
                                    <label for="filter-year" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Year Level</label>
                                    <select id="filter-year" class="w-full border border-gray-300 rounded-lg py-1.5 sm:py-2 pl-3 pr-8 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">All Years</option>
                                        <option value="1st Year">1st Year</option>
                                        <option value="2nd Year">2nd Year</option>
                                        <option value="3rd Year">3rd Year</option>
                                        <option value="4th Year">4th Year</option>
                                        <option value="All">All</option>
                                    </select>
                                </div>
                                
                                <!-- Sort Filter -->
                                <div>
                                    <label for="filter-sort" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Sort By</label>
                                    <select id="filter-sort" class="w-full border border-gray-300 rounded-lg py-1.5 sm:py-2 pl-3 pr-8 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="title-asc">Title (A-Z)</option>
                                        <option value="title-desc">Title (Z-A)</option>
                                        <option value="author-asc">Author (A-Z)</option>
                                        <option value="author-desc">Author (Z-A)</option>
                                        <option value="year-newest">Year (Newest)</option>
                                        <option value="year-oldest">Year (Oldest)</option>
                                        <option value="recently-added">Recently Added</option>
                                    </select>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="flex gap-2 pt-2 border-t border-gray-200">
                                    <button id="apply-filters" class="flex-1 px-3 sm:px-4 py-1.5 sm:py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium text-xs sm:text-sm">
                                        Apply Filters
                                    </button>
                                    <button id="reset-filters" class="flex-1 px-3 sm:px-4 py-1.5 sm:py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors font-medium text-xs sm:text-sm">
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Active Filters Display -->
                        <div id="active-filters" class="flex flex-wrap gap-2">
                            <!-- Active filter tags will be inserted here -->
                        </div>
                    </div>
                </div>
                
                </div>

            <!-- Recommended Books Section (Based on Course & Year) -->
            @if(($scope ?? null) !== 'recommended' && !isset($selectedResources))
            <div id="recommended-section" class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Recommended for You</h2>
                        <p class="text-sm text-gray-600">Resources matched to {{ $user->course_name ?? 'your program' }}</p>
                    </div>
                    <button id="show-all-recommended" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                        View All →
                    </button>
                </div>
                
                <!-- Recommended Books Grid - Mobile-First Responsive Design -->
                <div id="recommended-books" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 sm:gap-3">
                    @if(isset($recommendedBooks) && $recommendedBooks && $recommendedBooks->count() > 0)
                        @foreach($recommendedBooks as $book)
                        <div class="book-card flex-shrink-0 w-full"
                             data-book-id="{{ $book->id }}"
                             data-book-title="{{ $book->title }}"
                             data-book-author="{{ $book->author ?? 'Unknown Author' }}"
                             data-book-category="{{ $book->category ?? 'General' }}"
                             data-book-resource-type="{{ $book->resource_type ?? 'book' }}"
                             data-book-course="{{ $book->course ?? '' }}"
                             data-book-program="{{ $book->program ?? '' }}"
                             data-book-year-level="{{ $book->year_level ?? '' }}"
                             data-book-published-year="{{ $book->published_year ?? '' }}"
                             data-book-description="{{ $book->description ?? '' }}"
                             data-book-cover="{{ $book->display_cover_url ?? '' }}">
                            <div class="relative group">
                                <div class="book-cover relative bg-gray-100 rounded-lg shadow-md overflow-hidden h-56 sm:h-60 md:h-64 hover:shadow-xl transition-all duration-300 transform group-hover:scale-105">
                                    <span class="absolute top-2 left-2 z-10 px-2 py-1 rounded-full bg-white/90 text-[10px] font-semibold text-gray-700 uppercase tracking-wide">{{ strtoupper(str_replace('_', ' ', $book->resource_type ?: 'book')) }}</span>
                                    @if($book->display_cover_url)
                                        <img src="{{ $book->display_cover_url }}" alt="{{ $book->title }} Cover" class="w-full h-full object-cover rounded-lg book-cover-img" loading="lazy">
                                    @else
                                        <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-book text-gray-400 text-4xl"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="mt-3 text-center">
                                    <h3 class="text-sm font-semibold text-gray-900 line-clamp-2">{{ $book->title }}</h3>
                                    <p class="text-xs text-gray-600 mb-2 line-clamp-1">{{ $book->author ?? 'Unknown Author' }}</p>
                                    <p class="text-[11px] text-gray-500 mb-2 uppercase tracking-wide line-clamp-1">{{ $book->course ?: ($book->program ?: '') }}</p>
                                    <div class="flex gap-1">
                                        <a href="{{ route('student.books.show', $book) }}" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-2 rounded text-xs text-center transition-colors">View</a>
                                        <button class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-2 rounded text-xs transition-colors btn-borrow-quick" data-book-id="{{ $book->id }}" data-book-title="{{ $book->title }}">Borrow</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <!-- No books found -->
                        <div class="col-span-full text-center py-12">
                            <div class="text-gray-300 text-6xl mb-4">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-700 mb-2">No recommended resources available</h3>
                            <p class="text-gray-500">Add more course-related resources to show suggestions here.</p>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            @if(isset($selectedResources) && $selectedResources)
            @php
                $selectedLabel = match(true) {
                    ($scope ?? null) === 'recommended' => 'All Recommended Resources',
                    ($scope ?? null) === 'recent' => 'Recently Added E-Resources',
                    ($resourceType ?? null) === 'e_journal' => 'All E-Journals',
                    ($resourceType ?? null) === 'thesis' => 'All Theses',
                    default => 'All Books',
                };
            @endphp
            <div class="mb-10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">{{ $selectedLabel }}</h2>
                        <p class="text-sm text-gray-600">
                            {{ ($scope ?? null) === 'recent'
                                ? 'Showing the latest e-resources added to the library'
                                : 'Showing all available ' . strtolower(str_replace('All ', '', $selectedLabel)) }}
                        </p>
                    </div>
                    <a href="{{ route('student.books') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Back to Overview</a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 sm:gap-3">
                    @forelse($selectedResources as $resource)
                        <div class="book-card flex-shrink-0 w-full {{ ($resource->resource_type ?? null) === 'e_journal' ? 'ejournal-card' : '' }}">
                            <div class="relative group">
                                <div class="book-cover relative bg-gray-100 rounded-lg shadow-md overflow-hidden h-56 sm:h-60 md:h-64 hover:shadow-xl transition-all duration-300 transform group-hover:scale-105">
                                    <span class="absolute top-2 left-2 z-10 px-2 py-1 rounded-full bg-white/90 text-[10px] font-semibold text-gray-700 uppercase tracking-wide">{{ strtoupper(str_replace('_', ' ', $resource->resource_type ?: 'book')) }}</span>
                                    <img src="{{ $resource->display_cover_url }}" alt="{{ $resource->title }} Cover" class="w-full h-full object-cover rounded-lg book-cover-img" loading="lazy">
                                </div>
                                <div class="mt-3 text-center">
                                    <h3 class="text-sm font-semibold text-gray-900 line-clamp-2">{{ $resource->title }}</h3>
                                    <p class="text-xs text-gray-600 mb-2 line-clamp-1">{{ $resource->author ?? 'Unknown Author' }}</p>
                                    <p class="text-[11px] text-gray-500 mb-2 uppercase tracking-wide line-clamp-1">{{ $resource->course ?: ($resource->program ?: '') }}</p>
                                    <div class="flex gap-1">
                                        <a href="{{ route('student.books.show', $resource) }}" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-2 rounded text-xs text-center transition-colors">View</a>
                                        <button class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-2 rounded text-xs transition-colors btn-borrow-quick" data-book-id="{{ $resource->id }}" data-book-title="{{ $resource->title }}">Borrow</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-12">
                            <div class="text-gray-300 text-6xl mb-4"><i class="fas fa-folder-open"></i></div>
                            <h3 class="text-xl font-semibold text-gray-700 mb-2">No resources found</h3>
                            <p class="text-gray-500">There are no resources in this section yet.</p>
                        </div>
                    @endforelse
                </div>

                <div class="mt-6">
                    {{ $selectedResources->links() }}
                </div>
            </div>
            @endif

            @if(!(isset($selectedResources) && $selectedResources))
            <div id="ejournal-section" class="mt-10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">E-Journals</h2>
                        <p class="text-sm text-gray-600">All e-journals in the library collection</p>
                    </div>
                    <a href="{{ route('student.books', ['resource_type' => 'e_journal']) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">See All</a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 sm:gap-3">
                    @forelse($eJournalResources as $resource)
                        <div class="book-card flex-shrink-0 w-full ejournal-card"
                             data-book-id="{{ $resource->id }}"
                             data-book-title="{{ $resource->title }}"
                             data-book-author="{{ $resource->author ?? 'Unknown Author' }}"
                             data-book-category="{{ $resource->category ?? 'General' }}"
                             data-book-resource-type="{{ $resource->resource_type ?? 'e_journal' }}"
                             data-book-course="{{ $resource->course ?? '' }}"
                             data-book-program="{{ $resource->program ?? '' }}"
                             data-book-year-level="{{ $resource->year_level ?? '' }}"
                             data-book-published-year="{{ $resource->published_year ?? '' }}"
                             data-book-description="{{ $resource->description ?? '' }}"
                             data-book-cover="{{ $resource->display_cover_url ?? '' }}">
                            <div class="relative group">
                                <div class="book-cover relative bg-gray-100 rounded-lg shadow-md overflow-hidden h-56 sm:h-60 md:h-64 hover:shadow-xl transition-all duration-300 transform group-hover:scale-105">
                                    <span class="absolute top-2 left-2 z-10 px-2 py-1 rounded-full bg-white/90 text-[10px] font-semibold text-gray-700 uppercase tracking-wide">E JOURNAL</span>
                                    <img src="{{ $resource->display_cover_url }}" alt="{{ $resource->title }} Cover" class="w-full h-full object-cover rounded-lg book-cover-img" loading="lazy">
                                </div>
                                <div class="mt-3 text-center">
                                    <h3 class="text-sm font-semibold text-gray-900 line-clamp-2">{{ $resource->title }}</h3>
                                    <p class="text-xs text-gray-600 mb-2 line-clamp-1">{{ $resource->author ?? 'Unknown Author' }}</p>
                                    <p class="text-[11px] text-gray-500 mb-2 uppercase tracking-wide line-clamp-1">{{ $resource->course ?: ($resource->program ?: '') }}</p>
                                    <div class="flex gap-1">
                                        <a href="{{ route('student.books.show', $resource) }}" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-2 rounded text-xs text-center transition-colors">View</a>
                                        <button class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-2 rounded text-xs transition-colors btn-borrow-quick" data-book-id="{{ $resource->id }}" data-book-title="{{ $resource->title }}">Borrow</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-12">
                            <div class="text-gray-300 text-6xl mb-4"><i class="fas fa-newspaper"></i></div>
                            <h3 class="text-xl font-semibold text-gray-700 mb-2">No e-journals available</h3>
                            <p class="text-gray-500">Check back later for journal resources.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div id="thesis-section" class="mt-10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Theses</h2>
                        <p class="text-sm text-gray-600">All e-theses in the library collection</p>
                    </div>
                    <a href="{{ route('student.books', ['resource_type' => 'thesis']) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">See All</a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 sm:gap-3">
                    @forelse($thesisResources as $resource)
                        <div class="book-card flex-shrink-0 w-full"
                             data-book-id="{{ $resource->id }}"
                             data-book-title="{{ $resource->title }}"
                             data-book-author="{{ $resource->author ?? 'Unknown Author' }}"
                             data-book-category="{{ $resource->category ?? 'General' }}"
                             data-book-resource-type="{{ $resource->resource_type ?? 'thesis' }}"
                             data-book-course="{{ $resource->course ?? '' }}"
                             data-book-program="{{ $resource->program ?? '' }}"
                             data-book-year-level="{{ $resource->year_level ?? '' }}"
                             data-book-published-year="{{ $resource->published_year ?? '' }}"
                             data-book-description="{{ $resource->description ?? '' }}"
                             data-book-cover="{{ $resource->display_cover_url ?? '' }}">
                            <div class="relative group">
                                <div class="book-cover relative bg-gray-100 rounded-lg shadow-md overflow-hidden h-56 sm:h-60 md:h-64 hover:shadow-xl transition-all duration-300 transform group-hover:scale-105">
                                    <span class="absolute top-2 left-2 z-10 px-2 py-1 rounded-full bg-white/90 text-[10px] font-semibold text-gray-700 uppercase tracking-wide">THESIS</span>
                                    <img src="{{ $resource->display_cover_url }}" alt="{{ $resource->title }} Cover" class="w-full h-full object-cover rounded-lg book-cover-img" loading="lazy">
                                </div>
                                <div class="mt-3 text-center">
                                    <h3 class="text-sm font-semibold text-gray-900 line-clamp-2">{{ $resource->title }}</h3>
                                    <p class="text-xs text-gray-600 mb-2 line-clamp-1">{{ $resource->author ?? 'Unknown Author' }}</p>
                                    <p class="text-[11px] text-gray-500 mb-2 uppercase tracking-wide line-clamp-1">{{ $resource->course ?: ($resource->program ?: '') }}</p>
                                    <div class="flex gap-1">
                                        <a href="{{ route('student.books.show', $resource) }}" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-2 rounded text-xs text-center transition-colors">View</a>
                                        <button class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-2 rounded text-xs transition-colors btn-borrow-quick" data-book-id="{{ $resource->id }}" data-book-title="{{ $resource->title }}">Borrow</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-12">
                            <div class="text-gray-300 text-6xl mb-4"><i class="fas fa-scroll"></i></div>
                            <h3 class="text-xl font-semibold text-gray-700 mb-2">No theses available</h3>
                            <p class="text-gray-500">Check back later for thesis resources.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div id="all-books-section" class="mt-10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">All Books</h2>
                        <p class="text-sm text-gray-600">All books in the library collection</p>
                    </div>
                    <a href="{{ route('student.books', ['resource_type' => 'book']) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">See All</a>
                </div>
                
                <div id="books-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 sm:gap-3">
                    @if(isset($books) && $books && $books->count() > 0)
                        @foreach($books as $book)
                        <div class="book-card flex-shrink-0 w-full"
                             data-book-id="{{ $book->id }}"
                             data-book-title="{{ $book->title }}"
                             data-book-author="{{ $book->author ?? 'Unknown Author' }}"
                             data-book-category="{{ $book->category ?? 'General' }}"
                             data-book-resource-type="{{ $book->resource_type ?? 'book' }}"
                             data-book-course="{{ $book->course ?? '' }}"
                             data-book-program="{{ $book->program ?? '' }}"
                             data-book-year-level="{{ $book->year_level ?? '' }}"
                             data-book-published-year="{{ $book->published_year ?? '' }}"
                             data-book-description="{{ $book->description ?? '' }}"
                             data-book-cover="{{ $book->display_cover_url ?? '' }}">
                            <div class="relative group">
                                <div class="book-cover relative bg-gray-100 rounded-lg shadow-md overflow-hidden h-56 sm:h-60 md:h-64 hover:shadow-xl transition-all duration-300 transform group-hover:scale-105">
                                    <span class="absolute top-2 left-2 z-10 px-2 py-1 rounded-full bg-white/90 text-[10px] font-semibold text-gray-700 uppercase tracking-wide">{{ strtoupper(str_replace('_', ' ', $book->resource_type ?: 'book')) }}</span>
                                    @if($book->display_cover_url)
                                        <img src="{{ $book->display_cover_url }}" alt="{{ $book->title }} Cover" class="w-full h-full object-cover rounded-lg book-cover-img" loading="lazy">
                                    @else
                                        <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-book text-gray-400 text-4xl"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="mt-3 text-center">
                                    <h3 class="text-sm font-semibold text-gray-900 line-clamp-2">{{ $book->title }}</h3>
                                    <p class="text-xs text-gray-600 mb-2 line-clamp-1">{{ $book->author ?? 'Unknown Author' }}</p>
                                    <p class="text-[11px] text-gray-500 mb-2 uppercase tracking-wide line-clamp-1">{{ $book->course ?: ($book->program ?: '') }}</p>
                                    <div class="flex gap-1">
                                        <a href="{{ route('student.books.show', $book) }}" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-2 rounded text-xs text-center transition-colors">View</a>
                                        <button class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-2 rounded text-xs transition-colors btn-borrow-quick" data-book-id="{{ $book->id }}" data-book-title="{{ $book->title }}">Borrow</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="col-span-full text-center py-12">
                            <div class="text-gray-300 text-6xl mb-4">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-700 mb-2">No books available</h3>
                            <p class="text-gray-500">Check back later for new additions to our collection.</p>
                        </div>
                    @endif
                </div>
            </div>
            @endif

    <!-- Book Details Modal -->
    <div id="book-modal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen p-2 sm:p-4">
            <!-- Backdrop -->
            <div id="modal-backdrop" class="fixed inset-0 bg-black/50 transition-opacity duration-300"></div>
            
            <!-- Modal Container -->
            <div class="relative w-full max-w-5xl mx-auto">
                <!-- Modal Content -->
                <div class="bg-white rounded-xl shadow-2xl transform transition-all duration-300 w-full max-h-[90vh] overflow-y-auto">
                    <!-- Green Header Bar -->
                    <div class="bg-gradient-to-r from-green-400 to-green-500 text-white px-6 py-4 flex items-center justify-between rounded-t-xl">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-book text-2xl"></i>
                            <h2 class="text-xl font-bold">Book Details</h2>
                        </div>
                        <button id="close-modal" class="p-2 rounded-full bg-white/20 hover:bg-white/30 transition-colors">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Content will be loaded here -->
                    <div id="modal-content" class="p-6 sm:p-8">
                        <!-- Loading state -->
                        <div id="modal-loading" class="flex items-center justify-center py-12">
                            <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                        </div>
                        
                        <!-- Error state -->
                        <div id="modal-error" class="hidden p-6 text-center">
                            <div class="text-red-500 text-5xl mb-4">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">Error Loading Book Details</h3>
                            <p class="text-gray-600 mb-6">We couldn't load the book details. Please try again later.</p>
                            <button id="retry-loading" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                Try Again
                            </button>
                        </div>
                        
                        <!-- Book details will be loaded here -->
                        <div id="book-details" class="hidden"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

            <!-- BSN Nursing Books Section -->
            <div id="bsn-books-section" class="mb-8" style="display: none;">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">BSN Nursing Books</h2>
                        <p class="text-sm text-gray-600">Essential resources for nursing students</p>
                    </div>
                    <div class="flex space-x-2">
                        <button id="prevBtnBSN" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button id="nextBtnBSN" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
                <!-- BSN Books Carousel -->
                <div class="relative">
                    <div id="bsn-carousel" class="book-carousel overflow-x-auto flex gap-4 transition-transform duration-300 snap-x snap-mandatory md:overflow-hidden md:snap-none">
                        @php
                            $bsnBooks = [];
                            if(isset($books) && $books) {
                                foreach($books as $book) {
                                    $course = strtoupper(trim($book->course ?? ''));
                                    if($course === 'BSN') {
                                        $bsnBooks[] = $book;
                                    }
                                }
                            }
                        @endphp
                        
                        @if(!empty($bsnBooks))
                            @foreach($bsnBooks as $book)
                                <div class="group relative bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 book-card overflow-hidden flex-shrink-0" data-book-id="{{ $book->id }}" data-book-title="{{ $book->title }}">
                                    <!-- Book Cover -->
                                    <div class="relative aspect-[3/4] bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden shadow border border-gray-200">
                                        @if($book->cover_photo)
                                            <img src="{{ $book->display_cover_url }}" alt="{{ $book->title }} Cover" class="w-full h-full object-cover book-cover-img" loading="lazy">
                                        @else
                                            <!-- Default book cover design -->
                                            <div class="absolute inset-0 bg-white default-book-cover">
                                                <div class="h-8 bg-pink-600 relative">
                                                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                                                </div>
                                                <div class="p-2 h-full flex flex-col justify-between">
                                                    <div class="text-center">
                                                        <h3 class="text-xs font-bold text-gray-900 leading-tight mb-0.5 line-clamp-2">{{ Str::limit($book->title, 30) }}</h3>
                                                        <p class="text-xs text-gray-600 font-medium line-clamp-1">{{ Str::limit($book->author, 20) }}</p>
                                                    </div>
                                                    <div class="flex-1 flex items-center justify-center my-1">
                                                        <div class="w-8 h-8 bg-pink-100 rounded-full flex items-center justify-center">
                                                            <i class="fas fa-heartbeat text-xs text-pink-600"></i>
                                                        </div>
                                                    </div>
                                                    <div class="text-center">
                                                        <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold line-clamp-1">{{ $book->course ?? 'BSN' }}</div>
                                                    </div>
                                                </div>
                                                <div class="absolute bottom-0 left-0 right-0 h-4 bg-pink-600">
                                                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Book Details -->
                                    <div class="p-2 bg-white">
                                        <p class="text-gray-600 text-xs mb-2 line-clamp-1">
                                            {{ $book->author ?? 'Unknown Author' }}
                                        </p>
                                        
                                        <div class="flex gap-1">
                                            <a href="{{ route('student.books.show', $book) }}" 
                                               class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-1.5 px-2 rounded text-xs text-center transition-all duration-200 shadow hover:shadow-md">
                                               View
                                            </a>
                                            <button class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-1.5 px-2 rounded text-xs transition-all duration-200 shadow hover:shadow-md btn-borrow-quick" 
                                                    data-book-id="{{ $book->id }}" data-book-title="{{ $book->title }}">
                                                Borrow
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <!-- No BSN books found -->
                            <div class="col-span-full text-center py-12">
                                <div class="text-gray-300 text-6xl mb-4">
                                    <i class="fas fa-heartbeat"></i>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-700 mb-2">No BSN books available</h3>
                                <p class="text-gray-500">Check back later for nursing books.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- BSE - Bachelor of Science in Entrepreneurship Books Section -->
            <div id="bse-books-section" class="mb-8" style="display: none;">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">BSE - Entrepreneurship Books</h2>
                        <p class="text-sm text-gray-600">Business startup and entrepreneurship resources</p>
                    </div>
                    <div class="flex space-x-2">
                        <button id="prevBtnBSE" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button id="nextBtnBSE" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
                <!-- BSE Books Carousel -->
                <div class="relative">
                    <div id="bse-carousel" class="book-carousel overflow-x-auto flex gap-4 transition-transform duration-300 snap-x snap-mandatory md:overflow-hidden md:snap-none">
                        @php
                            $bseBooks = [];
                            if(isset($books) && $books) {
                                foreach($books as $book) {
                                    $course = strtoupper(trim($book->course ?? ''));
                                    if($course === 'BSE') {
                                        $bseBooks[] = $book;
                                    }
                                }
                            }
                        @endphp
                        
                        @if(!empty($bseBooks))
                            @foreach($bseBooks as $book)
                            <div class="group relative bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 book-card overflow-hidden flex-shrink-0" data-book-id="{{ $book->id }}" data-book-title="{{ $book->title }}">
                                <!-- Book Cover -->
                                <div class="relative aspect-[3/4] bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden shadow border border-gray-200">
                                                                        
                                    @if($book->cover_photo)
                                        <img src="{{ $book->display_cover_url }}" alt="{{ $book->title }} Cover" class="w-full h-full object-cover book-cover-img" loading="lazy">
                                    @else
                                        <!-- Default book cover design -->
                                        <div class="absolute inset-0 bg-white default-book-cover">
                                            <div class="h-8 bg-green-600 relative">
                                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                                            </div>
                                            <div class="p-2 h-full flex flex-col justify-between">
                                                <div class="text-center">
                                                    <h3 class="text-xs font-bold text-gray-900 leading-tight mb-0.5 line-clamp-2">{{ Str::limit($book->title, 30) }}</h3>
                                                    <p class="text-xs text-gray-600 font-medium line-clamp-1">{{ Str::limit($book->author, 20) }}</p>
                                                </div>
                                                <div class="flex-1 flex items-center justify-center my-1">
                                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                                        <i class="fas fa-lightbulb text-xs text-green-600"></i>
                                                    </div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold line-clamp-1">{{ $book->course ?? 'BSE' }}</div>
                                                </div>
                                            </div>
                                            <div class="absolute bottom-0 left-0 right-0 h-4 bg-green-600">
                                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="p-2 bg-white">
                                    <p class="text-gray-600 text-xs mb-2 line-clamp-1">{{ $book->author ?? 'Unknown Author' }}</p>
                                    <div class="flex gap-1">
                                        <a href="{{ route('student.books.show', $book) }}" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-1.5 px-2 rounded text-xs text-center transition-all duration-200 shadow hover:shadow-md">View</a>
                                        @if(in_array($book->id, $borrowedBooks ?? []))
                                            <button class="flex-1 bg-gray-400 text-white font-semibold py-1.5 px-2 rounded text-xs cursor-not-allowed" disabled>
                                                <i class="fas fa-check-circle mr-1"></i>Already Borrowed
                                            </button>
                                        @else
                                            <button class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-1.5 px-2 rounded text-xs transition-all duration-200 shadow hover:shadow-md btn-borrow-quick" data-book-id="{{ $book->id }}" data-book-title="{{ $book->title }}">Borrow</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="col-span-full text-center py-12">
                                <div class="text-gray-300 text-6xl mb-4"><i class="fas fa-lightbulb"></i></div>
                                <h3 class="text-xl font-semibold text-gray-700 mb-2">No BSE books available</h3>
                                <p class="text-gray-500">Check back later for entrepreneurship books.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- BSED - Bachelor of Science in Elementary Education Books Section -->
            <div id="bsed-books-section" class="mb-8" style="display: none;">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">BSED - Elementary Education Books</h2>
                        <p class="text-sm text-gray-600">Elementary education and teaching resources</p>
                    </div>
                    <div class="flex space-x-2">
                        <button id="prevBtnBSED" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button id="nextBtnBSED" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
                <!-- BSED Books Carousel -->
                <div class="relative">
                    <div id="bsed-carousel" class="book-carousel overflow-x-auto flex gap-4 transition-transform duration-300 snap-x snap-mandatory md:overflow-hidden md:snap-none">
                        @php
                            $bsedBooks = [];
                            if(isset($books) && $books) {
                                foreach($books as $book) {
                                    $course = strtoupper(trim($book->course ?? ''));
                                    if($course === 'BSED') {
                                        $bsedBooks[] = $book;
                                    }
                                }
                            }
                        @endphp
                        
                        @if(!empty($bsedBooks))
                            @foreach($bsedBooks as $book)
                            <div class="group relative bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 book-card overflow-hidden flex-shrink-0" data-book-id="{{ $book->id }}" data-book-title="{{ $book->title }}">
                                <!-- Book Cover -->
                                <div class="relative aspect-[3/4] bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden shadow border border-gray-200">
                                                                        
                                    @if($book->cover_photo)
                                        <img src="{{ $book->display_cover_url }}" alt="{{ $book->title }} Cover" class="w-full h-full object-cover book-cover-img" loading="lazy">
                                    @else
                                        <!-- Default book cover design -->
                                        <div class="absolute inset-0 bg-white default-book-cover">
                                            <div class="h-8 bg-purple-600 relative">
                                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                                            </div>
                                            <div class="p-2 h-full flex flex-col justify-between">
                                                <div class="text-center">
                                                    <h3 class="text-xs font-bold text-gray-900 leading-tight mb-0.5 line-clamp-2">{{ Str::limit($book->title, 30) }}</h3>
                                                    <p class="text-xs text-gray-600 font-medium line-clamp-1">{{ Str::limit($book->author, 20) }}</p>
                                                </div>
                                                <div class="flex-1 flex items-center justify-center my-1">
                                                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                                        <i class="fas fa-child text-xs text-purple-600"></i>
                                                    </div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold line-clamp-1">{{ $book->course ?? 'BSED' }}</div>
                                                </div>
                                            </div>
                                            <div class="absolute bottom-0 left-0 right-0 h-4 bg-purple-600">
                                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="p-2 bg-white">
                                    <p class="text-gray-600 text-xs mb-2 line-clamp-1">{{ $book->author ?? 'Unknown Author' }}</p>
                                    <div class="flex gap-1">
                                        <a href="{{ route('student.books.show', $book) }}" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-1.5 px-2 rounded text-xs text-center transition-all duration-200 shadow hover:shadow-md">View</a>
                                        @if(in_array($book->id, $borrowedBooks ?? []))
                                            <button class="flex-1 bg-gray-400 text-white font-semibold py-1.5 px-2 rounded text-xs cursor-not-allowed" disabled>
                                                <i class="fas fa-check-circle mr-1"></i>Already Borrowed
                                            </button>
                                        @else
                                            <button class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-1.5 px-2 rounded text-xs transition-all duration-200 shadow hover:shadow-md btn-borrow-quick" data-book-id="{{ $book->id }}" data-book-title="{{ $book->title }}">Borrow</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="col-span-full text-center py-12">
                                <div class="text-gray-300 text-6xl mb-4"><i class="fas fa-child"></i></div>
                                <h3 class="text-xl font-semibold text-gray-700 mb-2">No BSED books available</h3>
                                <p class="text-gray-500">Check back later for elementary education books.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- BSHM - Bachelor of Science in Hospitality Management Books Section -->
            <div id="bshm-books-section" class="mb-8" style="display: none;">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">BSHM - Hospitality Management Books</h2>
                        <p class="text-sm text-gray-600">Hospitality and tourism management resources</p>
                    </div>
                    <div class="flex space-x-2">
                        <button id="prevBtnBSHM" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button id="nextBtnBSHM" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
                <!-- BSHM Books Carousel -->
                <div class="relative">
                    <div id="bshm-carousel" class="book-carousel overflow-x-auto flex gap-4 transition-transform duration-300 snap-x snap-mandatory md:overflow-hidden md:snap-none">
                        @php
                            $bshmBooks = [];
                            if(isset($books) && $books) {
                                foreach($books as $book) {
                                    $course = strtoupper(trim($book->course ?? ''));
                                    if($course === 'BSHM') {
                                        $bshmBooks[] = $book;
                                    }
                                }
                            }
                        @endphp
                        
                        @if(!empty($bshmBooks))
                            @foreach($bshmBooks as $book)
                            <div class="group relative bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 book-card overflow-hidden flex-shrink-0" data-book-id="{{ $book->id }}" data-book-title="{{ $book->title }}">
                                <!-- Book Cover -->
                                <div class="relative aspect-[3/4] bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden shadow border border-gray-200">
                                                                        
                                    @if($book->cover_photo)
                                        <img src="{{ $book->display_cover_url }}" alt="{{ $book->title }} Cover" class="w-full h-full object-cover book-cover-img" loading="lazy">
                                    @else
                                        <!-- Default book cover design -->
                                        <div class="absolute inset-0 bg-white default-book-cover">
                                            <div class="h-8 bg-amber-600 relative">
                                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                                            </div>
                                            <div class="p-2 h-full flex flex-col justify-between">
                                                <div class="text-center">
                                                    <h3 class="text-xs font-bold text-gray-900 leading-tight mb-0.5 line-clamp-2">{{ Str::limit($book->title, 30) }}</h3>
                                                    <p class="text-xs text-gray-600 font-medium line-clamp-1">{{ Str::limit($book->author, 20) }}</p>
                                                </div>
                                                <div class="flex-1 flex items-center justify-center my-1">
                                                    <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center">
                                                        <i class="fas fa-utensils text-xs text-amber-600"></i>
                                                    </div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold line-clamp-1">{{ $book->course ?? 'BSHM' }}</div>
                                                </div>
                                            </div>
                                            <div class="absolute bottom-0 left-0 right-0 h-4 bg-amber-600">
                                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="p-2 bg-white">
                                    <p class="text-gray-600 text-xs mb-2 line-clamp-1">{{ $book->author ?? 'Unknown Author' }}</p>
                                    <div class="flex gap-1">
                                        <a href="{{ route('student.books.show', $book) }}" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-1.5 px-2 rounded text-xs text-center transition-all duration-200 shadow hover:shadow-md">View</a>
                                        @if(in_array($book->id, $borrowedBooks ?? []))
                                            <button class="flex-1 bg-gray-400 text-white font-semibold py-1.5 px-2 rounded text-xs cursor-not-allowed" disabled>
                                                <i class="fas fa-check-circle mr-1"></i>Already Borrowed
                                            </button>
                                        @else
                                            <button class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-1.5 px-2 rounded text-xs transition-all duration-200 shadow hover:shadow-md btn-borrow-quick" data-book-id="{{ $book->id }}" data-book-title="{{ $book->title }}">Borrow</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="col-span-full text-center py-12">
                                <div class="text-gray-300 text-6xl mb-4"><i class="fas fa-utensils"></i></div>
                                <h3 class="text-xl font-semibold text-gray-700 mb-2">No BSHM books available</h3>
                                <p class="text-gray-500">Check back later for hospitality management books.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- BSIT - Bachelor of Science in Information Technology Books Section -->
            <div id="bsit-books-section" class="mb-8" style="display: none;">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">BSIT - Information Technology Books</h2>
                        <p class="text-sm text-gray-600">Information technology and networking resources</p>
                    </div>
                    <div class="flex space-x-2">
                        <button id="prevBtnBSIT" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button id="nextBtnBSIT" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
                <!-- BSIT Books Carousel -->
                <div class="relative">
                    <div id="bsit-carousel" class="book-carousel overflow-x-auto flex gap-4 transition-transform duration-300 snap-x snap-mandatory md:overflow-hidden md:snap-none">
                        @php
                            $bsitBooks = [];
                            if(isset($books) && $books) {
                                foreach($books as $book) {
                                    $course = strtoupper(trim($book->course ?? ''));
                                    if($course === 'BSIT') {
                                        $bsitBooks[] = $book;
                                    }
                                }
                            }
                        @endphp
                        
                        @if(!empty($bsitBooks))
                            @foreach($bsitBooks as $book)
                            <div class="group relative bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 book-card overflow-hidden flex-shrink-0" data-book-id="{{ $book->id }}" data-book-title="{{ $book->title }}">
                                <!-- Book Cover -->
                                <div class="relative aspect-[3/4] bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden shadow border border-gray-200">
                                                                        
                                    @if($book->cover_photo)
                                        <img src="{{ $book->display_cover_url }}" alt="{{ $book->title }} Cover" class="w-full h-full object-cover book-cover-img" loading="lazy">
                                    @else
                                        <!-- Default book cover design -->
                                        <div class="absolute inset-0 bg-white default-book-cover">
                                            <div class="h-8 bg-indigo-600 relative">
                                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                                            </div>
                                            <div class="p-2 h-full flex flex-col justify-between">
                                                <div class="text-center">
                                                    <h3 class="text-xs font-bold text-gray-900 leading-tight mb-0.5 line-clamp-2">{{ Str::limit($book->title, 30) }}</h3>
                                                    <p class="text-xs text-gray-600 font-medium line-clamp-1">{{ Str::limit($book->author, 20) }}</p>
                                                </div>
                                                <div class="flex-1 flex items-center justify-center my-1">
                                                    <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                                        <i class="fas fa-network-wired text-xs text-indigo-600"></i>
                                                    </div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold line-clamp-1">{{ $book->course ?? 'BSIT' }}</div>
                                                </div>
                                            </div>
                                            <div class="absolute bottom-0 left-0 right-0 h-4 bg-indigo-600">
                                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="p-2 bg-white">
                                    <p class="text-gray-600 text-xs mb-2 line-clamp-1">{{ $book->author ?? 'Unknown Author' }}</p>
                                    <div class="flex gap-1">
                                        <a href="{{ route('student.books.show', $book) }}" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-1.5 px-2 rounded text-xs text-center transition-all duration-200 shadow hover:shadow-md">View</a>
                                        @if(in_array($book->id, $borrowedBooks ?? []))
                                            <button class="flex-1 bg-gray-400 text-white font-semibold py-1.5 px-2 rounded text-xs cursor-not-allowed" disabled>
                                                <i class="fas fa-check-circle mr-1"></i>Already Borrowed
                                            </button>
                                        @else
                                            <button class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-1.5 px-2 rounded text-xs transition-all duration-200 shadow hover:shadow-md btn-borrow-quick" data-book-id="{{ $book->id }}" data-book-title="{{ $book->title }}">Borrow</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="col-span-full text-center py-12">
                                <div class="text-gray-300 text-6xl mb-4"><i class="fas fa-network-wired"></i></div>
                                <h3 class="text-xl font-semibold text-gray-700 mb-2">No BSIT books available</h3>
                                <p class="text-gray-500">Check back later for information technology books.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>



    <!-- Borrow Book Modal -->
    <div id="borrow-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" id="borrow-modal-backdrop">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full" id="borrow-modal-content">
                <div class="bg-white px-6 pt-6 pb-4">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-hand-holding text-blue-600"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Borrow Book</h3>
                        </div>
                        <button id="close-borrow-modal" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>
                    
                    <!-- Book Info -->
                    <div id="borrow-book-info" class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <div id="borrow-book-cover" class="w-16 h-20 bg-gradient-to-br from-blue-600 to-blue-800 rounded-md flex items-center justify-center text-white text-xs font-medium mr-4">
                                <i class="fas fa-book text-2xl"></i>
                            </div>
                            <div class="flex-1">
                                <h4 id="borrow-book-title" class="font-semibold text-gray-900 mb-1">Book Title</h4>
                                <p id="borrow-book-author" class="text-sm text-gray-600 mb-1">Author Name</p>
                                <p id="borrow-book-category" class="text-xs text-gray-500">Category</p>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hidden form for POST submissions -->
<form id="borrow-form" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="X-Requested-With" value="XMLHttpRequest">
    <button type="submit" id="borrow-submit-btn" style="display: none;">Submit</button>
</form>

    <!-- Borrow Confirmation Popup (center card with subtle overlay) -->
    <div id="borrowPopup" class="fixed inset-0 flex items-center justify-center z-[9999] bg-black/40 hidden p-4">
        <div id="borrowPopupCard" class="bg-white rounded-2xl shadow-2xl max-w-md w-full sm:w-[90%] p-6 sm:p-8 transform transition-all duration-200 scale-95 opacity-0 border border-gray-100">
            <div class="flex justify-center mb-6">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center border-2 border-blue-200">
                    <i class="fas fa-book text-3xl text-blue-600"></i>
                </div>
            </div>
            
            <div class="text-center mb-6">
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Ready to Borrow?</h3>
                <p class="text-gray-600 text-sm mb-4">You're about to borrow:</p>
                
                <div class="bg-blue-50 rounded-xl p-4 mb-4 border border-blue-200">
                    <p class="text-lg font-semibold text-gray-900 truncate" id="borrowPopupTitle">Book Title</p>
                </div>
                
                <div class="space-y-2 mb-6 text-sm">
                    <div class="flex items-center justify-center text-gray-600">
                        <i class="fas fa-calendar-alt text-blue-600 mr-2"></i>
                        <span>Loan period: <strong>1 day</strong></span>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button id="borrowPopupCancel" class="flex-1 px-4 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-all font-medium text-sm">
                    Cancel
                </button>
                <button id="borrowPopupConfirm" class="flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all font-medium text-sm shadow-md hover:shadow-lg">
                    <span id="borrowPopupConfirmText">Confirm Borrow</span>
                </button>
            </div>
        </div>
    </div>  

    <!-- JavaScript -->
    <script>
    window.booksPageRoutes = {
        booksIndex: @json(route('student.books')),
        booksBase: @json(url('student/books')),
        borrowBase: @json(url('student/books')),
        borrowedBookIds: @json(array_map('intval', $borrowedBooks ?? [])),
    };
    </script>
    <script src="{{ asset('js/student-dashboard.js') }}?v={{ filemtime(public_path('js/student-dashboard.js')) }}"></script>
    <script src="{{ asset('js/books-sidebar.js') }}?v={{ filemtime(public_path('js/books-sidebar.js')) }}"></script>

    <!-- Helper functions for category styling -->
    <script>
    // Helper functions for category styling
    function getCategoryColor(category) {
        const colors = {
            'programming': 'bg-blue-600',
            'mathematics': 'bg-green-600',
            'literature': 'bg-purple-600',
            'science': 'bg-red-600',
            'business': 'bg-amber-600',
            'technology': 'bg-indigo-600',
            'education': 'bg-pink-600',
            'reference': 'bg-gray-600',
            'health': 'bg-teal-600',
            'history': 'bg-orange-600',
            'philosophy': 'bg-violet-600'
        };
        return colors[category?.toLowerCase()] || 'bg-gray-600';
    }

    function getCategoryBgColor(category) {
        const colors = {
            'programming': 'bg-blue-100',
            'mathematics': 'bg-green-100',
            'literature': 'bg-purple-100',
            'science': 'bg-red-100',
            'business': 'bg-amber-100',
            'technology': 'bg-indigo-100',
            'education': 'bg-pink-100',
            'reference': 'bg-gray-100',
            'health': 'bg-teal-100',
            'history': 'bg-orange-100',
            'philosophy': 'bg-violet-100'
        };
        return colors[category?.toLowerCase()] || 'bg-gray-100';
    }

    function getCategoryTextColor(category) {
        const colors = {
            'programming': 'text-blue-600',
            'mathematics': 'text-green-600',
            'literature': 'text-purple-600',
            'science': 'text-red-600',
            'business': 'text-amber-600',
            'technology': 'text-indigo-600',
            'education': 'text-pink-600',
            'reference': 'text-gray-600',
            'health': 'text-teal-600',
            'history': 'text-orange-600',
            'philosophy': 'text-violet-600'
        };
        return colors[category?.toLowerCase()] || 'text-gray-600';
    }

    function getCategoryIcon(category) {
        const icons = {
            'programming': 'fa-code',
            'mathematics': 'fa-calculator',
            'literature': 'fa-feather-alt',
            'science': 'fa-flask',
            'business': 'fa-chart-line',
            'technology': 'fa-microchip',
            'education': 'fa-graduation-cap',
            'reference': 'fa-bookmark',
            'health': 'fa-heartbeat',
            'history': 'fa-clock',
            'philosophy': 'fa-brain'
        };
        return icons[category?.toLowerCase()] || 'fa-book';
    }
    </script>

    <script src="{{ asset('js/books-new.js') }}?v={{ filemtime(public_path('js/books-new.js')) }}"></script>
</body>
</html>



