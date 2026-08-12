<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Knowly</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/student-dashboard.css') }}?v={{ filemtime(public_path('css/student-dashboard.css')) }}" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 min-h-screen homepage-dashboard">
    <!-- Header - Matches Homepage Exact Design -->
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
                            <span class="font-semibold text-sm text-green-700">{{ strtoupper(substr($user->firstname ?? '', 0, 1)) }}{{ strtoupper(substr($user->lastname ?? '', 0, 1)) }}</span>
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
        
        <!-- Sidebar Navigation -->
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
                    <a href="{{ route('student.profile') }}" class="sidebar-link active flex items-center space-x-3 text-white px-4 py-3 rounded-lg transition-all bg-green-600">
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
        <div class="main-content flex-1 p-4 sm:p-6 md:p-8 ml-0 md:ml-0">
            <div class="max-w-6xl mx-auto">
                <!-- Header Section -->
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 flex items-center">
                            <i class="fas fa-user-circle text-green-600 mr-3"></i>
                            Account Settings
                        </h1>
                        <p class="text-sm text-gray-600 mt-1">Manage your student profile information, academic details, and security.</p>
                    </div>
                </div>

                <!-- Alerts -->
                @if(session('success'))
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-r-xl shadow-sm flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 text-lg mr-3"></i>
                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800"><i class="fas fa-times"></i></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-xl shadow-sm">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-exclamation-triangle text-red-500 text-lg mr-3"></i>
                            <p class="text-sm font-semibold text-red-800">Please fix the following errors:</p>
                        </div>
                        <ul class="list-disc list-inside text-xs text-red-700 space-y-1 pl-4">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php
                    $courseText = '';
                    if (is_object($user->course)) {
                        $courseText = $user->course->code ?? $user->course->name ?? '';
                    } else {
                        $courseText = (string)($user->course ?? '');
                    }
                @endphp

                <!-- 2-Column Grid Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Left Side: Profile Summary Card & Quick Info -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Profile Card -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 text-center">
                            <h2 class="text-xl font-bold text-gray-900">{{ $user->firstname }} {{ $user->mi ? $user->mi . '.' : '' }} {{ $user->lastname }}</h2>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $user->email }}</p>

                            <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap items-center justify-center gap-1.5">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    <i class="fas fa-graduation-cap mr-1.5"></i> {{ $courseText ?: 'No Program' }}
                                </span>
                                @if($user->year)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                        <i class="fas fa-layer-group mr-1.5"></i> {{ $user->year }}
                                    </span>
                                @endif
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-100 text-left space-y-3 text-sm">
                                <div class="flex items-center justify-between text-gray-600">
                                    <span class="text-xs font-medium text-gray-500 flex items-center"><i class="fas fa-id-card mr-2 text-green-600"></i> Library ID</span>
                                    <span class="font-mono text-xs font-bold text-gray-900 bg-gray-100 px-2 py-1 rounded-md">{{ $user->library_id ?: 'N/A' }}</span>
                                </div>
                                <div class="flex items-center justify-between text-gray-600">
                                    <span class="text-xs font-medium text-gray-500 flex items-center"><i class="fas fa-university mr-2 text-green-600"></i> Campus</span>
                                    <span class="text-xs font-medium text-gray-900">{{ $user->campus ?: 'Main Campus' }}</span>
                                </div>
                                <div class="flex items-center justify-between text-gray-600">
                                    <span class="text-xs font-medium text-gray-500 flex items-center"><i class="fas fa-venus-mars mr-2 text-green-600"></i> Gender</span>
                                    <span class="text-xs font-medium text-gray-900">{{ $user->gender ?: 'Not specified' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- System Security Tip Card -->
                        <div class="bg-gradient-to-br from-emerald-50 to-green-50 rounded-2xl p-5 border border-green-100">
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-green-600 text-white flex items-center justify-center flex-shrink-0 mt-0.5 shadow-sm">
                                    <i class="fas fa-shield-alt text-sm"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900">Account Security Tip</h4>
                                    <p class="text-xs text-gray-600 mt-1 leading-relaxed">Keep your name and academic program details accurate to ensure smooth library transactions and borrow validation.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Form Sections -->
                    <div class="lg:col-span-2 space-y-6">
                        <form action="{{ route('student.profile.update') }}" method="POST" class="space-y-6">
                            @csrf

                            <!-- Card 1: Personal Info -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                                <div class="flex items-center space-x-3 pb-4 mb-4 border-b border-gray-100">
                                    <div class="w-9 h-9 rounded-xl bg-green-100 text-green-700 flex items-center justify-center font-bold">
                                        <i class="fas fa-user text-sm"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold text-gray-900">Personal Information</h3>
                                        <p class="text-xs text-gray-500">Update your legal name details</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label for="firstname" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">First Name <span class="text-red-500">*</span></label>
                                        <input type="text" id="firstname" name="firstname" value="{{ old('firstname', $user->firstname) }}" required class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all text-sm">
                                    </div>
                                    <div>
                                        <label for="lastname" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Last Name <span class="text-red-500">*</span></label>
                                        <input type="text" id="lastname" name="lastname" value="{{ old('lastname', $user->lastname) }}" required class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all text-sm">
                                    </div>
                                    <div>
                                        <label for="mi" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Middle Initial</label>
                                        <input type="text" id="mi" name="mi" maxlength="10" value="{{ old('mi', $user->mi) }}" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all text-sm" placeholder="e.g. A">
                                    </div>
                                </div>
                            </div>

                            <!-- Card 2: Academic & Campus Info -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                                <div class="flex items-center space-x-3 pb-4 mb-4 border-b border-gray-100">
                                    <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold">
                                        <i class="fas fa-graduation-cap text-sm"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold text-gray-900">Academic & Campus Details</h3>
                                        <p class="text-xs text-gray-500">Your registered program and campus location</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="course" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Program / Course</label>
                                        <select id="course" name="course" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all text-sm">
                                            <option value="">Select Program</option>
                                            <option value="BSE" {{ old('course', $courseText) === 'BSE' ? 'selected' : '' }}>BSE - Bachelor of Secondary Education</option>
                                            <option value="BSHM" {{ old('course', $courseText) === 'BSHM' ? 'selected' : '' }}>BSHM - Bachelor of Science in Hospitality Management</option>
                                            <option value="BSIT" {{ old('course', $courseText) === 'BSIT' ? 'selected' : '' }}>BSIT - Bachelor of Science in Information Technology</option>
                                            <option value="BSN" {{ old('course', $courseText) === 'BSN' ? 'selected' : '' }}>BSN - Bachelor of Science in Nursing</option>
                                            <option value="BSTM" {{ old('course', $courseText) === 'BSTM' ? 'selected' : '' }}>BSTM - Bachelor of Science in Tourism Management</option>
                                            <option value="Visitor / Guest" {{ old('course', $courseText) === 'Visitor / Guest' ? 'selected' : '' }}>Visitor / Guest Researcher</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="year" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Year Level</label>
                                        <select id="year" name="year" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all text-sm">
                                            <option value="">Select Year Level</option>
                                            <option value="1st Year" {{ old('year', $user->year) === '1st Year' ? 'selected' : '' }}>1st Year</option>
                                            <option value="2nd Year" {{ old('year', $user->year) === '2nd Year' ? 'selected' : '' }}>2nd Year</option>
                                            <option value="3rd Year" {{ old('year', $user->year) === '3rd Year' ? 'selected' : '' }}>3rd Year</option>
                                            <option value="4th Year" {{ old('year', $user->year) === '4th Year' ? 'selected' : '' }}>4th Year</option>
                                            <option value="N/A (Visitor)" {{ old('year', $user->year) === 'N/A (Visitor)' ? 'selected' : '' }}>N/A (Visitor / Guest)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="campus" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Campus</label>
                                        <input type="text" id="campus" name="campus" value="{{ old('campus', $user->campus) }}" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all text-sm" placeholder="e.g. Main Campus">
                                    </div>

                                    <div>
                                        <label for="gender" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Gender</label>
                                        <select id="gender" name="gender" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all text-sm">
                                            <option value="">Select Gender</option>
                                            <option value="Male" {{ old('gender', $user->gender) === 'Male' ? 'selected' : '' }}>Male</option>
                                            <option value="Female" {{ old('gender', $user->gender) === 'Female' ? 'selected' : '' }}>Female</option>
                                            <option value="Other" {{ old('gender', $user->gender) === 'Other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 3: Email Address (Read Only) -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                                <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-100">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                                            <i class="fas fa-envelope text-sm"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-base font-bold text-gray-900">Email Address</h3>
                                            <p class="text-xs text-gray-500">Your portal contact email address</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fas fa-lock text-xs mr-1"></i> Read-only
                                    </span>
                                </div>

                                <div>
                                    <div class="relative">
                                        <input type="email" id="email_display" value="{{ $user->email }}" disabled readonly class="w-full px-3.5 py-2.5 pl-10 border border-gray-200 bg-gray-50 text-gray-600 rounded-xl cursor-not-allowed text-sm">
                                        <i class="fas fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2 flex items-center">
                                        <i class="fas fa-info-circle mr-1.5 text-amber-500"></i>
                                        Your email address is managed by the library administration and cannot be changed here.
                                    </p>
                                </div>
                            </div>

                            <!-- Card 4: Security & Password Update -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                                <div class="flex items-center space-x-3 pb-4 mb-4 border-b border-gray-100">
                                    <div class="w-9 h-9 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold">
                                        <i class="fas fa-key text-sm"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold text-gray-900">Security & Password Update</h3>
                                        <p class="text-xs text-gray-500">Leave blank if you don't want to change your password</p>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <label for="current_password" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Current Password</label>
                                        <input type="password" id="current_password" name="current_password" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all text-sm" placeholder="Enter current password to verify changes">
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label for="new_password" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">New Password</label>
                                            <input type="password" id="new_password" name="new_password" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all text-sm" placeholder="At least 8 characters">
                                        </div>

                                        <div>
                                            <label for="new_password_confirmation" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Confirm New Password</label>
                                            <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all text-sm" placeholder="Confirm new password">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Action Buttons -->
                            <div class="flex items-center justify-end space-x-3 pt-2">
                                <a href="{{ route('dashboard') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors text-sm font-semibold">Cancel</a>
                                <button type="submit" class="px-7 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl shadow-md hover:shadow-lg transition-all text-sm font-semibold flex items-center">
                                    <i class="fas fa-save mr-2"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
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
</body>
</html>
