<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Borrow {{ $book->title }} - Knowly Library</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheets">
    <link href="{{ asset('css/student-dashboard.css') }}" rel="stylesheets">
    @vite('resources/css/components/borrow-book.css')
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="header sidebar-expanded bg-green-500 shadow-lg">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center space-x-4">
                <!-- Back Button -->
                <a href="{{ route('student.books.show', $book->id) }}" class="text-white hover:bg-white/10 p-2 rounded-lg transition-all" title="Back to Book Details">
                    <i class="fas fa-arrow-left text-lg"></i>
                </a>
                
                <div class="flex items-center">
                    <img src="{{ asset('images/jhcsclibrary-logo.png') }}" alt="Knowly logo" class="w-8 h-8 rounded-full object-cover mr-3" />
                    <h1 class="text-white text-2xl font-bold">Borrow Book</h1>
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
                    <h2 class="font-semibold text-lg">Knowly</h2>
                    <p class="text-gray-400 text-sm">Welcome, {{ $user->firstname }}</p>
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
            <!-- Borrow Form Container -->
            <div class="bg-white h-full">
                <!-- Header -->
                <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-white shadow-sm">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Borrow: {{ $book->title }}</h1>
                        <p class="text-gray-600 mt-1">Complete the borrowing process</p>
                    </div>
                    </div>
                
                <!-- Auto-Borrow Content -->
                <div class="p-8 h-full overflow-y-auto">
                    <!-- Borrowing Process Card -->
                    <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
                        <!-- Loading State -->
                        <div id="borrowing-loader" class="space-y-6">
                            <div class="flex justify-center">
                                <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i>
                                </div>
                            </div>
                            
                            <div>
                                <h2 class="text-3xl font-bold text-gray-900 mb-2">Borrowing Book...</h2>
                                <p class="text-lg text-gray-600">Setting up your 1-day reading period</p>
                            </div>
                            
                            <!-- Book Info -->
                            <div class="bg-gray-50 rounded-xl p-6 inline-block">
                                <div class="flex items-center gap-4">
                                    <div class="w-16 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-book text-white text-xl"></i>
                                    </div>
                                    <div class="text-left">
                                        <h3 class="font-bold text-gray-900 text-lg">{{ $book->title }}</h3>
                                        <p class="text-gray-600">by {{ $book->author }}</p>
                                        <div class="flex items-center gap-2 mt-2">
                                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">
                                                1-Day Loan
                                            </span>
                                            <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                                                Due: <span id="due-date"></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                        <!-- Success State (Hidden initially) -->
                        <div id="success-state" class="hidden space-y-6">
                            <div class="flex justify-center">
                                <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-check text-3xl text-blue-600"></i>
                                </div>
                            </div>
                            
                            <div>
                                <h2 class="text-3xl font-bold text-gray-900 mb-2">Book Borrowed Successfully!</h2>
                                <p class="text-lg text-gray-600">Redirecting to book reader...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Auto-Submit Form -->
                    <!-- Debug: Route should be /student/books/{{ $book->id }}/borrow -->
                    <!-- Current route: {{ route('student.books.borrow', ['book' => $book->id]) }} -->
                    <form action="/student/books/{{ $book->id }}/borrow" method="POST" id="auto-borrow-form" class="hidden">
                        @csrf
                        <input type="hidden" name="borrow_days" value="1" aria-hidden="true" tabindex="-1">
                        <input type="hidden" name="auto_borrow" value="true" aria-hidden="true" tabindex="-1">
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="{{ asset('js/student-dashboard.js') }}"></script>
    @vite('resources/js/components/borrow-book.js')
</body>
</html>
