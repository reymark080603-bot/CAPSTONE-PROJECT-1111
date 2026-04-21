@extends('layouts.librarian')

@section('title', 'Add New Book')

@section('content')
<!-- Error Messages -->
@if(session('error'))
<div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 flex items-center max-w-2xl mx-auto w-full">
    <div class="flex-shrink-0">
        <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
    </div>
    <div class="ml-3">
        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
        @if(session('duplicate_book_id'))
        <div class="mt-2">
            <a href="{{ route('librarian.books.edit', session('duplicate_book_id')) }}" class="text-red-700 underline text-sm hover:text-red-900">
                Click here to edit the existing book
            </a>
        </div>
        @endif
    </div>
    <div class="ml-auto pl-3">
        <button type="button" class="text-red-600 hover:text-red-800" onclick="this.parentElement.parentElement.style.display='none'">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif

<!-- Add New Book Header -->
<div class="books-header">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center mb-2">
                <h1 class="text-3xl font-bold text-gray-900">Add New Resource</h1>
            </div>
            <p class="text-gray-600">Add books, e-journals, thesis, and other library resources</p>
        </div>
    </div>
</div>

<!-- Add New Book Form -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <!-- Form Header -->
    <div class="bg-blue-600 px-6 py-4 rounded-t-xl">
        <div class="flex items-center">
            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center mr-3">
                <i class="fas fa-book text-white"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-white">Resource Information</h3>
                <p class="text-blue-100 text-sm">Complete all required fields marked with *</p>
            </div>
        </div>
    </div>
    
    <form id="addBookForm" class="p-8" action="{{ route('librarian.books.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <!-- Resource Type Selection -->
        <div class="mb-8">
            <div class="book-form-section">
                <div class="flex items-center mb-6">
                    <i class="fas fa-layer-group text-purple-600 mr-3 text-lg"></i>
                    <h4 class="text-lg font-semibold text-gray-900">Resource Type</h4>
                    <span class="ml-2 px-2 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-full">Required</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="resource-type-option">
                        <input type="radio" id="resource_book" name="resource_type" value="book" class="hidden" checked>
                        <label for="resource_book" class="block border-2 border-blue-500 bg-blue-50 rounded-lg p-4 cursor-pointer hover:bg-blue-100 transition-colors text-center">
                            <i class="fas fa-book text-blue-600 text-2xl mb-2"></i>
                            <h5 class="font-semibold text-gray-900">Book</h5>
                            <p class="text-sm text-gray-600">Traditional books, textbooks, reference materials</p>
                        </label>
                    </div>
                    <div class="resource-type-option">
                        <input type="radio" id="resource_journal" name="resource_type" value="e_journal" class="hidden">
                        <label for="resource_journal" class="block border-2 border-gray-300 bg-white rounded-lg p-4 cursor-pointer hover:bg-gray-50 transition-colors text-center">
                            <i class="fas fa-newspaper text-gray-600 text-2xl mb-2"></i>
                            <h5 class="font-semibold text-gray-900">E-Journal</h5>
                            <p class="text-sm text-gray-600">Electronic journals, periodicals, magazines</p>
                        </label>
                    </div>
                    <div class="resource-type-option">
                        <input type="radio" id="resource_thesis" name="resource_type" value="thesis" class="hidden">
                        <label for="resource_thesis" class="block border-2 border-gray-300 bg-white rounded-lg p-4 cursor-pointer hover:bg-gray-50 transition-colors text-center">
                            <i class="fas fa-graduation-cap text-gray-600 text-2xl mb-2"></i>
                            <h5 class="font-semibold text-gray-900">Thesis</h5>
                            <p class="text-sm text-gray-600">Research papers, dissertations, academic work</p>
                        </label>
                    </div>
                </div>
                @error('resource_type')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>
        </div>
        
        <!-- Form Content -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Column -->
            <div class="space-y-6">
                <!-- Basic Information Card -->
                <div class="book-form-section">
                    <div class="space-y-4">
                        <!-- Title Field -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-book text-gray-500 mr-1"></i>
                                <span id="titleLabel">Book Title</span> *
                            </label>
                            <input type="text" id="title" name="title" required 
                                   placeholder="Enter the complete title" 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                   value="{{ old('title') }}">
                            @error('title')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <!-- ISBN Field -->
                        <div>
                            <label for="isbn" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-barcode text-gray-500 mr-1"></i>
                                ISBN
                            </label>
                            <input type="text" id="isbn" name="isbn" 
                                   placeholder="Enter ISBN (optional, helps prevent duplicates)" 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                   value="{{ old('isbn') }}">
                            @error('isbn')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <!-- Author Field -->
                        <div>
                            <label for="author" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user-edit text-gray-500 mr-1"></i>
                                <span id="authorLabel">Author</span> *
                            </label>
                            <input type="text" id="author" name="author" required 
                                   placeholder="Enter full name" 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                   value="{{ old('author') }}">
                            @error('author')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <!-- Publisher Field -->
                        <div>
                            <label for="publisher" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-building text-gray-500 mr-1"></i>
                                Publisher
                            </label>
                            <input type="text" id="publisher" name="publisher" 
                                   placeholder="Enter publisher name" 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                   value="{{ old('publisher') }}">
                        </div>
                        
                        <!-- E-Journal Specific Fields (Hidden by default) -->
                        <div id="e_journal_fields" class="hidden space-y-4">
                            <div class="border-t pt-4">
                                <h5 class="text-sm font-semibold text-gray-700 mb-3">
                                    <i class="fas fa-newspaper text-blue-500 mr-1"></i>
                                    E-Journal Details
                                </h5>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="volume" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-layer-group text-gray-500 mr-1"></i>
                                            Volume
                                        </label>
                                        <input type="text" id="volume" name="volume" 
                                               placeholder="e.g., Vol. 12" 
                                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                               value="{{ old('volume') }}">
                                    </div>
                                    <div>
                                        <label for="issue" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-bookmark text-gray-500 mr-1"></i>
                                            Issue
                                        </label>
                                        <input type="text" id="issue" name="issue" 
                                               placeholder="e.g., Issue 3" 
                                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                               value="{{ old('issue') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Thesis Specific Fields (Hidden by default) -->
                        <div id="thesis_fields" class="hidden space-y-4">
                            <div class="border-t pt-4">
                                <h5 class="text-sm font-semibold text-gray-700 mb-3">
                                    <i class="fas fa-graduation-cap text-purple-500 mr-1"></i>
                                    Thesis Details
                                </h5>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="advisor" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-user-tie text-gray-500 mr-1"></i>
                                            Thesis Advisor
                                        </label>
                                        <input type="text" id="advisor" name="advisor" 
                                               placeholder="Advisor's full name" 
                                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                               value="{{ old('advisor') }}">
                                    </div>
                                    <div>
                                        <label for="defense_date" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-calendar-check text-gray-500 mr-1"></i>
                                            Defense Date
                                        </label>
                                        <input type="date" id="defense_date" name="defense_date" 
                                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                               value="{{ old('defense_date') }}">
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label for="degree" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-award text-gray-500 mr-1"></i>
                                        Degree
                                    </label>
                                    <select id="degree" name="degree" 
                                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                        <option value="">Select Degree</option>
                                        <option value="Bachelor's">Bachelor's Degree</option>
                                        <option value="Master's">Master's Degree</option>
                                        <option value="PhD">Doctoral Degree (PhD)</option>
                                        <option value="Dissertation">Dissertation</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Details Card -->
                <div class="book-form-section">
                    <div class="flex items-center mb-6">
                        <i class="fas fa-list-alt text-green-600 mr-3 text-lg"></i>
                        <h4 class="text-lg font-semibold text-gray-900">Additional Details</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Published Year -->
                        <div>
                            <label for="published_year" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar text-gray-500 mr-1"></i>
                                Published Year
                            </label>
                            <input type="number" id="published_year" name="published_year" min="1000" max="{{ date('Y') }}"
                                   placeholder="{{ date('Y') }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                   value="{{ old('published_year') }}">
                        </div>

                        <!-- Language -->
                        <div>
                            <label for="language" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-globe text-gray-500 mr-1"></i>
                                Language
                            </label>
                            <select id="language" name="language" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                <option value="English" {{ old('language') == 'English' ? 'selected' : '' }}>English</option>
                                <option value="Filipino" {{ old('language') == 'Filipino' ? 'selected' : '' }}>Filipino</option>
                                <option value="Other" {{ old('language') == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                    </div>
                </div>

                <!-- Academic Information Card -->
                <div class="book-form-section">
                    <div class="flex items-center mb-6">
                        <i class="fas fa-graduation-cap text-purple-600 mr-3 text-lg"></i>
                        <h4 class="text-lg font-semibold text-gray-900">Academic Information</h4>
                    </div>
                    <div class="space-y-4">
                        <!-- Category -->
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-tags text-gray-500 mr-1"></i>
                                Category
                            </label>
                            <select id="category" name="category" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                <option value="">Select Category</option>
                                <option value="Science" {{ old('category') == 'Science' ? 'selected' : '' }}>Science</option>
                                <option value="Technology" {{ old('category') == 'Technology' ? 'selected' : '' }}>Technology</option>
                                <option value="Engineering" {{ old('category') == 'Engineering' ? 'selected' : '' }}>Engineering</option>
                                <option value="Mathematics" {{ old('category') == 'Mathematics' ? 'selected' : '' }}>Mathematics</option>
                                <option value="Literature" {{ old('category') == 'Literature' ? 'selected' : '' }}>Literature</option>
                                <option value="History" {{ old('category') == 'History' ? 'selected' : '' }}>History</option>
                                <option value="Philosophy" {{ old('category') == 'Philosophy' ? 'selected' : '' }}>Philosophy</option>
                                <option value="Business" {{ old('category') == 'Business' ? 'selected' : '' }}>Business</option>
                                <option value="Arts" {{ old('category') == 'Arts' ? 'selected' : '' }}>Arts</option>
                                <option value="Education" {{ old('category') == 'Education' ? 'selected' : '' }}>Education</option>
                                <option value="Health" {{ old('category') == 'Health' ? 'selected' : '' }}>Health</option>
                            </select>
                        </div>

                        <!-- Course -->
                        <div>
                            <label for="course" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-university text-gray-500 mr-1"></i>
                                Target Program
                            </label>
                            @php($crs = isset($courses) ? $courses : ['BSE','BSHM','BSIT','BSN','BSTM'])
                            <select id="course" name="course" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                <option value="">All Programs</option>
                                @foreach($crs as $c)
                                    <option value="{{ $c }}" {{ old('course') == $c ? 'selected' : '' }}>{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Right Column - File Uploads Only -->
            <div class="space-y-6">
                
                <!-- Ebook File Card -->
                <div class="book-form-section">
                    <div class="flex items-center mb-6">
                        <i class="fas fa-file-alt text-blue-600 mr-3 text-lg"></i>
                        <h4 class="text-lg font-semibold text-gray-900">Ebook File</h4>
                    </div>
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 mb-4">
                            <i class="fas fa-info-circle mr-1"></i>
                            Upload the book content file. Supports PDF, EPUB, and DOC/DOCX formats (up to 50MB).
                        </p>
                        
                        <!-- Single File Upload Area -->
                        <div>
                            <label for="ebookInput" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-upload text-gray-500 mr-1"></i>
                                Book File (PDF, EPUB, DOC/DOCX)
                            </label>
                            <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-blue-400 transition-colors cursor-pointer" id="ebookUploadArea">
                                <div class="space-y-4">
                                    <div class="mx-auto w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center">
                                        <i class="fas fa-cloud-upload-alt text-blue-500 text-2xl" id="uploadIcon"></i>
                                    </div>
                                    <div>
                                        <p class="text-gray-600 font-medium">Click to upload or drag and drop</p>
                                        <p class="text-sm text-gray-500">PDF, EPUB, DOC, DOCX up to 50MB</p>
                                        <p class="text-xs text-gray-400 mt-2">
                                            <i class="fas fa-file-pdf text-red-500"></i> PDF • 
                                            <i class="fas fa-book text-purple-500"></i> EPUB • 
                                            <i class="fas fa-file-word text-blue-500"></i> DOC/DOCX
                                        </p>
                                    </div>
                                    <input type="file" id="ebookInput" name="ebook_file" 
                                           accept=".pdf,.epub,.doc,.docx,application/pdf,application/epub+zip,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" 
                                           class="hidden">
                                    @error('ebook_file')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- File Preview -->
                            <div class="hidden mt-4" id="ebookPreview">
                                <div class="flex items-center p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200">
                                    <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center mr-4" id="fileTypeIcon">
                                        <!-- Icon will be dynamically set based on file type -->
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate" id="ebookFileName"></p>
                                        <p class="text-xs text-gray-500" id="ebookFileSize"></p>
                                        <p class="text-xs text-blue-600 font-medium" id="ebookFileType"></p>
                                    </div>
                                    <button type="button" class="ml-4 flex-shrink-0 w-8 h-8 bg-red-100 text-red-600 rounded-full flex items-center justify-center hover:bg-red-200 transition-colors" id="removeEbook">
                                        <i class="fas fa-times text-sm"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Upload Tips -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <i class="fas fa-lightbulb text-blue-500 mt-1 mr-3"></i>
                                <div class="text-sm">
                                    <p class="font-medium text-blue-900 mb-2">File Format Guidelines:</p>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                                        <div class="flex items-center">
                                            <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                                            <div>
                                                <p class="font-medium text-red-700">PDF Files</p>
                                                <p class="text-red-600">Best for browser reading</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-book text-purple-500 mr-2"></i>
                                            <div>
                                                <p class="font-medium text-purple-700">EPUB Files</p>
                                                <p class="text-purple-600">Great for mobile devices</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-file-word text-blue-500 mr-2"></i>
                                            <div>
                                                <p class="font-medium text-blue-700">DOC/DOCX</p>
                                                <p class="text-blue-600">Converted for web view</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Cover Photo Card -->
                <div class="book-form-section">
                    <div class="flex items-center mb-6">
                        <i class="fas fa-image text-orange-600 mr-3 text-lg"></i>
                        <h4 class="text-lg font-semibold text-gray-900">Book Cover</h4>
                    </div>
                    <div class="space-y-4">
                        <!-- Upload Area -->
                        <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-blue-400 transition-colors cursor-pointer" id="uploadArea">
                            <div class="space-y-4">
                                <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl"></i>
                                </div>
                                <div>
                                    <p class="text-gray-600 font-medium">Click to upload or drag and drop</p>
                                    <p class="text-sm text-gray-500">PNG, JPG, GIF up to 2MB</p>
                                </div>
                                <input type="file" name="cover_photo" accept="image/*" class="hidden" id="coverInput">
                                @error('cover_photo')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Preview Area -->
                        <div class="hidden" id="previewArea">
                            <div class="relative inline-block">
                                <img id="coverPreview" src="" alt="Cover Preview" class="w-32 h-40 object-cover border border-gray-300 rounded-lg">
                                <button type="button" id="removeImage" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition-colors">
                                    ×
                                </button>
                            </div>
                            <p class="text-sm text-gray-600 mt-2">Cover image ready</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Description Section (Full Width) -->
        <div class="mt-8">
            <div class="book-form-section">
                <div class="flex items-center mb-6">
                    <i class="fas fa-align-left text-indigo-600 mr-3 text-lg"></i>
                    <h4 class="text-lg font-semibold text-gray-900">Description</h4>
                </div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-edit text-gray-500 mr-1"></i>
                    Book Description
                </label>
                <textarea id="description" name="description" rows="4" 
                          class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" 
                          placeholder="Write a brief description about the book content, key topics, or target audience...">{{ old('description') }}</textarea>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="mt-8 pt-6 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Fields marked with * are required
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('librarian.books.index') }}" class="px-6 py-3 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-200 flex items-center gap-2">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 flex items-center gap-2 shadow-lg hover:shadow-xl">
                        <i class="fas fa-save" id="saveBtnIcon"></i>
                        <span id="saveBtnText">Save Book</span>
                        <div id="saveBtnLoader" class="hidden">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@endsection











@push('styles')
<style>
/* Book Form Section Styling */
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
    display: flex;
    align-items: center;
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

/* Remove button for image */
#removeImage {
    transition: all 0.2s ease;
}

#removeImage:hover {
    transform: scale(1.1);
}

/* Books header styling */
.books-header {
    margin-bottom: 2rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .books-header .flex {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
}
</style>
@endpush

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeFileUpload();
    initializeEbookFileUploads();
    initializeResourceTypeSelection();
    
    // Form submission handling
    const form = document.getElementById('addBookForm');
    const saveBtn = form.querySelector('button[type="submit"]');
    const saveBtnText = document.getElementById('saveBtnText');
    const saveBtnIcon = document.getElementById('saveBtnIcon');
    const saveBtnLoader = document.getElementById('saveBtnLoader');
    
    form.addEventListener('submit', function(e) {
        // Show loading state
        saveBtn.disabled = true;
        saveBtnText.classList.add('hidden');
        saveBtnIcon.classList.add('hidden');
        saveBtnLoader.classList.remove('hidden');
        
        // Don't prevent default - let the form submit normally
    });
});

// Resource type selection functionality
function initializeResourceTypeSelection() {
    const resourceTypeInputs = document.querySelectorAll('input[name="resource_type"]');
    const bookFields = document.getElementById('e_journal_fields');
    const journalFields = document.getElementById('e_journal_fields');
    const thesisFields = document.getElementById('thesis_fields');
    
    if (!resourceTypeInputs.length) return;
    
    resourceTypeInputs.forEach(input => {
        input.addEventListener('change', function() {
            updateResourceTypeFields(this.value);
            updateResourceTypeUI(this.value);
        });
    });
    
    // Initialize with default selection
    const selectedType = document.querySelector('input[name="resource_type"]:checked').value;
    updateResourceTypeFields(selectedType);
    updateResourceTypeUI(selectedType);
}

function updateResourceTypeFields(resourceType) {
    const journalFields = document.getElementById('e_journal_fields');
    const thesisFields = document.getElementById('thesis_fields');
    const titleLabel = document.getElementById('titleLabel');
    const authorLabel = document.getElementById('authorLabel');
    const titleInput = document.getElementById('title');
    const authorInput = document.getElementById('author');
    
    // Hide all specialized fields first
    if (journalFields) journalFields.classList.add('hidden');
    if (thesisFields) thesisFields.classList.add('hidden');
    
    // Update labels and placeholders based on resource type
    switch(resourceType) {
        case 'e_journal':
            if (titleLabel) titleLabel.textContent = 'Journal Title';
            if (authorLabel) authorLabel.textContent = 'Editor/Author';
            if (titleInput) titleInput.placeholder = 'Enter the journal title';
            if (authorInput) authorInput.placeholder = 'Enter editor or author name';
            if (journalFields) journalFields.classList.remove('hidden');
            break;
        case 'thesis':
            if (titleLabel) titleLabel.textContent = 'Thesis Title';
            if (authorLabel) authorLabel.textContent = 'Student/Author';
            if (titleInput) titleInput.placeholder = 'Enter the thesis title';
            if (authorInput) authorInput.placeholder = 'Enter student name';
            if (thesisFields) thesisFields.classList.remove('hidden');
            break;
        case 'book':
        default:
            if (titleLabel) titleLabel.textContent = 'Book Title';
            if (authorLabel) authorLabel.textContent = 'Author';
            if (titleInput) titleInput.placeholder = 'Enter the complete book title';
            if (authorInput) authorInput.placeholder = 'Enter author\'s full name';
            break;
    }
}

function updateResourceTypeUI(resourceType) {
    const resourceTypeOptions = document.querySelectorAll('.resource-type-option');
    
    resourceTypeOptions.forEach(option => {
        const input = option.querySelector('input[type="radio"]');
        const label = option.querySelector('label');
        
        if (input.value === resourceType) {
            // Selected state
            label.classList.remove('border-gray-300', 'bg-white');
            label.classList.add('border-blue-500', 'bg-blue-50');
            
            // Update icon colors
            const icon = label.querySelector('i.fa-book, i.fa-newspaper, i.fa-graduation-cap');
            if (icon) {
                icon.classList.remove('text-gray-600');
                if (resourceType === 'book') {
                    icon.classList.add('text-blue-600');
                } else if (resourceType === 'e_journal') {
                    icon.classList.add('text-blue-600');
                } else if (resourceType === 'thesis') {
                    icon.classList.add('text-purple-600');
                }
            }
        } else {
            // Unselected state
            label.classList.remove('border-blue-500', 'bg-blue-50');
            label.classList.add('border-gray-300', 'bg-white');
            
            // Reset icon colors
            const icon = label.querySelector('i.fa-book, i.fa-newspaper, i.fa-graduation-cap');
            if (icon) {
                icon.classList.remove('text-blue-600', 'text-purple-600');
                icon.classList.add('text-gray-600');
            }
        }
    });
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
        uploadArea.classList.add('drag-over');
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        
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

// Single ebook file upload functionality
function initializeEbookFileUploads() {
    const uploadArea = document.getElementById('ebookUploadArea');
    const fileInput = document.getElementById('ebookInput');
    const preview = document.getElementById('ebookPreview');
    const fileName = document.getElementById('ebookFileName');
    const fileSize = document.getElementById('ebookFileSize');
    const fileType = document.getElementById('ebookFileType');
    const fileTypeIcon = document.getElementById('fileTypeIcon');
    const removeBtn = document.getElementById('removeEbook');
    const uploadIcon = document.getElementById('uploadIcon');
    
    if (!uploadArea || !fileInput || !preview) return;
    
    const maxSize = 50 * 1024 * 1024; // 50MB
    const allowedTypes = {
        'pdf': {
            icon: 'fas fa-file-pdf',
            color: 'bg-red-100 text-red-600',
            label: 'PDF Document'
        },
        'epub': {
            icon: 'fas fa-book',
            color: 'bg-purple-100 text-purple-600', 
            label: 'EPUB Book'
        },
        'doc': {
            icon: 'fas fa-file-word',
            color: 'bg-blue-100 text-blue-600',
            label: 'Word Document'
        },
        'docx': {
            icon: 'fas fa-file-word',
            color: 'bg-blue-100 text-blue-600',
            label: 'Word Document'
        }
    };
    
    // Click to upload
    uploadArea.addEventListener('click', () => fileInput.click());
    
    // File selection
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            handleFileSelection(file);
        }
    });
    
    // Handle file selection
    function handleFileSelection(file) {
        // Get file extension
        const extension = file.name.split('.').pop().toLowerCase();
        
        // Validate file type
        if (!allowedTypes[extension]) {
            alert('Please select a valid file. Supported formats: PDF, EPUB, DOC, DOCX');
            fileInput.value = '';
            return;
        }
        
        // Validate file size
        if (file.size > maxSize) {
            alert('File size must be less than 50MB.');
            fileInput.value = '';
            return;
        }
        
        // Update preview
        const fileConfig = allowedTypes[extension];
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        fileType.textContent = fileConfig.label;
        
        // Update icon
        fileTypeIcon.className = `w-12 h-12 ${fileConfig.color} rounded-full flex items-center justify-center`;
        fileTypeIcon.innerHTML = `<i class="${fileConfig.icon} text-xl"></i>`;
        
        // Show preview, hide upload area
        uploadArea.classList.add('hidden');
        preview.classList.remove('hidden');
    }
    
    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Remove file
    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            fileInput.value = '';
            fileName.textContent = '';
            fileSize.textContent = '';
            fileType.textContent = '';
            uploadArea.classList.remove('hidden');
            preview.classList.add('hidden');
        });
    }
    
    // Drag and drop functionality
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('drag-over');
        uploadArea.style.borderColor = '#3b82f6';
        uploadArea.style.backgroundColor = '#eff6ff';
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        uploadArea.style.borderColor = '';
        uploadArea.style.backgroundColor = '';
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        uploadArea.style.borderColor = '';
        uploadArea.style.backgroundColor = '';
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelection(files[0]);
            // Manually set the files to the input
            const dt = new DataTransfer();
            dt.items.add(files[0]);
            fileInput.files = dt.files;
        }
    });
}
</script>
@endsection
