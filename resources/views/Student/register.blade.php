<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Knowly Library - Create Account</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-100">

    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.1"%3E%3Ccircle cx="7" cy="7" r="2"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-30"></div>

    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-20 left-20 w-32 h-32 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-20 right-20 w-24 h-24 bg-white/10 rounded-full blur-2xl animate-pulse delay-1000"></div>
        <div class="absolute top-1/2 left-10 w-16 h-16 bg-white/10 rounded-full blur-xl animate-pulse delay-500"></div>
    </div>

    <div class="relative z-10 flex min-h-screen items-center justify-center p-4 sm:p-6 py-12">
        <div class="w-full max-w-sm sm:max-w-lg">
            <div class="bg-white/95 backdrop-blur-lg rounded-3xl shadow-2xl border border-white/20 p-8 transform transition-all duration-500 hover:shadow-3xl">
                <div class="text-center mb-8">
                    <div class="relative inline-block mb-6">
                        <div class="w-20 h-20 bg-emerald-600 rounded-2xl flex items-center justify-center shadow-lg transform rotate-3 transition-transform hover:rotate-6">
                            <i class="fas fa-book-open text-white text-2xl"></i>
                        </div>
                        <div class="absolute -top-2 -right-2 w-6 h-6 bg-yellow-400 rounded-full flex items-center justify-center">
                            <i class="fas fa-star text-white text-xs"></i>
                        </div>
                    </div>

                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        Join <span class="text-emerald-600">Knowly</span>
                    </h1>
                    <p class="text-gray-600 mb-4">Create your Student Portal Library Account</p>

                    <div class="inline-flex items-center px-4 py-2 bg-emerald-50 rounded-full">
                        <i class="fas fa-user-plus text-emerald-500 mr-2"></i>
                        <span class="text-emerald-700 font-medium text-sm">Student Registration</span>
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
                                <h3 class="text-sm font-semibold text-red-800 mb-2">Please fix the following errors:</h3>
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

                <div class="mb-6">
                    <div class="flex items-center justify-center gap-3 text-sm font-medium">
                        <div id="step-indicator-1" class="flex items-center gap-2 text-emerald-700">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-white">1</span>
                            <span>Account Info</span>
                        </div>
                        <div class="h-px w-10 bg-gray-300"></div>
                        <div id="step-indicator-2" class="flex items-center gap-2 text-gray-400">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-gray-600">2</span>
                            <span>Academic Info</span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('register') }}" class="space-y-5" id="register-form">
                    @csrf

                    <div id="form-step-1" class="space-y-5">
                        <div class="space-y-2">
                            <label for="name" class="text-sm font-medium text-gray-700 flex items-center">
                                <i class="fas fa-user mr-2 text-emerald-500"></i>
                                Full Name
                            </label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name') }}"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all duration-300 bg-gray-50/50 @error('name') border-red-300 @enderror"
                                placeholder="Enter your full name"
                                required
                                autofocus
                            >
                            @error('name')
                                <p class="text-xs text-red-500 flex items-center mt-1">
                                    <i class="fas fa-circle-exclamation mr-1"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="email" class="text-sm font-medium text-gray-700 flex items-center">
                                <i class="fas fa-envelope mr-2 text-emerald-500"></i>
                                Student Portal Email
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all duration-300 bg-gray-50/50 @error('email') border-red-300 @enderror"
                                placeholder="yourname@jhcsc.edu.ph"
                                required
                                autocomplete="email"
                            >
                            <p class="text-xs text-gray-500 mt-1">Must be your official JHCSC student portal email</p>
                            @error('email')
                                <p class="text-xs text-red-500 flex items-center mt-1">
                                    <i class="fas fa-circle-exclamation mr-1"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="gender" class="text-sm font-medium text-gray-700 flex items-center">
                                <i class="fas fa-venus-mars mr-2 text-emerald-500"></i>
                                Gender
                            </label>
                            <div class="relative">
                                <select
                                    id="gender"
                                    name="gender"
                                    class="w-full px-4 py-3 pr-11 border-2 border-gray-200 rounded-xl focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all duration-300 bg-gray-50/50 appearance-none @error('gender') border-red-300 @enderror"
                                    required
                                >
                                    <option value="" disabled {{ old('gender') ? '' : 'selected' }}>Select your gender</option>
                                    <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                                    <option value="Prefer not to say" {{ old('gender') == 'Prefer not to say' ? 'selected' : '' }}>Prefer not to say</option>
                                </select>
                                <span class="pointer-events-none absolute right-4 top-1/2 h-2.5 w-2.5 -translate-y-1/2 rotate-45 border-b-2 border-r-2 border-gray-400"></span>
                            </div>
                            @error('gender')
                                <p class="text-xs text-red-500 flex items-center mt-1">
                                    <i class="fas fa-circle-exclamation mr-1"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <button
                            type="button"
                            id="next-step"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-4 rounded-xl focus:outline-none focus:ring-4 focus:ring-emerald-500/25 transform transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-xl mt-2"
                        >
                            Next Step
                            <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>

                    <div id="form-step-2" class="space-y-5 hidden">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label for="course" class="text-sm font-medium text-gray-700 flex items-center">
                                    <i class="fas fa-graduation-cap mr-2 text-emerald-500"></i>
                                    Program
                                </label>
                                <div class="relative">
                                    <select
                                        id="course"
                                        name="course"
                                        class="w-full px-4 py-3 pr-11 border-2 border-gray-200 rounded-xl focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all duration-300 bg-gray-50/50 appearance-none @error('course') border-red-300 @enderror"
                                        required
                                    >
                                        <option value="" disabled {{ old('course') ? '' : 'selected' }}>Select your program</option>
                                        <option value="BSE" {{ old('course') == 'BSE' ? 'selected' : '' }}>BSE</option>
                                        <option value="BSHM" {{ old('course') == 'BSHM' ? 'selected' : '' }}>BSHM</option>
                                        <option value="BSIT" {{ old('course') == 'BSIT' ? 'selected' : '' }}>BSIT</option>
                                        <option value="BSN" {{ old('course') == 'BSN' ? 'selected' : '' }}>BSN</option>
                                        <option value="BSTM" {{ old('course') == 'BSTM' ? 'selected' : '' }}>BSTM</option>
                                    </select>
                                    <span class="pointer-events-none absolute right-4 top-1/2 h-2.5 w-2.5 -translate-y-1/2 rotate-45 border-b-2 border-r-2 border-gray-400"></span>
                                </div>
                                @error('course')
                                    <p class="text-xs text-red-500 flex items-center mt-1">
                                        <i class="fas fa-circle-exclamation mr-1"></i> {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label for="year_level" class="text-sm font-medium text-gray-700 flex items-center">
                                    <i class="fas fa-layer-group mr-2 text-emerald-500"></i>
                                    Year Level
                                </label>
                                <div class="relative">
                                    <select
                                        id="year_level"
                                        name="year_level"
                                        class="w-full px-4 py-3 pr-11 border-2 border-gray-200 rounded-xl focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all duration-300 bg-gray-50/50 appearance-none @error('year_level') border-red-300 @enderror"
                                        required
                                    >
                                        <option value="" disabled {{ old('year_level') ? '' : 'selected' }}>Select year</option>
                                        <option value="1st Year" {{ old('year_level') == '1st Year' ? 'selected' : '' }}>1st Year</option>
                                        <option value="2nd Year" {{ old('year_level') == '2nd Year' ? 'selected' : '' }}>2nd Year</option>
                                        <option value="3rd Year" {{ old('year_level') == '3rd Year' ? 'selected' : '' }}>3rd Year</option>
                                        <option value="4th Year" {{ old('year_level') == '4th Year' ? 'selected' : '' }}>4th Year</option>
                                    </select>
                                    <span class="pointer-events-none absolute right-4 top-1/2 h-2.5 w-2.5 -translate-y-1/2 rotate-45 border-b-2 border-r-2 border-gray-400"></span>
                                </div>
                                @error('year_level')
                                    <p class="text-xs text-red-500 flex items-center mt-1">
                                        <i class="fas fa-circle-exclamation mr-1"></i> {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label for="campus" class="text-sm font-medium text-gray-700 flex items-center">
                                <i class="fas fa-university mr-2 text-emerald-500"></i>
                                Campus
                            </label>
                            <div class="relative">
                                <select
                                    id="campus"
                                    name="campus"
                                    class="w-full px-4 py-3 pr-11 border-2 border-gray-200 rounded-xl focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all duration-300 bg-gray-50/50 appearance-none @error('campus') border-red-300 @enderror"
                                    required
                                >
                                    <option value="" disabled {{ old('campus') ? '' : 'selected' }}>Select your campus</option>
                                    <option value="Main Campus" {{ old('campus') == 'Main Campus' ? 'selected' : '' }}>Main Campus</option>
                                    <option value="Pagadian Campus" {{ old('campus') == 'Pagadian Campus' ? 'selected' : '' }}>Pagadian Campus</option>
                                    <option value="Dumingag Campus" {{ old('campus') == 'Dumingag Campus' ? 'selected' : '' }}>Dumingag Campus</option>
                                    <option value="Canuto MS Enerio Campus" {{ old('campus') == 'Canuto MS Enerio Campus' ? 'selected' : '' }}>Canuto MS Enerio Campus</option>
                                </select>
                                <span class="pointer-events-none absolute right-4 top-1/2 h-2.5 w-2.5 -translate-y-1/2 rotate-45 border-b-2 border-r-2 border-gray-400"></span>
                            </div>
                            @error('campus')
                                <p class="text-xs text-red-500 flex items-center mt-1">
                                    <i class="fas fa-circle-exclamation mr-1"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>

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
                                    class="w-full px-4 py-3 pr-11 border-2 border-gray-200 rounded-xl focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all duration-300 bg-gray-50/50 @error('password') border-red-300 @enderror"
                                    placeholder="Create a password (min. 8 characters)"
                                    required
                                    autocomplete="new-password"
                                >
                                <button
                                    type="button"
                                    onclick="togglePassword('password')"
                                    class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                                >
                                    <i class="fas fa-eye" id="password-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <p class="text-xs text-red-500 flex items-center mt-1">
                                    <i class="fas fa-circle-exclamation mr-1"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="password_confirmation" class="text-sm font-medium text-gray-700 flex items-center">
                                <i class="fas fa-lock mr-2 text-emerald-500"></i>
                                Confirm Password
                            </label>
                            <div class="relative">
                                <input
                                    type="password"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    class="w-full px-4 py-3 pr-11 border-2 border-gray-200 rounded-xl focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all duration-300 bg-gray-50/50"
                                    placeholder="Re-enter your password"
                                    required
                                    autocomplete="new-password"
                                >
                                <button
                                    type="button"
                                    onclick="togglePassword('password_confirmation')"
                                    class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                                >
                                    <i class="fas fa-eye" id="password_confirmation-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button
                                type="button"
                                id="prev-step"
                                class="w-1/3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-4 rounded-xl focus:outline-none focus:ring-4 focus:ring-gray-200 transition-all duration-300"
                            >
                                Back
                            </button>

                            <button
                                type="submit"
                                class="w-2/3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-4 rounded-xl focus:outline-none focus:ring-4 focus:ring-emerald-500/25 transform transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-xl"
                            >
                                <i class="fas fa-user-plus mr-2"></i>
                                Create Account
                            </button>
                        </div>
                    </div>

                    <p class="text-center text-sm text-gray-600 pt-2">
                        Already have an account?
                        <a href="{{ route('login') }}" class="font-medium text-emerald-600 hover:text-emerald-700 transition-colors">
                            Sign in here
                        </a>
                    </p>
                </form>
            </div>
        </div>
    </div>

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

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('register-form');
            const inputs = form.querySelectorAll('input[required], select[required]');
            const step1 = document.getElementById('form-step-1');
            const step2 = document.getElementById('form-step-2');
            const nextButton = document.getElementById('next-step');
            const prevButton = document.getElementById('prev-step');
            const indicator1 = document.getElementById('step-indicator-1');
            const indicator2 = document.getElementById('step-indicator-2');
            const indicator1Circle = indicator1.querySelector('span');
            const indicator2Circle = indicator2.querySelector('span');

            function showStep(stepNumber) {
                const isStepOne = stepNumber === 1;

                step1.classList.toggle('hidden', !isStepOne);
                step2.classList.toggle('hidden', isStepOne);

                indicator1.className = 'flex items-center gap-2 text-emerald-700';
                indicator1Circle.className = isStepOne
                    ? 'flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-white'
                    : 'flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-700';

                indicator2.className = isStepOne
                    ? 'flex items-center gap-2 text-gray-400'
                    : 'flex items-center gap-2 text-emerald-700';
                indicator2Circle.className = isStepOne
                    ? 'flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-gray-600'
                    : 'flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-white';
            }

            nextButton.addEventListener('click', function () {
                const stepOneFields = [
                    document.getElementById('name'),
                    document.getElementById('email'),
                    document.getElementById('gender'),
                ];

                const isValid = stepOneFields.every(field => field.reportValidity());

                if (isValid) {
                    showStep(2);
                }
            });

            prevButton.addEventListener('click', function () {
                showStep(1);
            });

            if (@json($errors->has('course') || $errors->has('year_level') || $errors->has('campus') || $errors->has('password') || $errors->has('password_confirmation'))) {
                showStep(2);
            } else {
                showStep(1);
            }

            inputs.forEach(input => {
                input.addEventListener('blur', function () {
                    if (this.value.trim() === '') {
                        this.classList.add('border-red-300');
                        this.classList.remove('border-gray-200');
                    } else {
                        this.classList.remove('border-red-300');
                        this.classList.add('border-gray-200');
                    }
                });
            });
        });
    </script>
</body>
</html>
