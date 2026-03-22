<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        return view('Student.login');
    }

    /**
     * Handle login request - Updated for email + password only (per client requirements)
     */
    public function login(Request $request)
    {
        try {
            // Validate input - email and password only
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput($request->except('password'));
            }

            // Log login attempt
            Log::info('Login Attempt', [
                'request_data' => $request->all(),
                'credentials' => [
                    'email' => $request->email,
                    'password_provided' => !empty($request->password)
                ]
            ]);

            // Find user by email only
            Log::info('Attempting email-only login', ['email' => $request->email]);
            $user = User::where('email', $request->email)->first();

            Log::info('User found', [
                'user_id' => $user ? $user->id : null,
                'user_email' => $user ? $user->email : null,
                'user_exists' => !is_null($user),
                'password_check' => $user ? Hash::check($request->password, $user->password) : false
            ]);

            if ($user && Hash::check($request->password, $user->password)) {
                // Block deactivated students
                if ($user->isStudent() && empty($user->email_verified_at)) {
                    Log::warning('Login blocked - deactivated student', [
                        'user_id' => $user->id,
                        'email' => $user->email
                    ]);
                    return redirect()->back()
                        ->withErrors(['email' => 'Your account is deactivated. Please contact the librarian.'])
                        ->withInput($request->except('password'));
                }

                // Login using appropriate guard
                if ($user->isLibrarian()) {
                    Log::info('Librarian login successful', ['user_id' => $user->id]);
                    Auth::guard('librarian')->login($user, $request->filled('remember'));
                    $request->session()->regenerate();
                    return redirect(route('librarian.dashboard'));
                } elseif ($user->isAdmin()) {
                    Log::info('Admin login successful', ['user_id' => $user->id]);
                    Auth::login($user, $request->filled('remember'));
                    $request->session()->regenerate();
                    return redirect()->intended('/admin/dashboard');
                } elseif ($user->isStudent()) {
                    Log::info('Student login successful', ['user_id' => $user->id]);
                    Auth::guard('student')->login($user, $request->filled('remember'));
                    $request->session()->regenerate();
                    return redirect()->route('student.dashboard');
                }
            }

            // Login failed
            Log::warning('Login failed', [
                'email' => $request->email,
                'reason' => 'User not found or password mismatch'
            ]);
            
            return redirect()->back()
                ->withErrors([
                    'email' => 'The provided credentials do not match our records.',
                ])
                ->withInput($request->except('password'));

        } catch (\Exception $e) {
            Log::error('Login Error', [
                'request_data' => $request->all(),
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withErrors([
                    'email' => 'Login failed: ' . $e->getMessage() . '. Please try again.',
                ])
                ->withInput($request->except('password'));
        }
    }

    /**
     * Handle student login request (Library ID + Password only).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function studentLogin(Request $request)
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'library_id' => 'required|string',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput($request->except('password'));
            }

            // Log student login attempt
            Log::info('Student Login Attempt', [
                'library_id' => $request->library_id,
                'password_provided' => !empty($request->password),
                'request_all' => $request->all()
            ]);

            // Find student by library_id
            Log::info('Looking for student with library_id', ['library_id' => $request->library_id]);
            
            $user = User::where('library_id', $request->library_id)->first();
            
            Log::info('User found by library_id', [
                'user_found' => !is_null($user),
                'user_id' => $user ? $user->id : null,
                'user_email' => $user ? $user->email : null,
                'role_id' => $user ? $user->role_id : null,
                'role_name' => $user ? ($user->role ? $user->role->name : 'no role') : 'no user'
            ]);

            // Check if user exists and is a student
            if (!$user || !$user->isStudent()) {
                Log::warning('Student login failed - user not found or not student', [
                    'library_id' => $request->library_id,
                    'user_exists' => !is_null($user),
                    'is_student' => $user ? $user->isStudent() : false
                ]);

                return redirect()->back()
                    ->withErrors(['library_id' => 'The provided Library ID and password do not match our records.'])
                    ->withInput($request->except('password'));
            }

            // Check password
            $passwordValid = Hash::check($request->password, $user->password);
            
            Log::info('Password validation', [
                'user_id' => $user->id,
                'password_valid' => $passwordValid
            ]);

            if (!$passwordValid) {
                Log::warning('Student login failed - invalid password', [
                    'library_id' => $request->library_id,
                    'user_id' => $user->id
                ]);

                return redirect()->back()
                    ->withErrors(['library_id' => 'The provided Library ID and password do not match our records.'])
                    ->withInput($request->except('password'));
            }

            // Block deactivated students
            if (empty($user->email_verified_at)) {
                Log::warning('Student login blocked - deactivated', [
                    'user_id' => $user->id,
                    'library_id' => $user->library_id
                ]);

                return redirect()->back()
                    ->withErrors(['library_id' => 'Your account is deactivated. Please contact the librarian.'])
                    ->withInput($request->except('password'));
            }

            Log::info('Student login successful', ['user_id' => $user->id]);
            Auth::guard('student')->login($user, $request->filled('remember'));
            $request->session()->regenerate();

            return redirect()->route('student.dashboard');

        } catch (\Exception $e) {
            Log::error('Student Login Error', [
                'request_data' => $request->all(),
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withErrors([
                    'library_id' => 'Login failed: ' . $e->getMessage() . '. Please try again.',
                ])
                ->withInput($request->except('password'));
        }
    }

    /**
     * Handle librarian login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function staffLogin(Request $request)
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput($request->except('password'));
            }

        Log::info('Librarian Login Attempt', [
                'email' => $request->email,
                'password_provided' => !empty($request->password)
            ]);

        // Find user by email and check if they're librarian
        $user = User::where('email', $request->email)
                   ->join('roles', 'users.role_id', '=', 'roles.id')
                   ->where('roles.name', 'librarian')
                   ->select('users.*')
                   ->first();

        Log::info('Librarian user found', [
            'user_found' => !is_null($user),
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'password_check' => $user ? Hash::check($request->password, $user->password) : false
        ]);

        if ($user && Hash::check($request->password, $user->password)) {
            // Use librarian guard for login
            Auth::guard('librarian')->login($user, $request->filled('remember'));
            $request->session()->regenerate();

            return redirect(route('librarian.dashboard'));
        }

            // Login failed
            return redirect()->back()
                ->withErrors([
                    'email' => 'The provided credentials do not match our records or you do not have librarian access.',
                ])
                ->withInput($request->except('password'));
                
        } catch (\Exception $e) {
            Log::error('Librarian login error: ' . $e->getMessage());
            return redirect()->back()
                ->withErrors([
                    'email' => 'An error occurred during login. Please try again.',
                ])
                ->withInput($request->except('password'));
        }
    }

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // For now, just return success message
        // You can implement actual email sending later
        return back()->with('status', 'Password reset link sent to your email address.');
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        Auth::guard('student')->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Handle librarian logout
     */
    public function librarianLogout(Request $request)
    {
        Auth::guard('librarian')->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/librarian/login');
    }
}
