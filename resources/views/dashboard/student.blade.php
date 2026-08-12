<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Knowly - Student Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/student-dashboard.css') }}?v={{ filemtime(public_path('css/student-dashboard.css')) }}" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen homepage-dashboard">
    @php
        $recentBooks = $recentBooks ?? collect();
        $recentEJournalResources = $recentEJournalResources ?? ($recentEBookResources ?? collect());
        $recentThesisResources = $recentThesisResources ?? collect();
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
            
            <div class="header-actions flex items-center flex-shrink-0 gap-3">
                <!-- Quick Search Bar -->
                <form action="{{ route('student.books') }}" method="GET" id="header-search-form" class="quick-search-form relative w-full max-w-[14rem] sm:max-w-xs">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 z-10 flex items-center pl-4 text-gray-500 pointer-events-none">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text"
                               id="header-search"
                               name="search"
                               placeholder="Quick search books..."
                               class="block w-full min-w-0 rounded-full border border-gray-300 bg-white py-2 pl-11 pr-4 text-sm text-gray-800 placeholder-gray-600 shadow-sm transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 sm:text-base">
                    </div>
                    <div id="header-search-results" class="quick-search-results hidden absolute left-0 right-0 sm:right-0 sm:left-auto mt-2 w-full sm:w-96 max-w-[calc(100vw-2rem)] max-h-96 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-xl z-[60]"></div>
                </form>
                
                <!-- User Profile -->
                <div class="flex items-center space-x-3 homepage-profile-group">
                    <div class="relative group" data-profile-wrapper>
                        <button type="button" data-profile-toggle class="w-10 h-10 bg-white text-green-700 border-2 border-white/60 rounded-full flex items-center justify-center cursor-pointer shadow-sm hover:shadow-md transition-all">
                            <span class="font-semibold text-sm text-green-700">{{ substr($user->firstname ?? '', 0, 1) }}{{ substr($user->lastname ?? '', 0, 1) }}</span>
                        </button>
                        <!-- Dropdown tooltip -->
                        <div data-profile-menu class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg p-3 hidden z-50">
                            <div class="text-sm text-gray-700">
                                <p class="font-semibold">{{ $user->firstname ?? 'User' }} {{ $user->lastname ?? '' }}</p>
                                <p class="text-gray-500 text-xs truncate">{{ $user->email ?? '' }}</p>
                                <hr class="my-2">
                                <a href="{{ route('student.profile') }}" class="block px-2 py-1 hover:bg-gray-100 rounded text-gray-700 font-medium mb-1">
                                    <i class="fas fa-user-circle mr-1.5 text-green-600"></i> My Profile
                                </a>
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

    <div class="dashboard-container flex">
        <!-- Mobile Backdrop -->
        <div class="sidebar-backdrop hidden" id="sidebar-backdrop"></div>
        
        <!-- Sidebar -->
        <div class="sidebar bg-gray-800 text-white min-h-screen w-64 fixed md:relative transition-all">
            <div class="p-4">
                <div class="sidebar-welcome mb-6">
                    <a href="{{ route('dashboard') }}" class="font-semibold text-lg inline-block">Knowly</a>
                    <p class="text-gray-400 text-sm">Welcome, {{ $user->firstname ?? 'Student' }}</p>
                </div>
                
                <nav class="space-y-2">
                    <a href="{{ route('dashboard') }}" class="sidebar-link active flex items-center space-x-3 text-white px-4 py-3 rounded-lg transition-all bg-green-600">
                        <i class="fas fa-home"></i>
                        <span class="sidebar-text">Home</span>
                    </a>
                    <a href="{{ route('student.books') }}" class="sidebar-link flex items-center space-x-3 text-gray-300 px-4 py-3 rounded-lg transition-all hover:text-white hover:bg-gray-700">
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
                    <a href="{{ route('student.profile') }}" class="sidebar-link flex items-center space-x-3 text-gray-300 px-4 py-3 rounded-lg transition-all hover:text-white hover:bg-gray-700">
                        <i class="fas fa-user-cog"></i>
                        <span class="sidebar-text">My Profile</span>
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
        <div class="main-content flex-1 p-4 sm:p-6 ml-0 md:ml-0">
            <!-- Success Message -->
            @if(session('success'))
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
                <div class="ml-auto pl-3">
                    <button type="button" class="text-green-600 hover:text-green-800" onclick="this.parentElement.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            @endif
            
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Welcome, {{ $user->firstname ?? 'Student' }} </h1>
            </div>

            <!-- Recommended for You Section -->
            <div class="bg-white rounded-lg shadow-sm mb-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center px-4 sm:px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between gap-3 w-full">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Recommended for You</h3>
                            <p class="text-sm text-gray-600 mt-1">Resources matched to your program or course</p>
                        </div>
                        <a href="{{ route('student.books', ['scope' => 'recommended']) }}" class="px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 whitespace-nowrap">See All</a>
                    </div>
                    <div class="flex space-x-2 carousel-nav">
                        <button id="prevBtnRecommended" class="carousel-arrow p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button id="nextBtnRecommended" class="carousel-arrow p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Recommended Books Carousel -->
                <div class="p-6">
                    <div id="loading-recommended" class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
                    </div>
                    <div class="mb-6">
                        <div id="recommendedCarousel" class="book-carousel flex flex-nowrap overflow-x-auto gap-4 sm:gap-6 hidden snap-x snap-mandatory pb-2" style="scroll-behavior: smooth;">
                            <!-- Books will be rendered by JavaScript -->
                        </div>
                        <div id="no-recommendations" class="text-center py-8 text-gray-500 hidden">
                            <i class="fas fa-inbox text-3xl mb-2"></i>
                            <p>No course-matched resources available yet</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Continue Reading Section -->
            <div class="bg-white rounded-lg shadow-sm mb-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center px-4 sm:px-6 py-4 border-b border-gray-200">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900" data-continue-note>Continue Reading</h3>
                    </div>
                </div>
                
                <!-- Continue Reading Carousel -->
                <div class="p-6">
                    <div id="loading-continue" class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
                    </div>
                    <div id="continueCarousel" class="book-carousel flex flex-nowrap overflow-x-auto gap-4 sm:gap-6 transition-transform duration-300 hidden snap-x snap-mandatory pb-2" style="scroll-behavior: smooth;"></div>
                    <div id="no-continue" class="text-center py-8 text-gray-500 hidden">
                        <i class="fas fa-inbox text-3xl mb-2"></i>
                        <p>No current books to continue</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm mb-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center px-4 sm:px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between gap-3 w-full">
                        <h3 class="text-lg font-semibold text-gray-900">Latest E-Journals</h3>
                        <a href="{{ route('student.books', ['resource_type' => 'e_journal']) }}" class="px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 whitespace-nowrap">See All</a>
                    </div>
                    <div class="flex space-x-2 carousel-nav">
                        <button type="button" onclick="document.getElementById('recentEJournalsContainer').scrollLeft -= 220" class="carousel-arrow p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button type="button" onclick="document.getElementById('recentEJournalsContainer').scrollLeft += 220" class="carousel-arrow p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <div id="recentEJournalsContainer" class="book-carousel flex flex-nowrap overflow-x-auto gap-4 sm:gap-6 pb-2" style="scroll-behavior: smooth;">
                        @forelse($recentEJournalResources as $resource)
                            <div class="book-card flex-shrink-0 w-40 sm:w-44 md:w-48" data-book-id="{{ $resource->id }}" data-book-title="{{ $resource->title }}" data-borrow-days="{{ $resource->borrow_days ?? 5 }}">
                                <div class="relative group">
                                    <div class="book-cover relative bg-gray-100 rounded-lg shadow-md overflow-hidden h-64 sm:h-72 md:h-80 hover:shadow-xl transition-all duration-300 transform group-hover:scale-105">
                                        <span class="absolute top-2 left-2 z-10 px-2 py-1 rounded-full bg-white/90 text-[10px] font-semibold text-gray-700 uppercase tracking-wide">E Journal</span>
                                        <img src="{{ $resource->display_cover_url }}" alt="{{ $resource->title }}" class="w-full h-full object-cover rounded-lg" loading="lazy">
                                    </div>

                                    <div class="mt-3 text-center">
                                        <h5 class="font-semibold text-sm text-gray-900 truncate" title="{{ $resource->title }}">{{ $resource->title }}</h5>
                                        <p class="text-xs text-gray-600 mb-2 truncate" title="{{ $resource->author }}">{{ $resource->author }}</p>
                                        <p class="text-[11px] text-gray-500 mb-2 uppercase tracking-wide">{{ $resource->course ?: ($resource->program ?: '') }}</p>

                                        <div class="flex gap-1">
                                            <a href="{{ route('student.books.show', $resource->id) }}" class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white px-2 py-2 rounded text-xs font-medium transition-colors">View</a>
                                            <button onclick="quickBorrowBook({{ $resource->id }}, '{{ addslashes($resource->title) }}', {{ $resource->borrow_days ?? 5 }})" class="flex-1 text-center bg-green-500 hover:bg-green-600 text-white px-2 py-2 rounded text-xs font-medium transition-colors btn-borrow-quick" data-book-id="{{ $resource->id }}" data-book-title="{{ $resource->title }}" data-borrow-days="{{ $resource->borrow_days ?? 5 }}">Borrow</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="w-full text-center py-8 text-gray-500">
                                <i class="fas fa-newspaper text-4xl mb-2"></i>
                                <p>No e-journals available yet</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm mb-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center px-4 sm:px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between gap-3 w-full">
                        <h3 class="text-lg font-semibold text-gray-900">Latest E-Thesis</h3>
                        <a href="{{ route('student.books', ['resource_type' => 'thesis']) }}" class="px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 whitespace-nowrap">See All</a>
                    </div>
                    <div class="flex space-x-2 carousel-nav">
                        <button type="button" onclick="document.getElementById('recentThesisContainer').scrollLeft -= 220" class="carousel-arrow p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button type="button" onclick="document.getElementById('recentThesisContainer').scrollLeft += 220" class="carousel-arrow p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <div id="recentThesisContainer" class="book-carousel flex flex-nowrap overflow-x-auto gap-4 sm:gap-6 pb-2" style="scroll-behavior: smooth;">
                        @forelse($recentThesisResources as $resource)
                            <div class="book-card flex-shrink-0 w-40 sm:w-44 md:w-48" data-book-id="{{ $resource->id }}" data-book-title="{{ $resource->title }}" data-borrow-days="{{ $resource->borrow_days ?? 5 }}">
                                <div class="relative group">
                                    <div class="book-cover relative bg-gray-100 rounded-lg shadow-md overflow-hidden h-64 sm:h-72 md:h-80 hover:shadow-xl transition-all duration-300 transform group-hover:scale-105">
                                        <span class="absolute top-2 left-2 z-10 px-2 py-1 rounded-full bg-white/90 text-[10px] font-semibold text-gray-700 uppercase tracking-wide">Thesis</span>
                                        <img src="{{ $resource->display_cover_url }}" alt="{{ $resource->title }}" class="w-full h-full object-cover rounded-lg" loading="lazy">
                                    </div>

                                    <div class="mt-3 text-center">
                                        <h5 class="font-semibold text-sm text-gray-900 truncate" title="{{ $resource->title }}">{{ $resource->title }}</h5>
                                        <p class="text-xs text-gray-600 mb-2 truncate" title="{{ $resource->author }}">{{ $resource->author }}</p>
                                        <p class="text-[11px] text-gray-500 mb-2 uppercase tracking-wide">{{ $resource->course ?: ($resource->program ?: '') }}</p>

                                        <div class="flex gap-1">
                                            <a href="{{ route('student.books.show', $resource->id) }}" class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white px-2 py-2 rounded text-xs font-medium transition-colors">View</a>
                                            <button onclick="quickBorrowBook({{ $resource->id }}, '{{ addslashes($resource->title) }}', {{ $resource->borrow_days ?? 5 }})" class="flex-1 text-center bg-green-500 hover:bg-green-600 text-white px-2 py-2 rounded text-xs font-medium transition-colors btn-borrow-quick" data-book-id="{{ $resource->id }}" data-book-title="{{ $resource->title }}" data-borrow-days="{{ $resource->borrow_days ?? 5 }}">Borrow</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="w-full text-center py-8 text-gray-500">
                                <i class="fas fa-book-reader text-4xl mb-2"></i>
                                <p>No e-thesis available yet</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Recently Added Books - HORIZONTAL CAROUSEL -->
            <div class="bg-white rounded-lg shadow-sm mb-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center px-4 sm:px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between gap-3 w-full">
                        <h3 class="text-lg font-semibold text-gray-900">Recently Added E-Resources</h3>
                        <a href="{{ route('student.books', ['scope' => 'recent']) }}" class="px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 whitespace-nowrap">See All</a>
                    </div>
                    <div class="flex space-x-2 carousel-nav">
                        <button type="button" onclick="document.getElementById('recentBooksContainer').scrollLeft -= 220" class="carousel-arrow p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button type="button" onclick="document.getElementById('recentBooksContainer').scrollLeft += 220" class="carousel-arrow p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
                <div class="p-6">
                    <div id="recentBooksContainer" class="book-carousel flex flex-nowrap overflow-x-auto gap-4 sm:gap-6 pb-2" style="scroll-behavior: smooth;">
                        @forelse($recentBooks as $book)
                            <div class="book-card flex-shrink-0 w-40 sm:w-44 md:w-48" data-book-id="{{ $book->id }}" data-book-title="{{ $book->title }}" data-borrow-days="{{ $book->borrow_days ?? 5 }}">
                                <div class="relative group">
                                    <div class="book-cover relative bg-gray-100 rounded-lg shadow-md overflow-hidden h-64 sm:h-72 md:h-80 hover:shadow-xl transition-all duration-300 transform group-hover:scale-105">
                                        <span class="absolute top-2 left-2 z-10 px-2 py-1 rounded-full bg-white/90 text-[10px] font-semibold text-gray-700 uppercase tracking-wide">{{ strtoupper(str_replace('_', ' ', $book->resource_type ?: 'book')) }}</span>
                                        @if($book->display_cover_url)
                                            <img src="{{ $book->display_cover_url }}" alt="{{ $book->title }}" class="w-full h-full object-cover rounded-lg" loading="lazy">
                                        @else
                                            <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                                <i class="fas fa-book text-gray-400 text-4xl"></i>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="mt-3 text-center">
                                        <h5 class="font-semibold text-sm text-gray-900 truncate" title="{{ $book->title }}">{{ $book->title }}</h5>
                                        <p class="text-xs text-gray-600 mb-2 truncate" title="{{ $book->author }}">{{ $book->author }}</p>
                                        <p class="text-[11px] text-gray-500 mb-2 uppercase tracking-wide">{{ $book->course ?: ($book->program ?: '') }}</p>
                                        
                                        <div class="flex gap-1">
                                            <a href="{{ route('student.books.show', $book->id) }}" class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white px-2 py-2 rounded text-xs font-medium transition-colors">View</a>
                                            @if($book->availability_status === 'available')
                                                <button onclick="quickBorrowBook({{ $book->id }}, '{{ addslashes($book->title) }}', {{ $book->borrow_days ?? 5 }})" class="flex-1 text-center bg-green-500 hover:bg-green-600 text-white px-2 py-2 rounded text-xs font-medium transition-colors btn-borrow-quick" data-book-id="{{ $book->id }}" data-book-title="{{ $book->title }}" data-borrow-days="{{ $book->borrow_days ?? 5 }}">Borrow</button>
                                            @else
                                                <button disabled class="flex-1 text-center bg-gray-300 text-gray-500 px-2 py-2 rounded text-xs font-medium cursor-not-allowed">Unavailable</button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="w-full text-center py-8 text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>No recently added e-resources</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                
                <!-- Duration Selection Box -->
                <div class="mb-6 text-left bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <label for="borrowDurationSelect" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                        <i class="fas fa-clock text-blue-600 mr-1"></i>Select Borrowing Duration:
                    </label>
                    <select id="borrowDurationSelect" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm font-semibold text-gray-800 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="1">1 Day</option>
                    </select>
                    <p class="text-[11px] text-gray-500 mt-1.5" id="borrowLimitNote">Select duration up to the librarian's set limit for this resource.</p>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button id="borrowPopupCancel" class="flex-1 px-4 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-all font-medium text-sm" onclick="window.__studentDashboard.hideBorrowPopup()">
                    Cancel
                </button>
                <button id="borrowPopupConfirm" class="flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all font-medium text-sm shadow-md hover:shadow-lg" onclick="window.__studentDashboard.confirmBorrow()">
                    <span id="borrowPopupConfirmText">Confirm Borrow</span>
                </button>
            </div>
            
            <p class="text-xs text-gray-500 text-center mt-4">
                Manage your loans in <a href="{{ route('student.loans') }}" class="text-blue-600 hover:underline">Loan Books</a>
            </p>
        </div>
    </div>

    <script src="{{ asset('js/student-dashboard.js') }}?v={{ filemtime(public_path('js/student-dashboard.js')) }}"></script>
    <script>
    window.studentDashboardRoutes = {
        recommended: @json(route('dashboard.recommended')),
        recent: @json(route('dashboard.recent')),
        stats: @json(route('dashboard.stats')),
        search: @json(route('dashboard.search')),
        loansApi: @json(route('student.loans.api')),
        booksBase: @json(url('student/books')),
    };
    </script>
    
    <script>
    // Quick Borrow function for Recently Added Books carousel
    function quickBorrowBook(bookId, bookTitle, maxDays = 5) {
        if (window.__studentDashboard && typeof window.__studentDashboard.showBorrowPopup === 'function') {
            window.__studentDashboard.showBorrowPopup(bookId, bookTitle, maxDays);
        }
    }
    </script>
</body>
</html>
