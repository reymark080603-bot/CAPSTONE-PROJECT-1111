@extends('layouts.librarian')

@section('title', 'Reports')

@section('content')
<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-chart-bar text-blue-600 mr-3"></i>
                    Library Reports
                </h1>
                <p class="text-gray-600 mt-1">Generate comprehensive reports and analytics for library management</p>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-sm text-gray-500">Last updated: {{ now()->format('M d, Y H:i') }}</span>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
        <div class="bg-white rounded-lg p-6 border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Books</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $summaryStats['total_books'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600">
                    <i class="fas fa-book text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg p-6 border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Students</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $summaryStats['total_students'] }}</p>
                </div>
                <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center text-green-600">
                    <i class="fas fa-users text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg p-6 border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Active Borrows</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $summaryStats['active_borrows'] }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-50 rounded-lg flex items-center justify-center text-orange-600">
                    <i class="fas fa-hand-holding text-2xl"></i>
                </div>
            </div>
        </div>


        <div class="bg-white rounded-lg p-6 border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Borrowed This Month</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $summaryStats['borrowed_this_month'] }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center text-purple-600">
                    <i class="fas fa-calendar text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg p-6 border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Returned This Month</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $summaryStats['returned_this_month'] }}</p>
                </div>
                <div class="w-12 h-12 bg-teal-50 rounded-lg flex items-center justify-center text-teal-600">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Categories -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Borrowing & Usage Reports -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                Borrowing & Usage Reports
            </h2>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="flex items-center">
                        <i class="fas fa-chart-area text-blue-500 mr-3"></i>
                        <div>
                            <p class="font-medium text-gray-900">Borrowing Statistics</p>
                            <p class="text-sm text-gray-600">Monthly trends and borrowing patterns</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <a href="{{ route('librarian.reports.borrowing-statistics') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-eye mr-1"></i> View
                        </a>
                        <a href="{{ route('librarian.reports.export', 'borrowing-statistics') }}" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-download mr-1"></i> Export
                        </a>
                    </div>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="flex items-center">
                        <i class="fas fa-book-open text-purple-500 mr-3"></i>
                        <div>
                            <p class="font-medium text-gray-900">Book Usage Analysis</p>
                            <p class="text-sm text-gray-600">Most and least borrowed books</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <a href="{{ route('librarian.reports.book-usage') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-eye mr-1"></i> View
                        </a>
                        <a href="{{ route('librarian.reports.export', 'book-usage') }}" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-download mr-1"></i> Export
                        </a>
                    </div>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="flex items-center">
                        <i class="fas fa-star text-yellow-500 mr-3"></i>
                        <div>
                            <p class="font-medium text-gray-900">Popular Books Report</p>
                            <p class="text-sm text-gray-600">Trending and most requested books</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <a href="{{ route('librarian.reports.popular-books') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-eye mr-1"></i> View
                        </a>
                        <a href="{{ route('librarian.reports.export', 'popular-books') }}" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-download mr-1"></i> Export
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student & Administrative Reports -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-users text-green-600 mr-2"></i>
                Student & Administrative Reports
            </h2>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="flex items-center">
                        <i class="fas fa-user-graduate text-green-500 mr-3"></i>
                        <div>
                            <p class="font-medium text-gray-900">Student Activity Report</p>
                            <p class="text-sm text-gray-600">Student borrowing patterns and activity levels</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <a href="{{ route('librarian.reports.student-activity') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-eye mr-1"></i> View
                        </a>
                        <a href="{{ route('librarian.reports.export', 'student-activity') }}" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-download mr-1"></i> Export
                        </a>
                    </div>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="flex items-center">
                        <i class="fas fa-graduation-cap text-indigo-500 mr-3"></i>
                        <div>
                            <p class="font-medium text-gray-900">Course Analysis</p>
                            <p class="text-sm text-gray-600">Borrowing patterns by course and department</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <a href="{{ route('librarian.reports.course-analysis') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-eye mr-1"></i> View
                        </a>
                        <a href="{{ route('librarian.reports.export', 'course-analysis') }}" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-download mr-1"></i> Export
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Monthly Summary -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
            <i class="fas fa-calendar-alt text-purple-600 mr-2"></i>
            Monthly Summary Reports
        </h2>
        
        <div class="grid grid-cols-1 gap-4">
            <div class="flex items-center justify-between p-4 bg-purple-50 rounded-lg border border-purple-200">
                <div class="flex items-center">
                    <i class="fas fa-chart-bar text-purple-500 mr-3"></i>
                    <div>
                        <p class="font-medium text-gray-900">Current Month Summary</p>
                        <p class="text-sm text-gray-600">{{ now()->format('F Y') }} - Complete monthly overview</p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('librarian.reports.monthly-summary') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                        <i class="fas fa-eye mr-1"></i> View
                    </a>
                    <a href="{{ route('librarian.reports.export', 'monthly-summary') }}" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                        <i class="fas fa-download mr-1"></i> Export
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Visual Summaries -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900">Category of Books</h3>
                <p class="text-gray-600 text-sm">Borrowing activity by category</p>
            </div>
            <div class="p-6">
                <div class="h-72">
                    <canvas id="categories-chart"></canvas>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900">Most Borrowed Books</h3>
                <p class="text-gray-600 text-sm">Top 10 most popular books</p>
            </div>
            <div id="most-borrowed-books" class="p-6 max-h-80 overflow-y-auto">
                <div class="flex justify-center items-center py-12">
                    <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mr-3"></i>
                    <span class="text-gray-600">Loading books...</span>
                </div>
            </div>
        </div>
    </div>

<!-- Custom Report Modal -->
<div id="customReportModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Generate Custom Report</h3>
                <button onclick="closeCustomReportModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="customReportForm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="report-type" class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                        <select id="report-type" name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="borrowing-statistics">Borrowing Statistics</option>
                            <option value="student-activity">Student Activity</option>
                            <option value="book-usage">Book Usage</option>
                            <option value="popular-books">Popular Books</option>
                            <option value="course-analysis">Course Analysis</option>
                            <option value="monthly-summary">Monthly Summary</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="date-from" class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input id="date-from" type="date" name="date_from" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <input id="date-to" type="date" name="date_to" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeCustomReportModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/librarian-dashboard.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Print dropdown functionality
    const printDropdownBtn = document.getElementById('print-dropdown-btn');
    const printDropdown = document.getElementById('print-dropdown');
    
    if (printDropdownBtn && printDropdown) {
        printDropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            printDropdown.classList.toggle('hidden');
        });
        
        document.addEventListener('click', function() {
            printDropdown.classList.add('hidden');
        });
        
        printDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Custom report form handling
    const customReportForm = document.getElementById('customReportForm');
    if (customReportForm) {
        customReportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const params = new URLSearchParams(formData);
            
            // Generate the report with custom parameters
            window.open(`{{ route('librarian.reports.generate') }}?${params.toString()}`, '_blank');
            
            closeCustomReportModal();
        });
    }
});

function generateCustomReport() {
    document.getElementById('customReportModal').classList.remove('hidden');
}

function closeCustomReportModal() {
    document.getElementById('customReportModal').classList.add('hidden');
}

function scheduleReport() {
    alert('Report scheduling feature coming soon!');
}

function exportAllReports() {
    alert('Bulk export feature coming soon!');
}
</script>
@endsection
