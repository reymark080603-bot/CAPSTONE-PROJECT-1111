@extends('layouts.librarian')

@section('title', 'Manage Books')

@section('content')
<!-- Success Message -->
@if(session('success'))
<div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-6 flex items-center max-w-2xl mx-auto w-full">
    <div class="flex-shrink-0">
        <i class="fas fa-check-circle text-emerald-600 text-xl"></i>
    </div>
    <div class="ml-3">
        <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
    </div>
    <div class="ml-auto pl-3">
        <button type="button" class="text-emerald-600 hover:text-emerald-800" onclick="this.parentElement.parentElement.style.display='none'">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif

@if(session('error'))
<div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 flex items-center max-w-2xl mx-auto w-full">
    <div class="flex-shrink-0">
        <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
    </div>
    <div class="ml-3">
        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
    </div>
    <div class="ml-auto pl-3">
        <button type="button" class="text-red-600 hover:text-red-800" onclick="this.parentElement.parentElement.style.display='none'">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif

<!-- Books Management Header -->
<div class="books-header">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Manage Resources</h1>
            <p class="text-gray-600 mt-2">Add, edit, and manage library books, e-journals, thesis, and other resources</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('librarian.books.bulk.upload') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg flex items-center gap-2 transition-all duration-200 hover:transform hover:scale-105 shadow-lg">
                <i class="fas fa-file-upload"></i>
                Upload Resources
            </a>
        </div>
    </div>
</div>

<!-- Filters Section -->
<div class="books-filters">
    <div class="p-6">
        <div class="flex items-center mb-4">
            <i class="fas fa-filter text-gray-500 mr-2"></i>
            <h2 class="text-lg font-semibold text-gray-900">Search & Filters</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 items-end">
            <div class="lg:col-span-2">
                <label for="searchInput" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="searchInput" placeholder="Search by title, author, ISBN..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                </div>
            </div>
            <div>
                <label for="resourceTypeFilter" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select id="resourceTypeFilter" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <option value="">All Types</option>
                    <option value="book">Books</option>
                    <option value="e_journal">E-Journals</option>
                    <option value="thesis">Thesis</option>
                    <option value="homegrown">Homegrown / Unpublished</option>
                </select>
            </div>
            <div>
                <label for="subcategoryFilter" class="block text-sm font-medium text-gray-700 mb-2">Subcategory</label>
                <select id="subcategoryFilter" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <option value="">All Subcategories</option>
                    <option value="Thesis & Dissertation">Thesis & Dissertation</option>
                    <option value="Capstone Project">Capstone Project</option>
                    <option value="Institutional Research">Institutional Research</option>
                    <option value="Course Module">Course Module</option>
                    <option value="Institutional Publication">Institutional Publication</option>
                </select>
            </div>
            <div>
                <label for="courseFilter" class="block text-sm font-medium text-gray-700 mb-2">Program</label>
                <select id="courseFilter" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <option value="">All Programs</option>
                    <option value="BSE">BSE</option>
                    <option value="BSHM">BSHM</option>
                    <option value="BSIT">BSIT</option>
                    <option value="BSN">BSN</option>
                    <option value="BSTM">BSTM</option>
                </select>
            </div>
            <div>
                <label for="titleSortFilter" class="block text-sm font-medium text-gray-700 mb-2">Sort</label>
                <select id="titleSortFilter" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <option value="">Newest First</option>
                    <option value="asc">Title A-Z</option>
                    <option value="desc">Title Z-A</option>
                </select>
            </div>
            <div class="flex items-end">
                <button id="clearFiltersBtn" class="h-[42px] bg-gray-500 hover:bg-gray-600 text-white px-4 rounded-lg transition-all duration-200 inline-flex items-center justify-center gap-2 whitespace-nowrap">
                    <i class="fas fa-times"></i>
                    Clear
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Books Table -->
<div class="books-table">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center">
            <i class="fas fa-table text-gray-500 mr-2"></i>
            <h2 class="text-lg font-semibold text-gray-900">Library Resources Collection</h2>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table id="booksTable" class="w-full table-fixed">
            <colgroup>
                <col class="w-[96px]">
                <col style="width: 260px; max-width: 260px;">
                <col class="w-[120px]">
                <col class="w-[120px]">
                <col class="w-[140px]">
                <col class="w-[112px]">
            </colgroup>
            <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cover</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resource Details</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Availability</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <!-- DataTable will populate this -->
            </tbody>
        </table>
    </div>
</div>

<!-- Note: Add New Book is now a dedicated page. Modal functionality kept for editing books only. -->

<!-- Delete Confirmation Popup (center card, no dark overlay) -->
<div id="deletePopup" class="fixed inset-0 hidden items-center justify-center z-[9999] bg-black/40 backdrop-blur-[2px]">
    <div id="deletePopupCard" class="bg-white rounded-xl border shadow-2xl max-w-md w-[90%] p-6 transform transition-all duration-200 scale-95 opacity-0">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <div class="min-w-0">
                <h3 class="text-lg font-semibold text-gray-900">Delete Book</h3>
                <p class="text-sm text-gray-500">Are you sure you want to delete this book?</p>
                <div class="bg-gray-50 rounded-lg p-3 mt-3">
                    <p class="text-sm text-gray-700 truncate" id="deletePopupTitle"></p>
                </div>
            </div>
        </div>
        <div class="flex justify-end gap-3 mt-5">
            <button id="deletePopupCancel" class="px-4 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Cancel</button>
            <button id="deletePopupConfirm" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                <span id="deletePopupConfirmText">Delete</span>
            </button>
        </div>
    </div>
</div>

<!-- Bulk Upload Modal -->
<div id="bulkUploadModal" class="fixed inset-0 flex items-center justify-center z-[9999] bg-black/40 backdrop-blur-[2px]" style="display: none;">
    <div id="bulkUploadModalCard" class="bg-white rounded-xl border shadow-2xl max-w-4xl w-[90%] max-h-[90vh] overflow-hidden p-6 transform transition-all duration-200 scale-95 opacity-0">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-semibold text-gray-900">Bulk Upload Resources</h3>
                <p class="text-sm text-gray-600 mt-1">Upload multiple files to add resources quickly</p>
            </div>
            <button onclick="closeBulkUploadModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- Upload Area -->
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-400 transition-colors" id="bulkUploadArea">
            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
            <h4 class="text-lg font-medium text-gray-900 mb-2">Drop files here or click to browse</h4>
            <p class="text-sm text-gray-600 mb-4">Support: PDF, EPUB, DOC (Max 100MB per file)</p>
            <input type="file" id="bulkFileInput" multiple accept=".pdf,.epub,.doc,.docx,.jpg,.jpeg,.png" class="hidden">
            <button onclick="document.getElementById('bulkFileInput').click()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                <i class="fas fa-folder-open mr-2"></i>Select Files
            </button>
        </div>

        <!-- File List -->
        <div id="bulkFileList" class="mt-6 max-h-60 overflow-y-auto hidden">
            <h4 class="text-sm font-medium text-gray-900 mb-3">Selected Files:</h4>
            <div id="bulkFileItems" class="space-y-2">
                <!-- Files will be listed here -->
            </div>
        </div>

        <!-- Resource Type Selection -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Default Resource Type</label>
                <select id="bulkResourceType" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="book">Book</option>
                    <option value="e_journal">E-Journal</option>
                    <option value="thesis">Thesis</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Default Category</label>
                <select id="bulkCategory" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Category</option>
                    <option value="Science">Science</option>
                    <option value="Technology">Technology</option>
                    <option value="Engineering">Engineering</option>
                    <option value="Mathematics">Mathematics</option>
                    <option value="Literature">Literature</option>
                    <option value="History">History</option>
                    <option value="Philosophy">Philosophy</option>
                    <option value="Business">Business</option>
                    <option value="Health">Health</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Default Program</label>
                <select id="bulkCourse" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Programs</option>
                    <option value="BSE">BSE</option>
                    <option value="BSHM">BSHM</option>
                    <option value="BSIT">BSIT</option>
                    <option value="BSN">BSN</option>
                    <option value="BSTM">BSTM</option>
                </select>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end gap-3 mt-6">
            <button onclick="closeBulkUploadModal()" class="px-6 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                Cancel
            </button>
            <button id="bulkUploadBtn" onclick="startBulkUpload()" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors flex items-center gap-2" disabled>
                <i class="fas fa-upload"></i>
                <span id="bulkUploadBtnText">Upload Files</span>
                <div id="bulkUploadLoader" class="hidden">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
            </button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed top-4 left-1/2 -translate-x-1/2 -translate-y-2 w-[92%] sm:w-auto max-w-lg bg-white border border-gray-200 rounded-lg shadow-lg p-4 transition-all duration-300 z-50 opacity-0 pointer-events-none">
    <div class="flex items-center">
        <div id="toastIcon" class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center mr-3">
            <i class="fas fa-check"></i>
        </div>
        <div>
            <p id="toastMessage" class="text-sm font-medium text-gray-900"></p>
        </div>
        <button id="closeToast" class="ml-4 text-gray-400 hover:text-gray-600">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Books Management Page Styles */
.books-header {
    margin-bottom: 2rem;
}

.books-filters {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    margin-bottom: 2rem;
}

.books-table {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

#booksTable {
    table-layout: fixed;
    border-collapse: collapse;
}

#booksTable th,
#booksTable td {
    vertical-align: middle;
}

#booksTable tbody td {
    padding: 0.875rem 1rem;
}

#booksTable tbody td:first-child {
    text-align: left;
}

#booksTable tbody td:nth-child(2) {
    min-width: 0;
    max-width: 260px;
    width: 260px;
}

#booksTable tbody td:nth-child(2) .min-w-0 {
    overflow: hidden;
    max-width: 100%;
}

#booksTable tbody td:nth-child(2) h4,
#booksTable tbody td:nth-child(2) p {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
    display: block;
}

#booksTable tbody td:nth-child(3),
#booksTable tbody td:nth-child(4),
#booksTable tbody td:nth-child(5),
#booksTable tbody td:nth-child(6) {
    white-space: nowrap;
}

#booksTable tbody td:nth-child(6) .flex {
    justify-content: flex-start;
}

/* Responsive grid adjustments */
@media (max-width: 768px) {
    .books-header .flex {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .books-header .flex > div:last-child {
        width: 100%;
        display: flex;
        justify-content: flex-start;
    }
    
    .books-filters .grid {
        grid-template-columns: 1fr !important;
        gap: 1rem;
    }
}

/* Enhanced Modal Styles */
#bookModal {
    display: flex !important;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    backdrop-filter: blur(3px);
    -webkit-backdrop-filter: blur(3px);
}

/* deletePopup has no dark overlay; handled via fixed centered card */

/* Book Form Enhanced Styling */
.book-form-section {
    background: #f8fafc;
    border-radius: 0.75rem;
    padding: 1.5rem;
    border: 1px solid #e2e8f0;
}

.book-form-section h4 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
}

.book-form-section h4 i {
    margin-right: 0.5rem;
    width: 1.25rem;
    text-align: center;
}

/* Progress Indicator */
.progress-step {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
}

.progress-step.active .progress-circle {
    background-color: #2563eb;
    color: white;
}

.progress-step.active .progress-label {
    color: #2563eb;
    font-weight: 600;
}

.progress-circle {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    background-color: #d1d5db;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 500;
}

.progress-label {
    margin-left: 0.5rem;
    color: #6b7280;
}

/* Upload Area Styling */
#uploadArea {
    transition: all 0.3s ease;
    cursor: pointer;
}

#uploadArea:hover {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

#uploadArea.drag-over {
    border-color: #2563eb !important;
    background-color: #dbeafe !important;
    transform: scale(1.02);
}

/* Enhanced form inputs */
.book-form-section input,
.book-form-section select,
.book-form-section textarea {
    transition: all 0.2s ease;
    font-size: 0.875rem;
}

.book-form-section input:focus,
.book-form-section select:focus,
.book-form-section textarea:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    border-color: #3b82f6;
    transform: translateY(-1px);
}

/* Label icons */
.book-form-section label i {
    color: #6b7280;
    margin-right: 0.25rem;
    width: 1rem;
    text-align: center;
}

/* Remove button for image */
#removeImage {
    transition: all 0.2s ease;
}

#removeImage:hover {
    transform: scale(1.1);
}

#modalContent, #deletePopupCard {
    max-width: 90vw;
    max-height: 90vh;
    margin: auto;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    transform-origin: center;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

#modalContent:focus-within {
    box-shadow: 0 25px 50px -12px rgba(59, 130, 246, 0.25);
}

/* Prevent text selection in modal backdrop */
#bookModal {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

/* Allow text selection within modal content */
#modalContent, #deletePopupCard {
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
}

/* Enhanced form focus states */
#bookForm input:focus, 
#bookForm select:focus, 
#bookForm textarea:focus {
    ring-width: 2px;
    ring-color: rgb(59 130 246);
    border-color: rgb(59 130 246);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Smooth transitions for all interactive elements */
#bookForm input, 
#bookForm select, 
#bookForm textarea,
#bookForm button {
    transition: all 0.2s ease-in-out;
}

/* Loading state improvements */
.loading #modalContent {
    pointer-events: none;
    opacity: 0.7;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    #modalContent {
        max-width: 95vw;
        margin: 1rem;
    }
    
    #bookModal, #deleteModal {
        padding: 1rem;
        align-items: flex-start;
        padding-top: 2rem;
    }
}

/* Enhanced button hover effects */
#addBookBtn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

/* Modal content scroll styling */
#modalContent::-webkit-scrollbar {
    width: 8px;
}

#modalContent::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

#modalContent::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

#modalContent::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Override DataTables pagination with modern Tailwind design */
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_paginate {
    margin-top: 1rem;
}

.dataTables_wrapper .row {
    --bs-gutter-x: 0;
    margin-left: 0 !important;
    margin-right: 0 !important;
}

.dataTables_wrapper .row:last-child {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-top: 1px solid #e5e7eb;
    flex-wrap: wrap;
    background: #f9fafb;
    color: #374151;
}

.dataTables_wrapper .row:last-child > div {
    width: auto;
    flex: 0 0 auto;
    padding-left: 0 !important;
    padding-right: 0 !important;
    max-width: none;
}

.dataTables_wrapper .dataTables_info {
    color: #374151 !important;
    padding-top: 0 !important;
    font-size: 0.875rem;
    background: transparent !important;
    border: 0 !important;
}

.dataTables_wrapper .dataTables_paginate {
    display: flex !important;
    justify-content: flex-end;
    width: auto !important;
    float: none !important;
    padding-top: 0 !important;
    background: transparent !important;
    border: 0 !important;
}

.dataTables_wrapper .dataTables_paginate,
.dataTables_wrapper .dataTables_paginate * {
    box-shadow: none !important;
}

.dataTables_wrapper .dataTables_paginate::before,
.dataTables_wrapper .dataTables_paginate::after,
.dataTables_wrapper .row:last-child::before,
.dataTables_wrapper .row:last-child::after {
    display: none !important;
    content: none !important;
}

.dataTables_wrapper .dataTables_paginate .pagination {
    display: flex !important;
    flex-direction: row !important;
    align-items: center;
    flex-wrap: nowrap !important;
    gap: 0.25rem;
    margin: 0;
    padding-left: 0;
    list-style: none;
    white-space: nowrap;
}

#booksTable_wrapper .dataTables_paginate span {
    display: inline-flex !important;
    align-items: center;
    gap: 0.25rem;
    width: auto !important;
    background: transparent !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button,
.dataTables_wrapper .dataTables_paginate .page-item {
    display: inline-flex !important;
    flex: 0 0 auto !important;
    float: none !important;
    width: auto !important;
    min-width: 0 !important;
    height: auto !important;
    padding: 0 !important;
    margin: 0 !important;
    background: transparent !important;
    border: 0 !important;
    border-radius: 0 !important;
    color: #374151 !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button .page-link,
.dataTables_wrapper .dataTables_paginate .page-item .page-link,
#booksTable_wrapper .dataTables_paginate > .paginate_button,
#booksTable_wrapper .dataTables_paginate span .paginate_button {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    min-width: 2rem;
    height: 2rem;
    padding: 0 0.625rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: #374151;
    background: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    line-height: 1;
    text-decoration: none;
    white-space: nowrap;
    margin-left: 0 !important;
    width: auto !important;
    float: none !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button .page-link:hover,
.dataTables_wrapper .dataTables_paginate .page-item .page-link:hover,
#booksTable_wrapper .dataTables_paginate > .paginate_button:hover,
#booksTable_wrapper .dataTables_paginate span .paginate_button:hover {
    background: #f9fafb;
    color: #111827;
    border-color: #9ca3af;
    text-decoration: none !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current .page-link,
.dataTables_wrapper .dataTables_paginate .page-item.active .page-link,
#booksTable_wrapper .dataTables_paginate .paginate_button.current {
    background: #f9fafb !important;
    border-color: #d1d5db !important;
    color: #374151 !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
    width: auto !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.disabled .page-link,
.dataTables_wrapper .dataTables_paginate .page-item.disabled .page-link,
#booksTable_wrapper .dataTables_paginate .paginate_button.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    color: #9ca3af;
    background: #ffffff;
    border-color: #d1d5db;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.previous .page-link,
.dataTables_wrapper .dataTables_paginate .paginate_button.next .page-link,
.dataTables_wrapper .dataTables_paginate .page-item.previous .page-link,
.dataTables_wrapper .dataTables_paginate .page-item.next .page-link,
#booksTable_wrapper .dataTables_paginate .paginate_button.previous,
#booksTable_wrapper .dataTables_paginate .paginate_button.next {
    min-width: 0;
    padding: 0 0.75rem;
}

.dataTables_wrapper .row:last-child > div:last-child {
    margin-left: auto;
}

@media (max-width: 640px) {
    .dataTables_wrapper .row:last-child {
        gap: 0.5rem;
    }

    .dataTables_wrapper .dataTables_info {
        font-size: 0.75rem;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        min-width: 1.875rem;
        height: 1.75rem;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button .page-link,
    .dataTables_wrapper .dataTables_paginate .page-item .page-link {
        min-width: 1.875rem;
        height: 1.75rem;
        padding: 0 0.5rem;
        font-size: 11px;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.previous .page-link,
    .dataTables_wrapper .dataTables_paginate .paginate_button.next .page-link,
    .dataTables_wrapper .dataTables_paginate .page-item.previous .page-link,
    .dataTables_wrapper .dataTables_paginate .page-item.next .page-link {
        padding: 0 0.625rem;
    }
}
</style>
@endpush

@section('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
// Helper functions for category styling
function getCategoryColor(category) {
    const colors = {
        'programming': 'bg-blue-600',
        'mathematics': 'bg-green-600',
        'literature': 'bg-purple-600',
        'science': 'bg-red-600',
        'business': 'bg-amber-600',
        'technology': 'bg-indigo-600',
        'education': 'bg-pink-600',
        'reference': 'bg-gray-600',
        'health': 'bg-teal-600',
        'history': 'bg-orange-600',
        'philosophy': 'bg-violet-600'
    };
    return colors[category?.toLowerCase()] || 'bg-gray-600';
}

function getCategoryBgColor(category) {
    const colors = {
        'programming': 'bg-blue-100',
        'mathematics': 'bg-green-100',
        'literature': 'bg-purple-100',
        'science': 'bg-red-100',
        'business': 'bg-amber-100',
        'technology': 'bg-indigo-100',
        'education': 'bg-pink-100',
        'reference': 'bg-gray-100',
        'health': 'bg-teal-100',
        'history': 'bg-orange-100',
        'philosophy': 'bg-violet-100'
    };
    return colors[category?.toLowerCase()] || 'bg-gray-100';
}

function getCategoryTextColor(category) {
    const colors = {
        'programming': 'text-blue-600',
        'mathematics': 'text-green-600',
        'literature': 'text-purple-600',
        'science': 'text-red-600',
        'business': 'text-amber-600',
        'technology': 'text-indigo-600',
        'education': 'text-pink-600',
        'reference': 'text-gray-600',
        'health': 'text-teal-600',
        'history': 'text-orange-600',
        'philosophy': 'text-violet-600'
    };
    return colors[category?.toLowerCase()] || 'text-gray-600';
}

function getCategoryIcon(category) {
    const icons = {
        'programming': 'fa-code',
        'mathematics': 'fa-calculator',
        'literature': 'fa-feather-alt',
        'science': 'fa-flask',
        'business': 'fa-chart-line',
        'technology': 'fa-microchip',
        'education': 'fa-graduation-cap',
        'reference': 'fa-bookmark',
        'health': 'fa-heartbeat',
        'history': 'fa-clock',
        'philosophy': 'fa-brain'
    };
    return icons[category?.toLowerCase()] || 'fa-book';
}

function resolveBookCoverSrc(row) {
    if (row?.cover_url) {
        return row.cover_url;
    }

    if (row?.cover_image) {
        const cleaned = String(row.cover_image).replace(/^\/+/, '').replace(/^storage\//, '');
        return `{{ asset('storage') }}/${cleaned}`;
    }

    if (row?.cover_photo) {
        const cleaned = String(row.cover_photo).replace(/^\/+/, '');
        if (cleaned.startsWith('http://') || cleaned.startsWith('https://')) {
            return cleaned;
        }
        if (cleaned.startsWith('storage/')) {
            return `/${cleaned}`;
        }
        return `/${cleaned}`;
    }

    return `{{ asset('storage/covers/default-book.png') }}`;
}

function forceHorizontalPagination() {
    const paginate = document.querySelector('#booksTable_wrapper .dataTables_paginate');
    const info = document.querySelector('#booksTable_wrapper .dataTables_info');
    const row = paginate ? paginate.closest('.row') : null;

    if (row) {
        row.style.display = 'flex';
        row.style.alignItems = 'center';
        row.style.justifyContent = 'space-between';
        row.style.gap = '12px';
        row.style.flexWrap = 'wrap';
    }

    if (info) {
        info.style.marginTop = '1rem';
        info.style.flex = '0 0 auto';
    }

    if (!paginate) {
        return;
    }

    paginate.style.display = 'flex';
    paginate.style.justifyContent = 'flex-end';
    paginate.style.alignItems = 'center';
    paginate.style.width = 'auto';
    paginate.style.float = 'none';
    paginate.style.marginTop = '1rem';
    paginate.style.marginLeft = 'auto';

    const pagination = paginate.querySelector('.pagination');
    if (pagination) {
        pagination.style.display = 'flex';
        pagination.style.flexDirection = 'row';
        pagination.style.alignItems = 'center';
        pagination.style.flexWrap = 'nowrap';
        pagination.style.gap = '4px';
        pagination.style.margin = '0';
        pagination.style.paddingLeft = '0';
        pagination.style.listStyle = 'none';
        pagination.style.whiteSpace = 'nowrap';
    }

    paginate.querySelectorAll('.paginate_button, .page-item').forEach((item) => {
        item.style.display = 'inline-flex';
        item.style.flex = '0 0 auto';
        item.style.float = 'none';
        item.style.width = 'auto';
        item.style.minWidth = '0';
        item.style.height = 'auto';
        item.style.margin = '0';
        item.style.padding = '0';
        item.style.background = 'transparent';
        item.style.border = '0';
    });

    paginate.querySelectorAll('.paginate_button .page-link, .page-item .page-link, .paginate_button').forEach((item) => {
        item.style.display = 'inline-flex';
        item.style.alignItems = 'center';
        item.style.justifyContent = 'center';
        item.style.whiteSpace = 'nowrap';
        item.style.textDecoration = 'none';
        item.style.lineHeight = '1';
        item.style.width = 'auto';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    const table = $('#booksTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        lengthChange: true,
        pageLength: 10,
        language: {
            paginate: {
                previous: 'Prev',
                next: 'Next'
            }
        },
        initComplete: function() {
            forceHorizontalPagination();
        },
        drawCallback: function() {
            forceHorizontalPagination();
        },
        ajax: {
            url: "{{ route('librarian.books.data') }}",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            data: function(d) {
                d.search = document.getElementById('searchInput').value;
                d.resource_type = document.getElementById('resourceTypeFilter').value;
                d.subcategory = document.getElementById('subcategoryFilter') ? document.getElementById('subcategoryFilter').value : '';
                d.course = document.getElementById('courseFilter').value;
                d.title_sort = document.getElementById('titleSortFilter').value;
            },
            error: function(xhr, textStatus, errorThrown) {
                console.error('DataTables Ajax Error:');
                console.error('Status:', textStatus);
                console.error('Error:', errorThrown);
                console.error('Response:', xhr.responseText);
                
                if (xhr.status === 401) {
                    alert('Session expired. Please log in again.');
                    window.location.href = '/staff/login';
                } else if (xhr.status === 403) {
                    alert('Access denied. Staff privileges required.');
                } else {
                    alert('Error loading books data. Please try again or contact support.');
                }
            }
        },
        columns: [
            {
                data: 'cover_photo',
                orderable: false,
                render: function(data, type, row) {
                    let coverHtml = '';
                    const coverSrc = resolveBookCoverSrc(row);

                    if (coverSrc) {
                        coverHtml += `<img src="${coverSrc}" alt="${row.title} Cover" class="w-12 h-16 object-cover rounded border book-cover-img" onerror="this.classList.add('hidden'); const fallback = this.closest('td').querySelector('.default-book-cover'); if(fallback) fallback.classList.remove('hidden');">`;
                    }

                    // Default book cover design (shown if no image or image fails to load)
                    const categoryColor = getCategoryColor(row.category);
                    const categoryBgColor = getCategoryBgColor(row.category);
                    const categoryTextColor = getCategoryTextColor(row.category);
                    const categoryIcon = getCategoryIcon(row.category);

                    coverHtml += `
                        <div class="w-12 h-16 bg-white rounded border flex flex-col justify-between p-1 default-book-cover ${coverSrc ? 'hidden' : ''}">
                            <div class="h-2 ${categoryColor} relative rounded-sm">
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent rounded-sm"></div>
                            </div>
                            <div class="flex-1 flex items-center justify-center">
                                <div class="w-4 h-4 ${categoryBgColor} rounded-full flex items-center justify-center">
                                    <i class="fas ${categoryIcon} text-xs ${categoryTextColor}"></i>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold leading-none">
                                    ${row.category ? row.category.substring(0, 3) : 'GEN'}
                                </div>
                            </div>
                        </div>
                    `;

                    return coverHtml;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    const subcatBadge = row.subcategory ? `<span class="inline-block mt-1 px-2 py-0.5 bg-amber-50 border border-amber-200 text-amber-800 rounded text-[11px] font-medium"><i class="fas fa-sitemap mr-1"></i>${row.subcategory}</span>` : '';
                    const titleUpper = (row.title ?? '').toUpperCase();
                    return `
                        <div class="min-w-0">
                            <h4 class="font-medium text-gray-900 uppercase tracking-wide" title="${titleUpper}">${titleUpper}</h4>
                            <p class="text-sm text-gray-600" title="${row.author ?? ''}">by ${row.author}</p>
                            ${row.isbn ? `<p class="text-xs text-gray-500">ISBN: ${row.isbn}</p>` : ''}
                            ${subcatBadge}
                        </div>
                    `;
                }
            },
            {
                data: 'resource_type',
                render: function(data, type, row) {
                    const typeConfig = {
                        'book': { icon: 'fa-book', color: 'bg-blue-100 text-blue-800', label: 'Book' },
                        'e_journal': { icon: 'fa-newspaper', color: 'bg-purple-100 text-purple-800', label: 'E-Journal' },
                        'thesis': { icon: 'fa-graduation-cap', color: 'bg-green-100 text-green-800', label: 'Thesis' },
                        'homegrown': { icon: 'fa-house-user', color: 'bg-amber-100 text-amber-800', label: 'Homegrown' }
                    };
                    const config = typeConfig[data] || typeConfig.book;
                    return `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${config.color}"><i class="fas ${config.icon} mr-1"></i>${config.label}</span>`;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    const course = row.course || row.program || 'All Programs';
                    return `
                        <div class="text-sm">
                            <div>${course}</div>
                        </div>
                    `;
                }
            },
            {
                data: 'availability_status',
                render: function(data, type, row) {
                    const statusClasses = {
                        'available': 'bg-emerald-100 text-emerald-800',
                        'borrowed': 'bg-rose-100 text-rose-800',
                        'reserved': 'bg-amber-100 text-amber-800',
                        'maintenance': 'bg-slate-100 text-slate-800'
                    };
                    return `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${statusClasses[data] || 'bg-gray-100 text-gray-800'}">${data}</span>`;
                }
            },
            {
                data: 'actions',
                orderable: false,
                render: function(data, type, row) {
                    const safeTitle = JSON.stringify(row.title ?? '');
                    return `
                        <div class="flex items-center gap-2">
                            <button onclick="viewBook(${row.id})" class="text-blue-600 hover:text-blue-800" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="editBook(${row.id})" class="text-emerald-600 hover:text-emerald-800" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="toggleBookStatus(${row.id}, '${row.availability_status}')" class="${row.availability_status === 'maintenance' || row.availability_status === 'disabled' ? 'text-amber-600 hover:text-amber-800' : 'text-slate-500 hover:text-slate-800'}" title="${row.availability_status === 'maintenance' || row.availability_status === 'disabled' ? 'Enable Resource' : 'Disable Resource'}">
                                <i class="fas ${row.availability_status === 'maintenance' || row.availability_status === 'disabled' ? 'fa-check-circle' : 'fa-ban'}"></i>
                            </button>
                            <button onclick='deleteBook(${row.id}, ${safeTitle})' class="text-red-600 hover:text-red-800" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });

    // Expose table globally for other functions
    window.table = table;
    forceHorizontalPagination();

    // Filter event listeners
    let searchTimer = null;
    const searchInput = document.getElementById('searchInput');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                table.draw();
            }, 300);
        });
    }

    ['resourceTypeFilter', 'subcategoryFilter', 'courseFilter', 'titleSortFilter'].forEach(filterId => {
        const element = document.getElementById(filterId);
        if (element) {
            element.addEventListener('change', function() {
                table.draw();
            });
        }
    });

    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            const resourceTypeFilter = document.getElementById('resourceTypeFilter');
            const subcategoryFilter = document.getElementById('subcategoryFilter');
            const courseFilter = document.getElementById('courseFilter');
            const titleSortFilter = document.getElementById('titleSortFilter');
            if (resourceTypeFilter) resourceTypeFilter.value = '';
            if (subcategoryFilter) subcategoryFilter.value = '';
            if (courseFilter) courseFilter.value = '';
            if (titleSortFilter) titleSortFilter.value = '';
            table.draw();
        });
    }

    // Debug functionality removed - debugBtn element not present in HTML
    
    // Initialize modal functionality
    initializeBookModal();
});

// Book modal functionality
function initializeBookModal() {
    const bookModal = document.getElementById('bookModal');
    const modalContent = document.getElementById('modalContent');
    const modalTitle = document.getElementById('modalTitle');
    const addBookBtn = document.getElementById('addBookBtn');
    const closeModal = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const bookForm = document.getElementById('bookForm');
    const saveBtn = document.getElementById('saveBtn');
    const saveBtnText = document.getElementById('saveBtnText');
    const saveBtnLoader = document.getElementById('saveBtnLoader');
    
    let isEditing = false;
    let currentBookId = null;
    
    // Note: Add book button now navigates to dedicated page
    // Modal functionality kept for editing books only
    
    // Close modal events
    if (closeModal) {
        closeModal.addEventListener('click', function() {
            closeBookModal();
        });
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            closeBookModal();
        });
    }
    
    // Form submission
    if (bookForm) {
        bookForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await handleBookFormSubmit();
        });
    }
    
    function showBookModal() {
        if (bookModal && modalContent) {
            document.body.style.overflow = 'hidden';
            bookModal.classList.remove('hidden');
            
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        }
    }
    
    function closeBookModal() {
        if (bookModal && modalContent) {
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                bookModal.classList.add('hidden');
                document.body.style.overflow = '';
                resetBookForm();
            }, 300);
        }
    }
    
    function resetBookForm() {
        if (bookForm) {
            bookForm.reset();
            // Clear any error messages
            document.querySelectorAll('.error-message').forEach(el => {
                el.classList.add('hidden');
                el.textContent = '';
            });
            
            // Reset cover photo preview
            const uploadArea = document.getElementById('uploadArea');
            const previewArea = document.getElementById('previewArea');
            const coverInput = document.getElementById('coverInput');
            
            if (uploadArea && previewArea && coverInput) {
                uploadArea.classList.remove('hidden');
                previewArea.classList.add('hidden');
                coverInput.value = '';
            }
            
            // Set default values
            const languageSelect = bookForm.querySelector('[name="language"]');
            
            if (languageSelect) languageSelect.value = 'English';
        }
    }
    
    async function handleBookFormSubmit() {
        if (!bookForm) return;
        
        // Show loading state
        if (saveBtn) saveBtn.disabled = true;
        if (saveBtnText) saveBtnText.classList.add('hidden');
        if (saveBtnLoader) saveBtnLoader.classList.remove('hidden');
        
        try {
            const formData = new FormData(bookForm);
            const url = isEditing ? `/librarian/books/${currentBookId}` : '/librarian/books';
            
            if (isEditing) {
                formData.append('_method', 'PUT');
            }
            
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert(data.message);
                closeBookModal();
                if (window.table) {
                    window.table.draw();
                } else if (window.jQuery && $('#booksTable').length) {
                    $('#booksTable').DataTable().draw();
                }
            } else {
                if (data.errors) {
                    displayFormErrors(data.errors);
                } else {
                    alert(data.message || 'An error occurred');
                }
            }
            
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while saving the book');
        } finally {
            // Hide loading state
            if (saveBtn) saveBtn.disabled = false;
            if (saveBtnText) saveBtnText.classList.remove('hidden');
            if (saveBtnLoader) saveBtnLoader.classList.add('hidden');
        }
    }
    
    function displayFormErrors(errors) {
        Object.keys(errors).forEach(field => {
            const errorElement = document.querySelector(`.error-message[data-field="${field}"]`);
            if (errorElement) {
                errorElement.textContent = errors[field][0];
                errorElement.classList.remove('hidden');
            }
        });
    }
    
    // Initialize file upload functionality
    initializeFileUpload();
}

// File upload functionality
function initializeFileUpload() {
    const uploadArea = document.getElementById('uploadArea');
    const coverInput = document.getElementById('coverInput');
    const previewArea = document.getElementById('previewArea');
    const coverPreview = document.getElementById('coverPreview');
    const removeImageBtn = document.getElementById('removeImage');
    
    if (!uploadArea || !coverInput || !previewArea || !coverPreview) return;
    
    // Click to upload
    uploadArea.addEventListener('click', () => coverInput.click());
    
    // File selection
    coverInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file.');
                return;
            }
            
            if (file.size > 2 * 1024 * 1024) {
                alert('File size must be less than 2MB.');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                coverPreview.src = e.target.result;
                uploadArea.classList.add('hidden');
                previewArea.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Drag and drop
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('border-blue-400', 'bg-blue-50');
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('border-blue-400', 'bg-blue-50');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('border-blue-400', 'bg-blue-50');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            coverInput.files = files;
            coverInput.dispatchEvent(new Event('change'));
        }
    });
    
    // Remove image
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function() {
            coverInput.value = '';
            coverPreview.src = '';
            uploadArea.classList.remove('hidden');
            previewArea.classList.add('hidden');
        });
    }
}

// Global functions for DataTable buttons
window.viewBook = function(bookId) {
    // Open in the same tab instead of a new one
    window.location.href = `/librarian/books/${bookId}`;
};

window.editBook = function(bookId) {
    window.location.href = `/librarian/books/${bookId}/edit`;
};

window.deleteBook = function(bookId, bookTitle) {
    const container = document.getElementById('deletePopup');
    const card = document.getElementById('deletePopupCard');
    const titleEl = document.getElementById('deletePopupTitle');
    const confirmBtn = document.getElementById('deletePopupConfirm');
    const cancelBtn = document.getElementById('deletePopupCancel');
    const btnText = document.getElementById('deletePopupConfirmText');
    const btnLoader = document.getElementById('deletePopupLoader');

    if (!container || !card || !titleEl || !confirmBtn || !cancelBtn) {
        if (confirm(`Delete \"${bookTitle}\"?`)) {
            performDelete(bookId);
        }
        return;
    }

    titleEl.textContent = `\"${bookTitle}\"`;
    container.classList.remove('hidden');
    container.style.display = 'flex';
    // Animate in
    requestAnimationFrame(() => {
        card.classList.remove('scale-95', 'opacity-0');
        card.classList.add('scale-100', 'opacity-100');
    });

    const close = () => {
        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            container.classList.add('hidden');
            container.style.display = '';
        }, 180);
        confirmBtn.replaceWith(confirmBtn.cloneNode(true));
        cancelBtn.replaceWith(cancelBtn.cloneNode(true));
        if (btnText && btnLoader) { btnText.classList.remove('hidden'); btnLoader.classList.add('hidden'); }
    };

    // Click outside to close
    container.onclick = (e) => {
        if (e.target === container) {
            close();
        }
    };

    document.getElementById('deletePopupCancel').addEventListener('click', close, { once: true });
    document.getElementById('deletePopupConfirm').addEventListener('click', async () => {
        if (btnText && btnLoader) { btnText.classList.add('hidden'); btnLoader.classList.remove('hidden'); }
        await performDelete(bookId);
        close();
        // Redraw DataTable if present
        if (window.jQuery && $('#booksTable').length) {
            $('#booksTable').DataTable().draw();
        } else {
            window.location.reload();
        }
    }, { once: true });
};

async function performDelete(bookId) {
    try {
        const res = await fetch(`/librarian/books/${bookId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) {
            alert(data.message || 'Failed to delete book');
            return;
        }
        showToast('Book deleted successfully', 'success');
    } catch (e) {
        console.error(e);
        alert('Failed to delete book');
    }
}

function showToast(message, type) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    const toastIcon = document.getElementById('toastIcon');
    if (!toast || !toastMessage || !toastIcon) return;
    toastMessage.textContent = message;
    toastIcon.className = 'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center mr-3 ' + (type === 'success' ? 'bg-emerald-100 text-emerald-600' : 'bg-blue-100 text-blue-600');
    toast.style.transform = 'translateX(0)';
    setTimeout(() => { toast.style.transform = 'translateX(120%)'; }, 2000);
}

// Bulk Upload Functions
let selectedBulkFiles = [];

function openBulkUploadModal() {
    const modal = document.getElementById('bulkUploadModal');
    const card = document.getElementById('bulkUploadModalCard');
    
    modal.classList.remove('hidden');
    requestAnimationFrame(() => {
        card.classList.remove('scale-95', 'opacity-0');
        card.classList.add('scale-100', 'opacity-100');
    });
    
    initializeBulkUpload();
}

function closeBulkUploadModal() {
    const modal = document.getElementById('bulkUploadModal');
    const card = document.getElementById('bulkUploadModalCard');
    
    card.classList.remove('scale-100', 'opacity-100');
    card.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        selectedBulkFiles = [];
        document.getElementById('bulkFileList').classList.add('hidden');
        document.getElementById('bulkFileItems').innerHTML = '';
        document.getElementById('bulkFileInput').value = '';
        document.getElementById('bulkUploadBtn').disabled = true;
    }, 200);
}

function initializeBulkUpload() {
    const uploadArea = document.getElementById('bulkUploadArea');
    const fileInput = document.getElementById('bulkFileInput');
    
    // Click to upload
    uploadArea.addEventListener('click', (e) => {
        if (e.target.tagName !== 'BUTTON') {
            fileInput.click();
        }
    });
    
    // File selection
    fileInput.addEventListener('change', handleBulkFileSelection);
    
    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('border-blue-400', 'bg-blue-50');
    });
    
    uploadArea.addEventListener('dragleave', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('border-blue-400', 'bg-blue-50');
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('border-blue-400', 'bg-blue-50');
        handleBulkFileSelection({ target: { files: e.dataTransfer.files } });
    });
}

function handleBulkFileSelection(event) {
    const files = Array.from(event.target.files);
    selectedBulkFiles = files.filter(file => {
        const validTypes = ['application/pdf', 'application/epub+zip', 'application/msword', 
                          'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                          'image/jpeg', 'image/png'];
        const maxSize = 100 * 1024 * 1024; // 100MB
        
        return validTypes.includes(file.type) && file.size <= maxSize;
    });
    
    if (selectedBulkFiles.length > 0) {
        displayBulkFiles();
        document.getElementById('bulkUploadBtn').disabled = false;
    } else {
        document.getElementById('bulkFileList').classList.add('hidden');
        document.getElementById('bulkUploadBtn').disabled = true;
    }
}

function displayBulkFiles() {
    const fileList = document.getElementById('bulkFileList');
    const fileItems = document.getElementById('bulkFileItems');
    
    fileList.classList.remove('hidden');
    fileItems.innerHTML = '';
    
    selectedBulkFiles.forEach((file, index) => {
        const fileItem = document.createElement('div');
        fileItem.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-lg';
        fileItem.innerHTML = `
            <div class="flex items-center gap-3">
                <i class="fas ${getFileIcon(file.type)} text-blue-600"></i>
                <div>
                    <p class="text-sm font-medium text-gray-900">${file.name}</p>
                    <p class="text-xs text-gray-500">${formatFileSize(file.size)}</p>
                </div>
            </div>
            <button onclick="removeBulkFile(${index})" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times"></i>
            </button>
        `;
        fileItems.appendChild(fileItem);
    });
}

function getFileIcon(fileType) {
    if (fileType === 'application/pdf') return 'fa-file-pdf';
    if (fileType === 'application/epub+zip') return 'fa-book';
    if (fileType.includes('word')) return 'fa-file-word';
    if (fileType.includes('image')) return 'fa-file-image';
    return 'fa-file';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function removeBulkFile(index) {
    selectedBulkFiles.splice(index, 1);
    displayBulkFiles();
    
    if (selectedBulkFiles.length === 0) {
        document.getElementById('bulkFileList').classList.add('hidden');
        document.getElementById('bulkUploadBtn').disabled = true;
    }
}

async function startBulkUpload() {
    const btn = document.getElementById('bulkUploadBtn');
    const btnText = document.getElementById('bulkUploadBtnText');
    const loader = document.getElementById('bulkUploadLoader');
    
    // Get form data
    const resourceType = document.getElementById('bulkResourceType').value;
    const category = document.getElementById('bulkCategory').value;
    const course = document.getElementById('bulkCourse').value;
    
    // Show loading state
    btn.disabled = true;
    btnText.classList.add('hidden');
    loader.classList.remove('hidden');
    
    try {
        const formData = new FormData();
        
        // Use proper array format for Laravel to recognize files[]
        selectedBulkFiles.forEach((file) => {
            formData.append('files[]', file);
        });
        
        formData.append('resource_type', resourceType);
        formData.append('category', category);
        formData.append('course', course);
        
        const response = await fetch('/librarian/books/bulk-upload', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(`Successfully uploaded ${data.uploaded_count} files!`, 'success');
            closeBulkUploadModal();
            // Refresh DataTable after modal animation completes
            setTimeout(function() {
                if (window.table) {
                    window.table.draw(false);
                } else if (typeof $('#booksTable').DataTable === 'function') {
                    $('#booksTable').DataTable().draw(false);
                }
                // Force reload page as fallback to ensure new items show
                window.location.reload();
            }, 500);
        } else {
            // Show detailed error message
            let errorMsg = data.message || 'Upload failed';
            if (data.errors) {
                errorMsg = Object.values(data.errors).flat().join(', ');
            }
            showToast(errorMsg, 'error');
        }
        
    } catch (error) {
        console.error('Bulk upload error:', error);
        showToast('Upload failed. Please try again. Make sure files are under 100MB each.', 'error');
    } finally {
        // Hide loading state
        btn.disabled = false;
        btnText.classList.remove('hidden');
        loader.classList.add('hidden');
    }
}

function toggleBookStatus(id, currentStatus) {
    const isDisabling = (currentStatus === 'available');
    const actionText = isDisabling ? 'disable' : 'enable';
    
    if (!confirm(`Are you sure you want to ${actionText} this resource?`)) {
        return;
    }

    fetch(`/librarian/books/${id}/toggle-status`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof showToast === 'function') {
                showToast(data.message, 'success');
            } else {
                alert(data.message);
            }
            if (window.table) {
                window.table.draw(false);
            } else if (typeof $('#booksTable').DataTable === 'function') {
                $('#booksTable').DataTable().draw(false);
            }
        } else {
            alert(data.message || 'Error updating status.');
        }
    })
    .catch(err => {
        console.error('Error toggling resource status:', err);
        alert('An error occurred while updating resource status.');
    });
}
</script>
@endsection
