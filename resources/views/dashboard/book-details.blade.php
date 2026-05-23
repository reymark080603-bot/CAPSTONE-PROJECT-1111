<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $book->title }} - Knowly Library</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @vite('resources/css/components/book-details.css')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/student-dashboard.css') }}" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="header sidebar-expanded bg-green-500 shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 w-full">
            <div class="flex items-center space-x-4 flex-shrink-0">
                <!-- Back Button -->
                <button type="button" onclick="window.goBackToPreviousPage()" class="text-white hover:bg-white/10 p-2 rounded-lg transition-all" title="Back">
                    <i class="fas fa-arrow-left text-lg"></i>
                </button>
                
                <script>
                    window.goBackToPreviousPage = function () {
                        const fallbackUrl = @json($backUrl ?? route('student.books'));
                        let targetUrl = fallbackUrl;

                        try {
                            const referrerUrl = document.referrer ? new URL(document.referrer) : null;
                            const currentPath = window.location.pathname;

                            if (
                                referrerUrl &&
                                referrerUrl.origin === window.location.origin &&
                                referrerUrl.pathname !== currentPath &&
                                !referrerUrl.pathname.includes('/read')
                            ) {
                                targetUrl = referrerUrl.href;
                            }
                        } catch (error) {
                            targetUrl = fallbackUrl;
                        }

                        window.location.assign(targetUrl);
                    };
                </script>
                
                <div class="flex items-center">
                    <img src="{{ asset('images/jhcsclibrary-logo.png') }}" alt="Knowly logo" class="w-8 h-8 rounded-full object-cover mr-3" />
                    <h1 class="text-white text-2xl font-bold">Book Details</h1>
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
        <!-- Sidebar -->
        <div class="sidebar bg-gray-800 min-h-screen">
            <div class="p-4">
                <div class="sidebar-welcome text-white mb-6">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/jhcsclibrary-logo.png') }}" alt="Knowly logo" class="w-8 h-8 rounded-full object-cover" />
                        <h2 class="font-semibold text-lg">Knowly</h2>
                    </div>
                    <p class="text-gray-400 text-sm">Welcome, {{ $user->firstname ?? 'Student' }}</p>
                </div>
                
                <nav class="space-y-2">
                    <a href="{{ route('home') }}" class="sidebar-link flex items-center space-x-3 text-gray-300 px-4 py-3 rounded-lg transition-all hover:text-white">
                        <i class="fas fa-home sidebar-icon"></i>
                        <span class="sidebar-text">Home</span>
                    </a>
                    <a href="{{ route('student.books') }}" class="sidebar-link active flex items-center space-x-3 text-white px-4 py-3 rounded-lg transition-all">
                        <i class="fas fa-book sidebar-icon"></i>
                        <span class="sidebar-text">Library</span>
                    </a>
                    <a href="{{ route('student.loans') }}" class="sidebar-link flex items-center space-x-3 text-gray-300 px-4 py-3 rounded-lg transition-all hover:text-white">
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
        <div class="main-content p-0 h-full">
            <!-- Book Details Content -->
            <div class="p-4 sm:p-6 lg:p-8 h-full overflow-y-auto">
                <!-- Book Card -->
                <div class="bg-white rounded-2xl shadow-lg p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">
                    <div class="flex flex-col items-center text-center">
                        <!-- Book Cover -->
                        <div class="w-48 h-64 md:w-56 md:h-72 lg:w-64 lg:h-80 rounded-lg shadow-md overflow-hidden mb-6">
                            @if($book->cover_photo)
                                <img 
                                    src="{{ $book->display_cover_url }}" 
                                    alt="{{ $book->title }} Cover"
                                    class="w-full h-full object-cover"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                >
                                <!-- Fallback design -->
                                <div class="absolute inset-0 hidden" style="display: none;">
                                    @include('dashboard.partials._default_book_cover')
                                </div>
                            @else
                                @include('dashboard.partials._default_book_cover')
                            @endif
                        </div>
                        
                        <!-- Title and Author -->
                        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 mb-2">{{ $book->title }}</h1>
                        <p class="text-gray-600 mb-4">by {{ $book->author }}</p>
                        
                        <!-- Status Badges -->
                        <div class="flex flex-wrap justify-center gap-3 mb-6">
                            <span class="px-4 py-1.5 text-sm font-medium rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1.5"></i>Available
                            </span>
                            <span class="px-4 py-1.5 text-sm font-medium rounded-full bg-blue-100 text-blue-800">
                                {{ $book->course ?: ($book->program ?: 'General') }}
                            </span>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row flex-wrap justify-center gap-3 sm:gap-4 mb-8 w-full sm:w-auto">
                            @if(!$book->user_has_borrowed)
                                <button class="btn-borrow w-full sm:w-auto justify-center px-6 sm:px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors flex items-center text-base sm:text-lg" 
                                        data-book-id="{{ $book->id }}" 
                                        data-book-title="{{ $book->title }}">
                                    <i class="fas fa-book-reader mr-2"></i>Borrow Book
                                </button>
                            @else
                                <a href="{{ route('student.books.read', $book->id) }}" class="w-full sm:w-auto justify-center px-6 sm:px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors flex items-center text-base sm:text-lg">
                                    <i class="fas fa-book-open mr-2"></i>Read Now
                                </a>
                            @endif
                        </div>

                        <!-- Quick Info Grid -->
                        <div class="w-full grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                            <div class="text-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <div class="text-sm font-medium text-gray-900">{{ $book->published_year ?? '---' }}</div>
                                <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">Year</div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <div class="text-sm font-medium text-gray-900">{{ $book->course ?? 'All' }}</div>
                                <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">Course</div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <div class="text-sm font-medium text-gray-900">{{ $book->year_level ?? 'All' }}</div>
                                <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">Level</div>
                            </div>
                        </div>

                        <!-- Description Section -->
                        @if($book->description)
                        <div class="w-full">
                            <h2 class="text-xl lg:text-2xl font-bold text-gray-900 mb-4">Description</h2>
                            <p class="text-gray-700 leading-relaxed text-left">{{ $book->description }}</p>
                        </div>
                        @endif

                    </div>
                </div>

                <!-- Borrowing Information (if applicable) -->
                @if($book->user_has_borrowed && $book->borrow_record)
                <div class="bg-blue-50 rounded-2xl p-6 lg:p-8 border border-blue-200 max-w-7xl mx-auto mt-6">
                    <h2 class="text-xl lg:text-2xl font-bold text-blue-900 mb-6">Your Borrowing Details</h2>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="text-center bg-white rounded-lg p-4 lg:p-6">
                            <div class="text-2xl lg:text-3xl font-bold {{ $book->borrow_record->days_remaining > 0 ? 'text-blue-600' : 'text-red-600' }} mb-2">{{ $book->borrow_record->days_remaining }} Days</div>
                            <div class="text-sm text-blue-700 uppercase tracking-wide">Duration</div>
                        </div>
                        <div class="text-center bg-white rounded-lg p-4 lg:p-6">
                            <div class="text-2xl lg:text-3xl font-bold text-blue-600 mb-2">{{ $book->borrow_record->borrowed_date->format('M j') }}</div>
                            <div class="text-sm text-blue-700 uppercase tracking-wide">Borrowed</div>
                        </div>
                        <div class="text-center bg-white rounded-lg p-4 lg:p-6">
                            <div class="text-2xl lg:text-3xl font-bold text-blue-600 mb-2">{{ $book->borrow_record->due_date->format('M j') }}</div>
                            <div class="text-sm text-blue-700 uppercase tracking-wide">Due Date</div>
                        </div>
                    </div>
                </div>
                @endif
                </div>
            </div>
        </div>
    </div>


    <!-- Include JavaScript -->
    <script>
    window.bookDetailsRoutes = {
        booksBase: @json(url('student/books')),
        borrowBase: @json(url('student/books')),
    };
    </script>
    <script src="{{ asset('js/student-dashboard.js') }}?v={{ filemtime(public_path('js/student-dashboard.js')) }}"></script>
    @vite('resources/js/components/book-details.js')
    <!-- Hidden borrow notification overlay; kept in markup for future activation but does not block page interactions -->
    <div id="borrowNotification" class="hidden fixed inset-0 flex items-center justify-center z-[9999] pointer-events-none">
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 max-w-md w-[90%] mx-4 p-6 transform transition-all duration-300 scale-95 opacity-0">
            <!-- Icon and Header -->
            <div class="flex items-start gap-3 mb-4">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                    <i class="fas fa-hand-holding text-blue-600 text-lg"></i>
                </div>
                <div class="flex-1">
                    <!-- Title -->
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Borrow Book</h3>
                    
                    <!-- Description -->
                    <p class="text-sm text-gray-600 mb-4">
                        Borrow this book for 1 day? It will be automatically returned after the period ends.
                    </p>
                    
                    <!-- Book Title -->
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-sm font-medium text-gray-800" id="borrowNotificationTitle">
                            "{{ $book->title }}"
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 mt-5">
                <button id="borrowNotificationCancel" class="px-4 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                    Cancel
                </button>
                <button id="borrowNotificationConfirm" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                    <span id="borrowNotificationConfirmText">Confirm</span>
                </button>
            </div>
        </div>
    </div>

</body>
</html>
