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
<body class="bg-gray-50 min-h-screen homepage-dashboard">
    <!-- Header -->
    <div class="header sidebar-expanded bg-green-600 shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 w-full">
            <div class="flex items-center space-x-4 flex-shrink-0">
                <!-- Sidebar Toggle Button -->
                <button id="sidebar-toggle" class="sidebar-toggle text-white hover:bg-white/10 p-2 rounded-lg transition-all">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                
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
                <div class="flex items-center space-x-3">
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
        <div class="main-content flex-1 p-0 ml-0 md:ml-0 h-full">
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
                                {{ $book->resource_type === 'e_journal' ? 'E-Journal' : ($book->resource_type === 'thesis' ? 'Thesis' : 'Book') }}
                            </span>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row flex-wrap justify-center gap-3 sm:gap-4 mb-8 w-full sm:w-auto">
                            @if(!$book->user_has_borrowed)
                                <button type="button" class="btn-borrow w-full sm:w-auto justify-center px-6 sm:px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors flex items-center text-base sm:text-lg" 
                                        data-book-id="{{ $book->id }}" 
                                        data-book-title="{{ $book->title }}"
                                        data-borrow-days="{{ $book->borrow_days ?? 5 }}">
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

                        <!-- Summary & Overview Section -->
                        <div class="w-full mt-8 pt-6 border-t border-gray-200/80 text-left">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-xl lg:text-2xl font-bold text-gray-900 flex items-center gap-2">
                                    <i class="fas fa-file-alt text-blue-600"></i>
                                    <span>Summary & Overview</span>
                                </h2>
                                <span class="px-3 py-1 bg-blue-50 text-blue-700 text-xs font-semibold rounded-full border border-blue-100 uppercase tracking-wide">
                                    {{ $book->category ?? ($book->resource_type ?? 'Resource') }}
                                </span>
                            </div>

                            <div class="bg-gradient-to-br from-gray-50 to-blue-50/40 rounded-xl p-5 sm:p-6 border border-gray-200/80 shadow-sm relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-32 h-32 bg-blue-100/30 rounded-full blur-2xl pointer-events-none"></div>

                                @if(!empty(trim($book->description ?? '')))
                                    <p class="text-gray-700 leading-relaxed text-sm sm:text-base whitespace-pre-line relative z-10">{{ $book->description }}</p>
                                @else
                                    <p class="text-gray-700 leading-relaxed text-sm sm:text-base relative z-10">
                                        <span class="font-semibold text-gray-900">"{{ $book->title }}"</span> is an academic {{ strtolower(str_replace('_', ' ', $book->resource_type ?? 'resource')) }} authored by <span class="font-semibold text-gray-900">{{ $book->author ?? 'the designated author' }}</span>.
                                        @if($book->published_year) Published in <span class="font-semibold text-gray-900">{{ $book->published_year }}</span>.@endif
                                        @if($book->course && $book->course !== 'All') It is recommended for <span class="font-semibold text-blue-800">{{ $book->course }}</span> students.@endif
                                        Available for online reading and borrowing via Knowly Library with up to <span class="font-semibold text-blue-800">{{ $book->borrow_days ?? 5 }} days</span> borrowing duration.
                                    </p>
                                @endif

                                <!-- Quick Metadata Tags -->
                                <div class="mt-4 pt-4 border-t border-gray-200/60 flex flex-wrap gap-2 text-xs text-gray-600 relative z-10">
                                    @if($book->subcategory)
                                        <span class="inline-flex items-center gap-1.5 bg-white px-3 py-1.5 rounded-lg border border-gray-200 shadow-2xs font-medium text-gray-700">
                                            <i class="fas fa-bookmark text-blue-500"></i> {{ $book->subcategory }}
                                        </span>
                                    @endif
                                    @if($book->language)
                                        <span class="inline-flex items-center gap-1.5 bg-white px-3 py-1.5 rounded-lg border border-gray-200 shadow-2xs font-medium text-gray-700">
                                            <i class="fas fa-globe text-green-500"></i> {{ $book->language }}
                                        </span>
                                    @endif
                                    <span class="inline-flex items-center gap-1.5 bg-white px-3 py-1.5 rounded-lg border border-gray-200 shadow-2xs font-medium text-gray-700">
                                        <i class="fas fa-clock text-amber-500"></i> Max {{ $book->borrow_days ?? 5 }} Days Borrowing
                                    </span>
                                </div>
                            </div>
                        </div>

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

    <!-- Borrow Duration Selection Popup Modal -->
    <div id="borrowPopup" class="hidden fixed inset-0 flex items-center justify-center bg-black/60 backdrop-blur-xs z-[9999] p-4 transition-all duration-300">
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
                    <p class="text-lg font-semibold text-gray-900 truncate" id="borrowPopupTitle">{{ $book->title }}</p>
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
                <button id="borrowPopupCancel" type="button" class="flex-1 px-4 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-all font-medium text-sm" onclick="if(window.__studentDashboard) window.__studentDashboard.hideBorrowPopup()">
                    Cancel
                </button>
                <button id="borrowPopupConfirm" type="button" class="flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all font-medium text-sm shadow-md hover:shadow-lg" onclick="if(window.__studentDashboard) window.__studentDashboard.confirmBorrow()">
                    <span id="borrowPopupConfirmText">Confirm Borrow</span>
                </button>
            </div>
            
            <p class="text-xs text-gray-500 text-center mt-4">
                Manage your loans in <a href="{{ route('student.loans') }}" class="text-blue-600 hover:underline">Loan Books</a>
            </p>
        </div>
    </div>

    <!-- Self-Contained Direct Script for Book Details Borrow Duration Selection Modal -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const borrowPopup = document.getElementById('borrowPopup');
        const borrowPopupCard = document.getElementById('borrowPopupCard');
        const borrowPopupTitle = document.getElementById('borrowPopupTitle');
        const borrowDurationSelect = document.getElementById('borrowDurationSelect');
        const borrowLimitNote = document.getElementById('borrowLimitNote');
        const borrowPopupCancel = document.getElementById('borrowPopupCancel');
        const borrowPopupConfirm = document.getElementById('borrowPopupConfirm');
        const borrowPopupConfirmText = document.getElementById('borrowPopupConfirmText');

        let currentBookIdToBorrow = null;

        document.querySelectorAll('.btn-borrow').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const bookId = this.dataset.bookId || '{{ $book->id }}';
                const bookTitle = this.dataset.bookTitle || @json($book->title);
                const maxDays = parseInt(this.dataset.borrowDays || '{{ $book->borrow_days ?? 5 }}', 10);

                currentBookIdToBorrow = bookId;

                if (borrowPopupTitle) {
                    borrowPopupTitle.textContent = bookTitle.toUpperCase();
                }

                if (borrowDurationSelect) {
                    let optionsHtml = '';
                    const limit = Math.max(1, maxDays);
                    for (let i = 1; i <= limit; i++) {
                        const label = i === 1 ? '1 Day' : i + ' Days';
                        optionsHtml += '<option value="' + i + '">' + label + '</option>';
                    }
                    borrowDurationSelect.innerHTML = optionsHtml;
                    borrowDurationSelect.value = '1';
                }

                if (borrowLimitNote) {
                    borrowLimitNote.textContent = 'Select duration up to the librarian\'s set limit for this resource (' + maxDays + ' day(s)).';
                }

                if (borrowPopup && borrowPopupCard) {
                    borrowPopup.classList.remove('hidden');
                    setTimeout(function() {
                        borrowPopupCard.classList.remove('scale-95', 'opacity-0');
                        borrowPopupCard.classList.add('scale-100', 'opacity-100');
                    }, 10);
                    document.body.style.overflow = 'hidden';
                }
            });
        });

        function closePopup() {
            if (borrowPopup && borrowPopupCard) {
                borrowPopupCard.classList.add('scale-95', 'opacity-0');
                borrowPopupCard.classList.remove('scale-100', 'opacity-100');
                setTimeout(function() {
                    borrowPopup.classList.add('hidden');
                    document.body.style.overflow = '';
                }, 200);
            }
            currentBookIdToBorrow = null;
        }

        if (borrowPopupCancel) {
            borrowPopupCancel.addEventListener('click', function(e) {
                e.preventDefault();
                closePopup();
            });
        }

        if (borrowPopup) {
            borrowPopup.addEventListener('click', function(e) {
                if (e.target === borrowPopup) {
                    closePopup();
                }
            });
        }

        function showToast(message, type) {
            document.querySelectorAll('.knowly-toast-notification').forEach(t => t.remove());

            const isError = type === 'error';
            const isWarning = type === 'warning';
            const toastTypeClass = isError ? 'knowly-toast-error' : (isWarning ? 'knowly-toast-warning' : 'knowly-toast-success');

            const notification = document.createElement('div');
            notification.className = `knowly-toast-notification ${toastTypeClass}`;

            const iconBg = isError ? 'background-color: #fee2e2; color: #dc2626;' : (isWarning ? 'background-color: #fef3c7; color: #d97706;' : 'background-color: #dcfce7; color: #16a34a;');
            const iconClass = isError ? 'fa-exclamation-circle' : (isWarning ? 'fa-exclamation-triangle' : 'fa-check-circle');
            const subtext = isError ? 'Please check your action or try again.' : (isWarning ? 'Note your borrowing status.' : 'You can now read this resource in your library.');

            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 9999px; ${iconBg} display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas ${iconClass}" style="font-size: 16px;"></i>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <p style="font-weight: 600; font-size: 14px; color: #111827; margin: 0; line-height: 1.3;">${message}</p>
                        <p style="font-size: 12px; color: #6b7280; margin-top: 2px; margin-bottom: 0;">${subtext}</p>
                    </div>
                    <button type="button" style="background: none; border: none; color: #9ca3af; cursor: pointer; padding: 4px; font-size: 12px;" onclick="this.closest('.knowly-toast-notification').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            document.body.appendChild(notification);

            requestAnimationFrame(() => {
                notification.classList.add('knowly-toast-show');
            });

            setTimeout(() => {
                notification.classList.remove('knowly-toast-show');
                setTimeout(() => {
                    if (notification.parentNode) notification.remove();
                }, 400);
            }, 4000);
        }

        // Auto display persisted toast upon reload
        try {
            const storedToast = sessionStorage.getItem('knowly_toast');
            if (storedToast) {
                sessionStorage.removeItem('knowly_toast');
                const tData = JSON.parse(storedToast);
                showToast(tData.message, tData.type || 'success');
            }
        } catch (e) {}

        if (borrowPopupConfirm) {
            borrowPopupConfirm.addEventListener('click', function(e) {
                e.preventDefault();
                if (!currentBookIdToBorrow) return;

                const selectedDays = borrowDurationSelect ? parseInt(borrowDurationSelect.value || 1, 10) : 1;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                borrowPopupConfirm.disabled = true;
                if (borrowPopupConfirmText) borrowPopupConfirmText.textContent = 'Borrowing...';

                fetch('/student/books/' + currentBookIdToBorrow + '/borrow', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ borrow_days: selectedDays })
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        closePopup();
                        const msg = data.message || ('Book borrowed successfully for ' + selectedDays + ' day(s)!');
                        try {
                            sessionStorage.setItem('knowly_toast', JSON.stringify({ message: msg, type: 'success' }));
                        } catch (e) {}
                        window.location.reload();
                    } else {
                        showToast(data.message || 'Failed to borrow book', 'error');
                        borrowPopupConfirm.disabled = false;
                        if (borrowPopupConfirmText) borrowPopupConfirmText.textContent = 'Confirm Borrow';
                    }
                })
                .catch(function(err) {
                    showToast('An error occurred. Please try again.', 'error');
                    borrowPopupConfirm.disabled = false;
                    if (borrowPopupConfirmText) borrowPopupConfirmText.textContent = 'Confirm Borrow';
                });
            });
        }
    });
    </script>
</body>
</html>
