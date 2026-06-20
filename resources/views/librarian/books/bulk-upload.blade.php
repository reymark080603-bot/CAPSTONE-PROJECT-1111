@extends('layouts.librarian')

@section('title', 'Bulk Upload')

@section('content')
<div class="p-6">
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Bulk Upload E-Resources</h1>
                <p class="text-gray-600 mt-1">Upload multiple PDF files at once</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('librarian.books.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Books
                </a>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div id="alert-container"></div>

    <!-- Upload Form -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-file-pdf mr-2 text-red-600"></i>Upload PDF Files
                </h2>
                
                <form id="bulk-upload-form" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    
                    <!-- PDF Files Input -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select PDF Files <span class="text-red-500">*</span>
                            <span class="text-gray-500 font-normal">(Multiple files allowed)</span>
                        </label>
                        <div class="flex items-center justify-center w-full">
                            <label for="pdfs" class="flex flex-col items-center justify-center w-full h-40 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    <p class="text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                    <p class="text-xs text-gray-400 mt-1">PDF files only (max 100MB each)</p>
                                </div>
                                <input id="pdfs" name="pdfs[]" type="file" class="hidden" accept=".pdf" multiple />
                            </label>
                        </div>
                        
                        <!-- Selected Files List -->
                        <div id="file-list" class="mt-4 space-y-2 hidden">
                            <h4 class="text-sm font-medium text-gray-700">Selected Files:</h4>
                            <div id="files-container" class="space-y-1"></div>
                        </div>
                        
                        @error('pdfs')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Info Box -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-blue-800 mb-2">
                            <i class="fas fa-info-circle mr-1"></i>Filename Format
                        </h4>
                        <p class="text-sm text-blue-700">
                            Use this format for filenames to auto-extract metadata:
                        </p>
                        <code class="block mt-2 bg-white px-2 py-1 rounded text-xs text-gray-700">
                            Title - Author - Year - Program - Type.pdf
                        </code>
                        <p class="text-xs text-blue-600 mt-2">
                            Example: <code>Beginning PHP and MySQL - Jason Gilmore - 2018 - BSIT - Book.pdf</code>
                        </p>
                        <p class="text-xs text-blue-600 mt-1">
                            Allowed type values: <code>Book</code>, <code>E-Journal</code>, <code>Thesis</code>
                        </p>
                        <p class="text-xs text-blue-500 mt-1">
                            If type is missing or invalid, it will default to <code>Book</code>.
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button type="submit" id="upload-btn" class="px-6 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <i class="fas fa-upload mr-2"></i>
                            <span>Upload PDFs</span>
                        </button>
                    </div>
                </form>

                <!-- Progress Section -->
                <div id="progress-section" class="hidden mt-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Uploading books...</span>
                        <span id="progress-percent" class="text-sm text-gray-500">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div id="progress-bar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Results Section -->
                <div id="results-section" class="hidden mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Upload Results</h3>
                    <div id="results-content"></div>
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-6">
            <!-- Instructions -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-info-circle mr-2 text-blue-600"></i>Instructions
                </h3>
                <ol class="list-decimal list-inside space-y-3 text-sm text-gray-600">
                    <li>Prepare your PDF files with proper naming</li>
                    <li>Name format: <strong>Title - Author - Year - Program - Type.pdf</strong></li>
                    <li>Click to select or drag and drop files</li>
                    <li>Click "Upload PDFs" to begin</li>
                    <li>Review the results below</li>
                </ol>
            </div>

            <!-- Format Examples -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-list mr-2 text-green-600"></i>Filename Examples
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="p-2 bg-gray-50 rounded">
                        <code class="text-xs text-green-600">Introduction to Programming - John Smith - 2023 - BSIT - Book.pdf</code>
                    </div>
                    <div class="p-2 bg-gray-50 rounded">
                        <code class="text-xs text-green-600">Database Systems - Jane Doe - 2022 - BSCS - E-Journal.pdf</code>
                    </div>
                    <div class="p-2 bg-gray-50 rounded">
                        <code class="text-xs text-green-600">Research Methods - Dr. Wilson - 2024 - BSED - Thesis.pdf</code>
                    </div>
                    <div class="p-2 bg-yellow-50 rounded border border-yellow-200">
                        <p class="text-xs text-yellow-600">If filename is incomplete:</p>
                        <code class="text-xs text-yellow-700">MyBook.pdf -> Title: MyBook, Author: Unknown Author</code>
                    </div>
                </div>
            </div>

            <!-- Storage Info -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-hdd mr-2 text-yellow-600"></i>Storage Status
                </h3>
                <div id="storage-info" class="text-sm text-gray-600">
                    <div class="flex justify-between mb-2">
                        <span>Status:</span>
                        <span id="storage-status" class="text-gray-400">Loading...</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span>PDF Files:</span>
                        <span id="pdf-count" class="text-gray-400">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Total Size:</span>
                        <span id="total-size" class="text-gray-400">-</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
$(document).ready(function() {
    // CSRF token
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Configure PDF.js worker
    if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    }

    // Store generated thumbnails
    let generatedThumbnails = {};

    // Store selected files array
    let selectedFiles = [];

    // Helper functions for escaping HTML and selectors
    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function escapeSelector(val) {
        if (!val) return '';
        return val.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
    }

    // File input change
    $('#pdfs').on('change', function(e) {
        const files = e.target.files;
        
        if (files && files.length > 0) {
            // Overwrite selectedFiles with new files when input changes
            selectedFiles = Array.from(files);
            
            // Cleanup generatedThumbnails for files that are no longer selected
            const fileNames = new Set(selectedFiles.map(f => f.name));
            for (const key of Object.keys(generatedThumbnails)) {
                if (!fileNames.has(key)) {
                    delete generatedThumbnails[key];
                }
            }
            
            renderFileList();
        }
    });

    // Render the file list UI
    function renderFileList() {
        const filesContainer = $('#files-container');
        filesContainer.html('');
        
        if (selectedFiles.length > 0) {
            $('#file-list').removeClass('hidden');
            
            selectedFiles.forEach((file, index) => {
                const hasThumb = !!generatedThumbnails[file.name];
                const statusHtml = hasThumb 
                    ? '<i class="fas fa-image text-green-500" title="Thumbnail generated"></i>' 
                    : '<i class="fas fa-spinner fa-spin text-blue-500"></i>';

                const row = `
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded text-sm file-row">
                        <div class="flex items-center flex-1 min-w-0 mr-4">
                            <i class="fas fa-file-pdf text-red-500 mr-2 flex-shrink-0"></i>
                            <span class="text-gray-700 truncate" title="${escapeHtml(file.name)}">${file.name}</span>
                        </div>
                        <div class="flex items-center space-x-3 flex-shrink-0">
                            <span class="text-gray-500 text-xs">${formatBytes(file.size)}</span>
                            <div class="thumb-status" data-name="${escapeHtml(file.name)}">${statusHtml}</div>
                            <button type="button" class="remove-file-btn text-gray-400 hover:text-red-500 transition-colors p-1" data-index="${index}" title="Remove file">
                                <i class="fas fa-times text-base"></i>
                            </button>
                        </div>
                    </div>
                `;
                filesContainer.append(row);
                
                if (!hasThumb) {
                    generateThumbnailForFile(file);
                }
            });
        } else {
            $('#file-list').addClass('hidden');
        }
        
        syncInputFiles();
    }

    // Sync array to input files
    function syncInputFiles() {
        try {
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            document.getElementById('pdfs').files = dt.files;
        } catch (err) {
            console.error('Error syncing file input files:', err);
        }
    }

    // Generate thumbnail and update status dynamically
    function generateThumbnailForFile(file) {
        if (typeof pdfjsLib === 'undefined') return;
        
        const fileReader = new FileReader();
        fileReader.onload = function(ev) {
            const typedarray = new Uint8Array(ev.target.result);
            
            pdfjsLib.getDocument(typedarray).promise.then(function(pdf) {
                return pdf.getPage(1);
            }).then(function(page) {
                const viewport = page.getViewport({ scale: 1.0 });
                const scale = Math.min(400 / viewport.width, 600 / viewport.height);
                const scaledViewport = page.getViewport({ scale: scale });

                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.height = scaledViewport.height;
                canvas.width = scaledViewport.width;

                const renderContext = {
                    canvasContext: context,
                    viewport: scaledViewport
                };
                
                return page.render(renderContext).promise.then(function() {
                    generatedThumbnails[file.name] = canvas.toDataURL('image/jpeg', 0.85);
                    $(`.thumb-status[data-name="${escapeSelector(file.name)}"]`).html('<i class="fas fa-image text-green-500" title="Thumbnail generated"></i>');
                });
            }).catch(function(error) {
                console.error('Error generating thumbnail for', file.name, error);
                $(`.thumb-status[data-name="${escapeSelector(file.name)}"]`).html('');
            });
        };
        fileReader.readAsArrayBuffer(file);
    }

    // Handle file removal clicking the 'x'
    $(document).on('click', '.remove-file-btn', function(e) {
        e.preventDefault();
        const index = parseInt($(this).attr('data-index'));
        if (!isNaN(index) && index >= 0 && index < selectedFiles.length) {
            const removedFile = selectedFiles[index];
            selectedFiles.splice(index, 1);
            
            // Clean up its thumbnail cache if it's no longer in the list (in case of duplicate names)
            const stillExists = selectedFiles.some(f => f.name === removedFile.name);
            if (!stillExists) {
                delete generatedThumbnails[removedFile.name];
            }
            
            renderFileList();
        }
    });

    // Form submission
    $('#bulk-upload-form').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const uploadBtn = $('#upload-btn');

        // Check files first
        const fileInput = document.getElementById('pdfs');
        if (!fileInput.files || fileInput.files.length === 0) {
            showAlert('error', '<i class="fas fa-exclamation-circle mr-2"></i>Please select at least one PDF file.');
            return;
        }

        // Validate all files are PDFs
        for (let i = 0; i < fileInput.files.length; i++) {
            const file = fileInput.files[i];
            if (file.type !== 'application/pdf') {
                showAlert('error', '<i class="fas fa-exclamation-circle mr-2"></i>All files must be PDF format. Found: ' + file.name);
                return;
            }
        }

        // Always append thumbnails - small JPEGs, not the cause of POST size issues
        for (const [filename, base64] of Object.entries(generatedThumbnails)) {
            formData.append('thumbnails[' + filename + ']', base64);
        }

        // Disable button
        uploadBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...');

        // Show progress
        $('#progress-section').removeClass('hidden');
        $('#results-section').addClass('hidden');
        $('#progress-bar').css('width', '10%');

        $.ajax({
            url: '{{ route("librarian.books.bulk.process") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = Math.round((e.loaded / e.total) * 100);
                        $('#progress-bar').css('width', percentComplete + '%');
                        $('#progress-percent').text(percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                $('#progress-bar').css('width', '100%');
                $('#progress-percent').text('100%');
                
                if (response.success) {
                    showAlert('success', '<i class="fas fa-check-circle mr-2"></i>' + response.message);
                    displayResults(response.data);
                    
                    // Clear file input
                    $('#pdfs').val('');
                    $('#file-list').addClass('hidden');
                    generatedThumbnails = {};
                    selectedFiles = [];
                } else {
                    showAlert('error', '<i class="fas fa-exclamation-circle mr-2"></i>' + response.message);
                }
            },
            error: function(xhr) {
                let errorMessage = 'Upload failed (HTTP ' + xhr.status + ').';

                // Log full response for debugging
                console.error('Upload error response:', xhr.responseText);

                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    if (xhr.responseJSON.errors) {
                        errorMessage += '<br>' + Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    }
                    if (xhr.responseJSON.error) {
                        errorMessage += '<br><small>' + xhr.responseJSON.error + '</small>';
                    }
                }

                showAlert('error', '<i class="fas fa-exclamation-circle mr-2"></i>' + errorMessage);
                $('#progress-section').addClass('hidden');
            },
            complete: function() {
                uploadBtn.prop('disabled', false).html('<i class="fas fa-upload mr-2"></i><span>Upload PDFs</span>');
            }
        });
    });

    // Load storage info
    loadStorageInfo();

    function loadStorageInfo() {
        $.ajax({
            url: '{{ route("librarian.books.bulk.storage-status") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const status = response.storage_status;
                    $('#storage-status').html(status.linked 
                        ? '<span class="text-green-600"><i class="fas fa-check-circle"></i> Connected</span>' 
                        : '<span class="text-red-600"><i class="fas fa-times-circle"></i> Not Connected</span>');
                    $('#pdf-count').text(status.pdf_count);
                    const totalSizeMb = (Number(status.pdf_size_mb || 0) + Number(status.cover_size_mb || 0)).toFixed(2);
                    $('#total-size').text(totalSizeMb + ' MB');
                }
            },
            error: function() {
                $('#storage-status').html('<span class="text-gray-400">Unable to load</span>');
            }
        });
    }

    function displayResults(data) {
        const resultsHtml = `
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                        <div class="text-2xl font-bold text-green-600">${data.uploaded}</div>
                        <div class="text-xs text-gray-500">Successfully Uploaded</div>
                    </div>
                    <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                        <div class="text-2xl font-bold text-red-600">${data.failed}</div>
                        <div class="text-xs text-gray-500">Failed</div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                        <div class="text-2xl font-bold text-blue-600">${data.covers_generated || 0}</div>
                        <div class="text-xs text-gray-500">Covers Generated</div>
                    </div>
                    <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                        <div class="text-2xl font-bold text-yellow-600">${data.duplicates_skipped || 0}</div>
                        <div class="text-xs text-gray-500">Duplicates Skipped</div>
                    </div>
                </div>
                
                ${data.created_books && data.created_books.length > 0 ? `
                    <div class="mb-4">
                        <h4 class="font-medium text-gray-700 mb-2">Recently Added Books:</h4>
                        <ul class="space-y-1 text-sm text-gray-600 max-h-40 overflow-y-auto">
                            ${data.created_books.map(book => `
                                <li class="flex items-center justify-between p-2 bg-white rounded">
                                    <span><i class="fas fa-book mr-2 text-green-500"></i>${book.title}</span>
                                    <span class="text-xs text-gray-500">${book.author}</span>
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                ` : ''}
                
                ${data.errors && data.errors.length > 0 ? `
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Errors:</h4>
                        <ul class="space-y-1 text-sm text-red-600 max-h-40 overflow-y-auto">
                            ${data.errors.map(err => `<li><i class="fas fa-times-circle mr-2"></i>${err}</li>`).join('')}
                        </ul>
                    </div>
                ` : ''}
            </div>
        `;
        
        $('#results-content').html(resultsHtml);
        $('#results-section').removeClass('hidden');
    }

    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
        const html = `
            <div class="alert-${type} ${alertClass} border rounded-lg p-4 mb-4">
                ${message}
            </div>
        `;
        $('#alert-container').html(html);
        
        // Auto-hide after 10 seconds
        setTimeout(() => {
            $('.alert-' + type).fadeOut();
        }, 10000);
    }

    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
});
</script>
@endsection
