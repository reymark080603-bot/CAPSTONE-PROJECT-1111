<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Knowly - Student Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/student-dashboard.css') }}" rel="stylesheet">
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

    <div class="dashboard-container flex">
        <!-- Mobile Backdrop -->
        <div class="sidebar-backdrop hidden" id="sidebar-backdrop"></div>
        
        <!-- Sidebar -->
        <div class="sidebar bg-gray-800 text-white min-h-screen w-64 fixed md:relative transition-all">
            <div class="p-4">
                <div class="sidebar-welcome mb-6">
                    <h2 class="font-semibold text-lg">Knowly</h2>
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
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Recommended for You</h3>
                        <p class="text-sm text-gray-600 mt-1">Books related to your course and popular picks</p>
                    </div>
                    <div class="flex space-x-2">
                        <button id="prevBtnRecommended" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button id="nextBtnRecommended" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
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
                        <div id="recommendedCarousel" class="book-carousel flex flex-nowrap overflow-x-auto gap-4 sm:gap-6 transition-transform duration-300 hidden snap-x snap-mandatory md:overflow-hidden md:snap-none pb-2" style="scroll-behavior: smooth;">
                            <!-- Books will be rendered by JavaScript -->
                        </div>
                        <div id="no-recommendations" class="text-center py-8 text-gray-500 hidden">
                            <i class="fas fa-inbox text-3xl mb-2"></i>
                            <p>No recommendations available yet</p>
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
                    <div id="continueCarousel" class="book-carousel flex flex-nowrap overflow-x-auto gap-4 sm:gap-6 transition-transform duration-300 hidden snap-x snap-mandatory md:overflow-hidden md:snap-none pb-2" style="scroll-behavior: smooth;"></div>
                    <div id="no-continue" class="text-center py-8 text-gray-500 hidden">
                        <i class="fas fa-inbox text-3xl mb-2"></i>
                        <p>No current books to continue</p>
                    </div>
                </div>
            </div>

            <!-- Recently Added Books - HORIZONTAL CAROUSEL -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center px-4 sm:px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Recently Added Books</h3>
                    <div class="flex space-x-2">
                        <button type="button" onclick="document.getElementById('recentBooksContainer').scrollLeft -= 220" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button type="button" onclick="document.getElementById('recentBooksContainer').scrollLeft += 220" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Book Carousel - HORIZONTAL with $recentBooks -->
                <div class="p-6">
                    <!-- Container: flex, flex-nowrap, overflow-x-auto, gap-6 -->
                    <div id="recentBooksContainer" class="flex flex-nowrap overflow-x-auto gap-4 sm:gap-6 pb-2" style="scroll-behavior: smooth;">
                        
                        @forelse($recentBooks as $book)
                            <!-- Card: shrink-0, fixed width w-48 -->
                            <div class="book-card flex-shrink-0 w-40 sm:w-44 md:w-48">
                                <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
                                    <!-- Image: fixed height -->
                                    <div class="relative h-56 sm:h-60 md:h-64 bg-gray-100">
                                        @if($book->display_cover_url)
                                            <img src="{{ $book->display_cover_url }}" alt="{{ $book->title }}" class="w-full h-full object-cover" loading="lazy">
                                        @else
                                            <div class="w-full h-full bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center">
                                                <i class="fas fa-book text-4xl text-blue-400"></i>
                                            </div>
                                        @endif
                                        
                                        @if($book->availability_status === 'available')
                                            <span class="absolute top-2 right-2 px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Available</span>
                                        @else
                                            <span class="absolute top-2 right-2 px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Unavailable</span>
                                        @endif
                                    </div>
                                    
                                    <!-- Info below image -->
                                    <div class="p-3">
                                        <h5 class="font-semibold text-sm text-gray-900 truncate" title="{{ $book->title }}">{{ $book->title }}</h5>
                                        <p class="text-xs text-gray-600 mt-1 truncate" title="{{ $book->author }}">{{ $book->author }}</p>
                                        
                                        <!-- Buttons below -->
                                        <div class="flex gap-2 mt-3">
                                            <a href="{{ route('student.books.show', $book->id) }}" class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white py-2 rounded text-xs font-medium">View</a>
                                            @if($book->availability_status === 'available')
                                                <button onclick="quickBorrowBook({{ $book->id }}, '{{ addslashes($book->title) }}')" class="flex-1 text-center bg-green-600 hover:bg-green-700 text-white py-2 rounded text-xs font-medium">Borrow</button>
                                            @else
                                                <button disabled class="flex-1 text-center bg-gray-300 text-gray-500 py-2 rounded text-xs font-medium cursor-not-allowed">Unavailable</button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="w-full text-center py-8 text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>No recently added books</p>
                            </div>
                        @endforelse
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Borrow Confirmation Popup (center card with subtle overlay) -->
    <div id="borrowPopup" class="fixed inset-0 items-center justify-center z-[9999] bg-black/40 hidden">
        <div id="borrowPopupCard" class="bg-white rounded-2xl shadow-2xl max-w-md w-[90%] p-8 transform transition-all duration-200 scale-95 opacity-0 border border-gray-100">
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
                        <span>Loan period: <strong>1 days</strong></span>
                    </div>
                    <div class="flex items-center justify-center text-gray-600">
                        <i class="fas fa-undo text-green-600 mr-2"></i>
                        <span>Auto-return enabled</span>
                    </div>
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

    <script src="{{ asset('js/student-dashboard.js') }}"></script>
    
    <script>
    // Quick Borrow function for Recently Added Books carousel
    function quickBorrowBook(bookId, bookTitle) {
        if(confirm('Borrow "' + bookTitle + '"?')) {
            fetch('/student/books/' + bookId + '/borrow', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Book borrowed successfully!');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to borrow book');
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
            });
        }
    }
    </script>
</body>
</html>
