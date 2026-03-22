<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Knowly Library - Reset Password</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gradient-to-br from-teal-400 via-cyan-500 to-blue-600">
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.1"%3E%3Cpath d="M30 30c0-11.046-8.954-20-20-20s-20 8.954-20 20 8.954 20 20 20 20-8.954 20-20z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-20"></div>
    
    <!-- Floating Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-20 left-20 w-40 h-40 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-20 right-20 w-32 h-32 bg-white/10 rounded-full blur-2xl animate-pulse delay-1000"></div>
        <div class="absolute top-1/2 left-10 w-20 h-20 bg-white/10 rounded-full blur-xl animate-pulse delay-500"></div>
    </div>

    <div class="relative z-10 flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Main Card -->
            <div class="bg-white/95 backdrop-blur-lg rounded-3xl shadow-2xl border border-white/20 p-8 transform transition-all duration-500 hover:shadow-3xl">
                <!-- Header -->
                <div class="text-center mb-8">
                    <!-- Logo -->
                    <div class="relative inline-block mb-6">
                        <div class="w-20 h-20 bg-gradient-to-tr from-cyan-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg transform rotate-12 transition-transform hover:rotate-0">
                            <i class="fas fa-key text-white text-2xl"></i>
                        </div>
                        <div class="absolute -top-2 -right-2 w-6 h-6 bg-red-400 rounded-full flex items-center justify-center">
                            <i class="fas fa-exclamation text-white text-xs"></i>
                        </div>
                    </div>
                    
                    <!-- Title -->
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        Reset Password - <span class="text-cyan-600">Knowly</span>
                    </h1>
                    <p class="text-gray-600 mb-4">We'll send you a reset link</p>
                    
                    <!-- Subtitle -->
                    <div class="inline-flex items-center px-4 py-2 bg-cyan-50 rounded-full">
                        <i class="fas fa-shield-alt text-cyan-500 mr-2"></i>
                        <span class="text-cyan-700 font-medium text-sm">Password Recovery</span>
                    </div>
                </div>

                @if (session('status'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-check-circle text-green-500 text-sm"></i>
                                </div>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-semibold text-green-800 mb-1">Email Sent!</h3>
                                <p class="text-sm text-green-700">{{ session('status') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-exclamation-triangle text-red-500 text-sm"></i>
                                </div>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-semibold text-red-800 mb-2">Error</h3>
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

                <!-- Reset Password Form -->
                <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                    @csrf

                    <!-- Instructions -->
                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-info text-blue-600 text-xs"></i>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-800">
                                    Enter your email address and we'll send you a link to reset your password.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Email Input -->
                    <div class="space-y-2">
                        <label for="email" class="text-sm font-medium text-gray-700 flex items-center">
                            <i class="fas fa-envelope mr-2 text-cyan-500"></i>
                            Email Address
                        </label>
                        <div class="relative">
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="{{ old('email') }}" 
                                class="w-full px-4 py-3 pl-11 border-2 border-gray-200 rounded-xl focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 transition-all duration-300 bg-gray-50/50" 
                                placeholder="Enter your email address"
                                required
                                autofocus
                            >
                            <i class="fas fa-envelope absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>

                    <!-- Send Reset Link Button -->
                    <button 
                        type="submit" 
                        class="w-full bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-semibold py-3 px-4 rounded-xl hover:from-cyan-600 hover:to-blue-700 focus:outline-none focus:ring-4 focus:ring-cyan-500/25 transform transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-xl"
                    >
                        <i class="fas fa-paper-plane mr-2"></i>
                        Send Reset Link
                    </button>
                </form>

                <!-- Back to Login Links -->
                <div class="mt-8 text-center">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-white text-gray-500">Remember your password?</span>
                        </div>
                    </div>
                    <div class="mt-4 space-y-3">
                        <div>
                            <a href="{{ route('login') }}" class="inline-flex items-center px-6 py-2 border-2 border-emerald-500 text-emerald-600 font-medium rounded-xl hover:bg-emerald-50 transition-all duration-300">
                                <i class="fas fa-graduation-cap mr-2"></i>
                                Student Login
                            </a>
                        </div>
                        <div>
                            <a href="{{ url('staff/login') }}" class="inline-flex items-center px-6 py-2 border-2 border-purple-500 text-purple-600 font-medium rounded-xl hover:bg-purple-50 transition-all duration-300">
                                <i class="fas fa-user-tie mr-2"></i>
                                Staff Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for form validation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const emailInput = document.getElementById('email');
            
            emailInput.addEventListener('blur', function() {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (this.value && !emailPattern.test(this.value)) {
                    this.classList.add('border-red-300', 'focus:border-red-500');
                    this.classList.remove('border-gray-200', 'focus:border-cyan-500');
                } else if (this.value) {
                    this.classList.remove('border-red-300', 'focus:border-red-500');
                    this.classList.add('border-gray-200', 'focus:border-cyan-500');
                }
            });

            // Loading state on form submit
            form.addEventListener('submit', function() {
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';
            });
        });
    </script>
</body>
</html>