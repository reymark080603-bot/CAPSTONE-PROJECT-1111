<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Knowly Library - Student Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-100">
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.1"%3E%3Ccircle cx="7" cy="7" r="2"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-30"></div>
    
    <!-- Floating Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-20 left-20 w-32 h-32 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-20 right-20 w-24 h-24 bg-white/10 rounded-full blur-2xl animate-pulse delay-1000"></div>
        <div class="absolute top-1/2 left-10 w-16 h-16 bg-white/10 rounded-full blur-xl animate-pulse delay-500"></div>
    </div>

    <div class="relative z-10 flex min-h-screen items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-sm sm:max-w-md">
            <!-- Main Card -->
            <div class="bg-white/95 backdrop-blur-lg rounded-3xl shadow-2xl border border-white/20 p-8 transform transition-all duration-500 hover:shadow-3xl">
                <!-- Header -->
                <div class="text-center mb-8">
                    <!-- Logo -->
                    <div class="relative inline-block mb-6">
<div class="w-20 h-20 bg-emerald-600 rounded-2xl flex items-center justify-center shadow-lg transform rotate-3 transition-transform hover:rotate-6">
                            <i class="fas fa-book-open text-white text-2xl"></i>
                        </div>
                        <div class="absolute -top-2 -right-2 w-6 h-6 bg-yellow-400 rounded-full flex items-center justify-center">
                            <i class="fas fa-star text-white text-xs"></i>
                        </div>
                    </div>
                    
                    <!-- Title -->
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        Welcome to <span class="text-emerald-600">Knowly</span>
                    </h1>
                    <p class="text-gray-600 mb-4">Your Student Portal Library Account</p>
                    
                    <!-- Subtitle for Students -->
                    <div class="inline-flex items-center px-4 py-2 bg-emerald-50 rounded-full">
                        <i class="fas fa-id-card text-emerald-500 mr-2"></i>
                        <span class="text-emerald-700 font-medium text-sm">Student Portal Login</span>
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
                                <h3 class="text-sm font-semibold text-red-800 mb-2">Login Error</h3>
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

                <!-- Student Login Form -->
                <form method="POST" action="{{ url('student/login') }}" class="space-y-6">
                    @csrf

                    <!-- Library ID Input -->
                    <div class="space-y-2">
                        <label for="library_id" class="text-sm font-medium text-gray-700 flex items-center">
                            <i class="fas fa-id-card mr-2 text-emerald-500"></i>
                            Library ID
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="library_id" 
                                name="library_id" 
                                value="{{ old('library_id') }}" 
                                class="w-full px-4 py-3 pl-11 border-2 border-gray-200 rounded-xl focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all duration-300 bg-gray-50/50" 
                                placeholder="Enter your Library ID"
                                autocomplete="username"
                                required
                                autofocus
                            >
                            <i class="fas fa-id-card absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Your student portal Library ID</p>
                    </div>

                    <!-- Password Input -->
                    <div class="space-y-2">
                        <label for="password" class="text-sm font-medium text-gray-700 flex items-center">
                            <i class="fas fa-lock mr-2 text-emerald-500"></i>
                            Password
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="w-full px-4 py-3 pl-11 pr-11 border-2 border-gray-200 rounded-xl focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all duration-300 bg-gray-50/50" 
                                placeholder="Enter your password"
                                required 
                                autocomplete="current-password"
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

                    <!-- Remember Me and Forgot Password -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                id="remember" 
                                name="remember" 
                                class="w-4 h-4 text-emerald-600 border-2 border-gray-300 rounded focus:ring-emerald-500 focus:ring-2"
                            >
                            <span class="ml-2 text-sm text-gray-700">Remember me</span>
                        </label>
                        <a href="{{ url('password/reset') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 transition-colors">
                            Forgot password?
                        </a>
                    </div>

                    <!-- Login Button -->
                    <button 
                        type="submit" 
class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-4 rounded-xl focus:outline-none focus:ring-4 focus:ring-emerald-500/25 transform transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-xl"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Sign In with Portal Account
                    </button>
                </form>

            </div>
        </div>
    <!-- JavaScript for Password Toggle -->
    <script>
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const eyeIcon = document.getElementById(inputId + '-eye');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        // Form validation feedback
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input[required]');
            
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim() === '') {
                        this.classList.add('border-red-300', 'focus:border-red-500');
                        this.classList.remove('border-gray-200', 'focus:border-emerald-500');
                    } else {
                        this.classList.remove('border-red-300', 'focus:border-red-500');
                        this.classList.add('border-gray-200', 'focus:border-emerald-500');
                    }
                });
            });
        });
    </script>
</body>
</html>
