<?php

    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\HomeController;
    use App\Http\Controllers\Auth\LoginController;
    use App\Http\Controllers\Auth\RegisterController;
    use App\Http\Controllers\Auth\ForgotPasswordController;
    use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\LibrarianController;
use App\Http\Controllers\BulkUploadController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\RecommendedController;
    
use App\Http\Controllers\RecentBooksController;
    
use App\Services\PdfCoverService;

// SYSTEM CHECK ROUTE - Visit this to diagnose cover generation issues
// GHOST ROUTE - Bypasses Nginx static interceptor on Railway
Route::get('/library-assets/{path}', function($path) {
    $paths = [
        '/app/storage/app/public/' . $path,
        storage_path('app/public/' . $path),
    ];
    
    foreach ($paths as $fullPath) {
        if (file_exists($fullPath) && !is_dir($fullPath)) {
            return response()->file($fullPath);
        }
    }
    
    return "DEBUG: File Not Found. Checked: " . implode(', ', $paths);
})->where('path', '.*');

Route::get('/check-system', function(PdfCoverService $pdfService) {
    $info = $pdfService->getStorageInfo();
    
    // LIVE TEST
    $testFile = 'debug_' . time() . '.txt';
    $writeSuccess = \Illuminate\Support\Facades\Storage::disk('public')->put($testFile, 'Hello World');
    $fullPath = storage_path('app/public/' . $testFile);
    $fileExistsAtLocation = file_exists($fullPath);
    $publicUrl = asset('library-assets/' . $testFile);
    
    $html = "<h1>System Diagnostic (GHOST TEST)</h1>";
    
    $html .= "<h3>Live File Test:</h3>";
    $html .= "1. Write Success: " . ($writeSuccess ? "✅" : "❌") . "<br>";
    $html .= "2. Absolute Path: <code>$fullPath</code><br>";
    $html .= "3. Exists at Path: " . ($fileExistsAtLocation ? "✅" : "❌") . "<br>";
    $html .= "4. Ghost URL: <a href='$publicUrl' target='_blank'>$publicUrl</a><br>";
    
    $html .= "<h3>Extensions:</h3>";
    $html .= "Loaded: " . implode(', ', get_loaded_extensions()) . "<br>";
    
    $html .= "<h3>Cloudinary Status:</h3>";
    $cloudinaryUrl = env('CLOUDINARY_URL');
    $html .= "CLOUDINARY_URL: " . ($cloudinaryUrl ? "✅ Configured (" . substr($cloudinaryUrl, 0, 15) . "...)" : "❌ NOT FOUND") . "<br>";
    $html .= "<p style='font-size: 0.9em; color: #666;'>To fix this, go to Cloudinary Dashboard, copy the 'API Environment variable' and paste it into your Railway Variables as CLOUDINARY_URL.</p>";
    
    $html .= "<h3>Tools Status:</h3>";
    $html .= "GD Extension: " . (extension_loaded('gd') ? "✅ Installed" : "❌ MISSING") . "<br>";
    $html .= "Imagick Extension: " . ($info['imagick_available'] ? "✅ Installed" : "❌ MISSING") . "<br>";
    $html .= "pdftoppm (Poppler): " . ($info['pdftoppm_available'] ? "✅ Installed" : "❌ MISSING") . "<br>";
    
    $html .= "<h3>Storage Status:</h3>";
    $html .= "Public Storage Link: " . (file_exists(public_path('storage')) ? "✅ Exists" : "❌ MISSING") . "<br>";
    $html .= "Ebooks Directory: " . (is_dir(storage_path('app/public/ebooks')) ? "✅ Exists" : "❌ MISSING") . "<br>";
    $html .= "Covers Directory: " . (is_dir(storage_path('app/public/covers')) ? "✅ Exists" : "❌ MISSING") . "<br>";
    $html .= "Covers Writable: " . (is_writable(storage_path('app/public/covers')) ? "✅ Yes" : "❌ No") . "<br>";
    
    $html .= "<h3>File Counts:</h3>";
    $html .= "PDFs found: " . $info['pdf']['count'] . "<br>";
    $html .= "Covers found: " . $info['covers']['count'] . "<br>";
    
    return $html;
});

// STORAGE PROXY - Bypasses broken symlinks on Railway
Route::get('/storage/{path}', function($path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) {
        abort(404);
    }
    return response()->file($fullPath);
})->where('path', '.*');

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

// TEMPORARY FIX ROUTE - Visit this once on Railway to fix admin access
Route::get('/fix-admin-access', function() {
    try {
        $email = 'JHCSCLibrarian@gmail.com';
        $password = 'JHCSCLib2026';
        $name = 'Shienalie S. Lubon';

        // 1. Ensure role exists
        $role = Role::firstOrCreate(
            ['name' => 'librarian'],
            ['display_name' => 'Librarian', 'description' => 'Library staff with admin privileges']
        );

        // 2. Delete any conflicting user
        User::where('email', $email)->delete();

        // 3. Create fresh admin using direct DB to avoid model cast issues
        DB::table('users')->insert([
            'name' => $name,
            'firstname' => 'Shienalie',
            'lastname' => 'Lubon',
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => $role->id,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return "✅ ADMIN FIXED! You can now login with: <br>Email: $email <br>Password: $password <br><br><a href='/login'>Go to Login</a>";
    } catch (\Exception $e) {
        return "❌ FIX FAILED: " . $e->getMessage();
    }
});

// Admin/librarian login routes redirect to the shared login page.
    Route::get('/librarian/login', function () {
        return redirect()->route('login');
    })->name('librarian.login');

    Route::post('/librarian/login', [LoginController::class, 'login'])->name('librarian.login.post');

    // Main route - redirect to login
    Route::get('/', function () {
        return view('Student.login');
    });

    // Student login routes
    Route::get('/login', function () {
        return view('Student.login');
    })->name('login');

    // Student login route kept for backward compatibility.
    Route::post('/student/login', [LoginController::class, 'login']);

    // General login (for backward compatibility)
    Route::post('/login', [LoginController::class, 'login']);

    // Student registration routes
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // Password reset routes
    Route::get('/password/reset', function () {
        return view('auth.passwords.email');
    })->name('password.request');

    Route::post('/password/email', [LoginController::class, 'sendResetLinkEmail'])->name('password.email');

    // Logout routes
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/librarian/logout', [LoginController::class, 'librarianLogout'])->name('librarian.logout');

    Route::middleware(['auth:student'])->group(function () {
        Route::get('/home', [HomeController::class, 'index'])->name('home');
        Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');
        Route::get('/student/dashboard', [StudentDashboardController::class, 'index'])->name('student.dashboard');
        Route::get('/dashboard/recommended', [RecommendedController::class, 'index'])->name('dashboard.recommended');
        Route::get('/dashboard/recent', [HomeController::class, 'getRecentBooks'])->name('dashboard.recent');
        Route::get('/dashboard/stats', [HomeController::class, 'getDashboardStats'])->name('dashboard.stats');
        Route::get('/dashboard/search', [HomeController::class, 'quickSearch'])->name('dashboard.search');
        
        // Books routes for students
        Route::prefix('student/books')->group(function () {
            Route::get('/', [HomeController::class, 'books'])->name('student.books');
            Route::get('/api', [HomeController::class, 'booksApi'])->name('student.books.api');
            Route::post('/{book}/borrow', [HomeController::class, 'borrowBook'])->name('student.books.borrow');
            // More specific routes must be declared before the generic book detail route
            Route::get('/{book}/read', [HomeController::class, 'viewBook'])->name('student.books.read');
            Route::get('/{book}/details', [HomeController::class, 'bookDetails'])->name('student.books.details');
            Route::get('/{book}', [HomeController::class, 'showBookDetailsPage'])->name('student.books.show');
            // Route::post('/{book}/reserve', [HomeController::class, 'reserveBook'])->name('student.books.reserve'); // Disabled for open access
            // Route::get('/{book}/download', [HomeController::class, 'downloadBook'])->name('student.books.download'); // Disabled for online ebook system
        });

        // General API routes
        Route::get('/api/books', [HomeController::class, 'booksApi'])->name('api.books');
        
        // Recent books routes (accessible by students and librarians)
        Route::get('/recent-books', [RecentBooksController::class, 'index'])->name('recent.books');
        Route::get('/recent-books/api', [RecentBooksController::class, 'getRecentBooks'])->name('recent.books.api');
        
        // Upload single book (librarian only)
        
        // Borrow page route
        Route::get('/student/borrow', [HomeController::class, 'borrowPage'])->name('student.borrow');
        Route::get('/student/borrow/{book}', [HomeController::class, 'borrowPage'])->name('student.borrow.book');
        
        // History routes for students
        Route::prefix('student')->group(function () {
            Route::get('/history', [HomeController::class, 'history'])->name('student.history');
            Route::get('/history/api', [HomeController::class, 'historyApi'])->name('student.history.api');
            Route::get('/history/statistics', [HomeController::class, 'historyStatistics'])->name('student.history.statistics');
            Route::get('/history/export', [HomeController::class, 'exportHistory'])->name('student.history.export');
            Route::post('/history/clear', [HomeController::class, 'clearHistory'])->name('student.history.clear');
            Route::post('/borrow-records/{borrowRecord}/renew', [HomeController::class, 'renewBook'])->name('student.borrow.renew');
            Route::post('/borrow-records/{borrowRecord}/return', [HomeController::class, 'returnBook'])->name('student.borrow.return');
        });
        
        // Loans routes for students
        Route::prefix('student')->group(function () {
            Route::get('/loans', [HomeController::class, 'loans'])->name('student.loans');
            Route::get('/loans/api', [HomeController::class, 'loansApi'])->name('student.loans.api');
            Route::get('/loans/statistics', [HomeController::class, 'loansStatistics'])->name('student.loans.statistics');
            Route::post('/loans/renew-all', [HomeController::class, 'renewAllLoans'])->name('student.loans.renewAll');
        });
    });

    // Librarian routes
    Route::middleware(['auth:librarian'])->group(function () {
        // Main librarian dashboard
        Route::get('/librarian/dashboard', [LibrarianController::class, 'dashboard'])->name('librarian.dashboard');
        
        // Dashboard API endpoints
        Route::get('/librarian/dashboard/stats', [LibrarianController::class, 'getDashboardStats'])->name('librarian.dashboard.stats');
        Route::get('/librarian/dashboard/activities', [LibrarianController::class, 'getRecentActivities'])->name('librarian.dashboard.activities');
        Route::get('/librarian/dashboard/alerts', [LibrarianController::class, 'getNotifications'])->name('librarian.dashboard.alerts');

        // Notification management routes
        Route::get('/librarian/dashboard/notifications/unread-count', [LibrarianController::class, 'getUnreadNotificationsCount'])->name('librarian.dashboard.notifications.unread-count');
        Route::post('/librarian/dashboard/notifications/{notification}/read', [LibrarianController::class, 'markNotificationAsRead'])->name('librarian.dashboard.notifications.mark-read');
        Route::post('/librarian/dashboard/notifications/mark-all-read', [LibrarianController::class, 'markAllNotificationsAsRead'])->name('librarian.dashboard.notifications.mark-all-read');
        Route::delete('/librarian/dashboard/notifications/{notification}', [LibrarianController::class, 'deleteNotification'])->name('librarian.dashboard.notifications.delete');
        Route::delete('/librarian/dashboard/notifications', [LibrarianController::class, 'clearAllNotifications'])->name('librarian.dashboard.notifications.clear-all');
        Route::post('/librarian/dashboard/notifications', [LibrarianController::class, 'createNotification'])->name('librarian.dashboard.notifications.create');

        // Quick actions
        Route::post('/librarian/dashboard/quick-action', [LibrarianController::class, 'quickAction'])->name('librarian.dashboard.quick-action');
        
        // Data export
        Route::get('/librarian/dashboard/export', [LibrarianController::class, 'exportData'])->name('librarian.dashboard.export');
        
        
        // Book management routes
        Route::prefix('librarian/books')->name('librarian.books.')->group(function () {
            Route::get('/', [LibrarianController::class, 'manageBooks'])->name('index');
            Route::get('/create', [LibrarianController::class, 'createBook'])->name('create');
            Route::get('/data', [LibrarianController::class, 'getBooksData'])->name('data');
            Route::post('/', [LibrarianController::class, 'storeBook'])->name('store');
            Route::post('/bulk-upload', [LibrarianController::class, 'bulkUpload'])->name('bulk-upload');
            Route::get('/{book}', [LibrarianController::class, 'showBook'])->name('show');
            Route::get('/{book}/edit', [LibrarianController::class, 'editBook'])->name('edit');
            Route::put('/{book}', [LibrarianController::class, 'updateBook'])->name('update');
            Route::delete('/{book}', [LibrarianController::class, 'destroyBook'])->name('destroy');
        });

        // PDF Bulk upload routes
        Route::prefix('librarian/books/bulk')->name('librarian.books.bulk.')->group(function () {
            Route::get('/upload', [BulkUploadController::class, 'index'])->name('upload');
            Route::post('/process', [BulkUploadController::class, 'process'])->name('process');
            Route::get('/storage-status', [BulkUploadController::class, 'checkStorage'])->name('storage-status');
        });

        // Single book upload and thumbnail generation (librarian only)
        Route::prefix('librarian/books')->name('librarian.books.')->group(function () {
            Route::post('/upload', [RecentBooksController::class, 'upload'])->name('upload');
            Route::post('/{book}/generate-thumbnail', [RecentBooksController::class, 'generateThumbnail'])->name('generate-thumbnail');
        });

        // Loans management routes for librarians
        Route::prefix('librarian/loans')->name('librarian.loans.')->group(function () {
            Route::get('/', [LibrarianController::class, 'manageLoans'])->name('index');
            Route::get('/data', [LibrarianController::class, 'getLoansData'])->name('data');
            Route::get('/statistics', [LibrarianController::class, 'loansStatistics'])->name('statistics');
            Route::post('/{borrowRecord}/return', [LibrarianController::class, 'returnLoan'])->name('return');
            Route::post('/{borrowRecord}/renew', [LibrarianController::class, 'renewLoan'])->name('renew');
        });
        
        // Student management routes for librarians
        Route::prefix('librarian/students')->name('librarian.students.')->group(function () {
            Route::get('/', [LibrarianController::class, 'manageStudents'])->name('index');
            Route::get('/data', [LibrarianController::class, 'getStudentsData'])->name('data');
            Route::get('/{user}', [LibrarianController::class, 'showStudent'])->name('show');
            Route::put('/{user}/activate', [LibrarianController::class, 'activateStudent'])->name('activate');
            Route::put('/{user}/deactivate', [LibrarianController::class, 'deactivateStudent'])->name('deactivate');
            Route::delete('/{user}', [LibrarianController::class, 'deleteStudent'])->name('destroy');
        });
        
        // Reports management routes for librarians
        Route::prefix('librarian/reports')->name('librarian.reports.')->group(function () {
            Route::get('/', [LibrarianController::class, 'reportsIndex'])->name('index');
            Route::post('/generate', [LibrarianController::class, 'generateReport'])->name('generate');
            Route::get('/borrowing-statistics', [LibrarianController::class, 'borrowingStatisticsReport'])->name('borrowing-statistics');
            Route::get('/student-activity', [LibrarianController::class, 'studentActivityReport'])->name('student-activity');
            Route::get('/book-usage', [LibrarianController::class, 'bookUsageReport'])->name('book-usage');
            Route::get('/popular-books', [LibrarianController::class, 'popularBooksReport'])->name('popular-books');
            Route::get('/course-analysis', [LibrarianController::class, 'courseAnalysisReport'])->name('course-analysis');
            Route::get('/monthly-summary', [LibrarianController::class, 'monthlySummaryReport'])->name('monthly-summary');
            Route::get('/print/{type}', [LibrarianController::class, 'printReport'])->name('print');
            Route::get('/export/{type}', [LibrarianController::class, 'exportReport'])->name('export');
        });
    });
