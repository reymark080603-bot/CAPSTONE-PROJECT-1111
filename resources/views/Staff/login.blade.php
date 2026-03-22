<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Knowly Library - Librarian Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-100">
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.1"%3E%3Crect x="11" y="11" width="4" height="4"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-30"></div>
    
    <!-- Floating Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-20 left-20 w-32 h-32 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-20 right-20 w-24 h-24 bg-white/10 rounded-full blur-2xl animate-pulse delay-1000"></div>
        <div class="absolute top-1/2 right-10 w-16 h-16 bg-white/10 rounded-full blur-xl animate-pulse delay-500"></div>
    </div>

    <div class="relative z-10 flex min-h-screen items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-sm sm:max-w-md">
            <!-- Main Card -->
            <div class="bg-white/95 backdrop-blur-lg rounded-3xl shadow-2xl border border-white/20 p-8 transform transition-all duration-500 hover:shadow-3xl">
                <!-- Header -->
                <div class="text-center mb-8">
                    <!-- Logo -->
                    <div class="relative inline-block mb-6">
<div class="w-20 h-20 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-lg transform -rotate-3 transition-transform hover:-rotate-6">
                            <i class="fas fa-user-tie text-blue-100 text-2xl"></i>
                        </div>
                        <div class="absolute -top-2 -right-2 w-6 h-6 bg-orange-400 rounded-full flex items-center justify-center">
                            <i class="fas fa-shield-alt text-white text-xs"></i>
                        </div>
                    </div>
                    
                    <!-- Title -->
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                       Librarian Portal - <span class="text-purple-600">Knowly</span>
                    </h1>
                    <p class="text-gray-600 mb-4">Administrative Access</p>
                    
                    <!-- Subtitle for Staff -->
                    <div class="inline-flex items-center px-4 py-2 bg-purple-50 rounded-full">
                        <i class="fas fa-cogs text-purple-500 mr-2"></i>
                        <span class="text-purple-700 font-medium text-sm">Librarian Login</span>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-exclamation-triangle text-red-500 text-sm"></i>
                                </div>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-semibold text-red-800 mb-2">Authentication Error</h3>
                                <ul class="text-sm text-red-700 space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li class="flex items-center">
                                            <i class="fas fa-circle text-xs mr-2 text-red-400"></i>
                                            {{ $error }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Librarian Login Form -->
                <form method="POST" action="{{ route('librarian.login.post') }}" class="space-y-6">
                    @csrf

                    <!-- Email Input -->
                    <div class="space-y-2">
                        <label for="email" class="text-sm font-medium text-gray-700 flex items-center">
                            <i class="fas fa-envelope mr-2 text-purple-500"></i>
                            Librarian Email Address
                        </label>
                        <div class="relative">
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="{{ old('email') }}" 
                                class="w-full px-4 py-3 pl-11 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all duration-300 bg-gray-50/50" 
                                placeholder="Enter your librarian email address"
                                autocomplete="email"
                                autofocus
                                required
                            >
                            <i class="fas fa-envelope absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div class="space-y-2">
                        <label for="password" class="text-sm font-medium text-gray-700 flex items-center">
                            <i class="fas fa-lock mr-2 text-purple-500"></i>
                            Password
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="w-full px-4 py-3 pl-11 pr-11 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all duration-300 bg-gray-50/50" 
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required 
                            >
                            <i class="fas fa-lock absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <button 
                                type="button" 
                                onclick="togglePassword('password')" 
                                class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                <i class="fas fa-eye" id="password-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Admin Notice -->
                    <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-6 h-6 bg-amber-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-key text-amber-600 text-xs"></i>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-amber-800">
                                    <strong class="font-semibold">Staff Access:</strong> This portal is restricted to authorized library staff members only.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Student Login Switch -->
                    <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
                        <div class="flex items-center justify-between">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-graduation-cap text-emerald-600 text-sm"></i>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-semibold text-emerald-800 mb-1">Are you a student?</p>
                                    <p class="text-xs text-emerald-700">Use Library ID to access student portal</p>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <a href="{{ route('login') }}" class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors">
                                    <i class="fas fa-arrow-right mr-1"></i>
                                    Student Login
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Remember Me and Forgot Password -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                id="remember" 
                                name="remember" 
                                class="w-4 h-4 text-purple-600 border-2 border-gray-300 rounded focus:ring-purple-500 focus:ring-2"
                            >
                            <span class="ml-2 text-sm text-gray-700">Remember me</span>
                        </label>
                        <a href="{{ url('staff/password/reset') }}" class="text-sm font-medium text-purple-600 hover:text-purple-700 transition-colors">
                            Forgot password?
                        </a>
                    </div>

                    <!-- Login Button -->
                    <button 
                        type="submit" 
class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-xl focus:outline-none focus:ring-4 focus:ring-indigo-500/25 transform transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-xl"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Sign In as Librarian
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript for Password Toggle -->
    <script>
        // Make sure the DOM is loaded before adding event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle function with error handling
            window.togglePassword = function(inputId) {
                try {
                    const passwordInput = document.getElementById(inputId);
                    const eyeIcon = document.getElementById(inputId + '-eye');
                    
                    if (!passwordInput || !eyeIcon) {
                        console.warn('Password toggle elements not found for:', inputId);
                        return;
                    }
                    
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        eyeIcon.classList.remove('fa-eye');
                        eyeIcon.classList.add('fa-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        eyeIcon.classList.remove('fa-eye-slash');
                        eyeIcon.classList.add('fa-eye');
                    }
                } catch (error) {
                    console.error('Error toggling password:', error);
                }
            };

            // Form validation feedback
            const form = document.querySelector('form');
            const inputs = form?.querySelectorAll('input[required]');
            
            if (inputs) {
                inputs.forEach(input => {
                    input.addEventListener('blur', function() {
                        if (this.value.trim() === '') {
                            this.classList.add('border-red-300');
                            this.classList.remove('border-gray-200', 'focus:border-purple-500');
                        } else {
                            this.classList.remove('border-red-300');
                            this.classList.add('border-gray-200', 'focus:border-purple-500');
                        }
                    });
                });
            }

            // Clear any error states on focus
            const errorInputs = document.querySelectorAll('.border-red-300');
            errorInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.classList.remove('border-red-300');
                });
            });
        });

        // Fallback in case DOMContentLoaded already fired
        if (document.readyState === 'loading') {
            // DOM is still loading
        } else {
            // DOM is already loaded, initialize immediately
            const event = new Event('DOMContentLoaded');
            document.dispatchEvent(event);
        }
    </script>
</body>
</html>