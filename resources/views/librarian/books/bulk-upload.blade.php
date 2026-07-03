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

    // Upload & Pre-rendering state
    let selectedFiles = []; // Array of { id, file, status, error, thumbnailStatus }
    let generatedThumbnails = {}; // filename -> base64
    let fileIdCounter = 0;
    
    let thumbnailQueue = []; // Queue of fileObj
    let isGeneratingThumbnail = false;

    // Sequential PDF cover rendering queue
    function enqueueThumbnail(fileObj) {
        thumbnailQueue.push(fileObj);
        processThumbnailQueue();
    }

    function processThumbnailQueue() {
        if (isGeneratingThumbnail || thumbnailQueue.length === 0) return;
        
        const fileObj = thumbnailQueue.shift();
        
        // Ensure file is still selected (not removed by user while queued)
        const stillSelected = selectedFiles.some(item => item.id === fileObj.id);
        if (!stillSelected) {
            processThumbnailQueue();
            return;
        }

        isGeneratingThumbnail = true;
        updateFileStatusUIById(fileObj.id, 'generating_thumbnail');

        generatePdfThumbnail(fileObj.file, function(base64) {
            generatedThumbnails[fileObj.file.name] = base64;
            updateFileStatusUIById(fileObj.id, 'thumbnail_ready');
            fileObj.thumbnailStatus = 'ready';
            isGeneratingThumbnail = false;
            processThumbnailQueue();
        }, function(err) {
            console.error('Error generating thumbnail for', fileObj.file.name, err);
            updateFileStatusUIById(fileObj.id, 'thumbnail_failed');
            fileObj.thumbnailStatus = 'failed';
            isGeneratingThumbnail = false;
            processThumbnailQueue();
        });
    }

    // Render a single PDF page to base64 cover thumbnail
    function generatePdfThumbnail(file, onSuccess, onError) {
        if (typeof pdfjsLib === 'undefined') {
            onError('pdfjsLib is not loaded');
            return;
        }
        
        const fileReader = new FileReader();
        fileReader.onload = function(e) {
            const typedarray = new Uint8Array(e.target.result);
            
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
                    const base64 = canvas.toDataURL('image/jpeg', 0.85);
                    onSuccess(base64);
                });
            }).catch(function(error) {
                onError(error);
            });
        };
        fileReader.onerror = function(e) {
            onError(e);
        };
        fileReader.readAsArrayBuffer(file);
    }

    // Append file row to file list UI
    function addFileRowToUI(fileObj) {
        const file = fileObj.file;
        const id = fileObj.id;
        
        const row = `
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg text-sm border border-gray-100 hover:bg-gray-100 transition-colors file-row" id="file-row-${id}">
                <div class="flex items-center flex-1 min-w-0 mr-4">
                    <i class="fas fa-file-pdf text-red-500 mr-3 text-lg flex-shrink-0"></i>
                    <span class="text-gray-700 font-medium truncate" title="${escapeHtml(file.name)}">${file.name}</span>
                </div>
                <div class="flex items-center space-x-3 flex-shrink-0">
                    <span class="text-gray-500 text-xs">${formatBytes(file.size)}</span>
                    <div class="thumb-status flex items-center" id="thumb-status-${id}">
                        <i class="fas fa-clock text-gray-400 mr-2" title="Queued for cover thumbnail"></i>
                    </div>
                    <div class="upload-status-badge font-semibold text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-600" id="upload-status-${id}">
                        Pending
                    </div>
                    <button type="button" class="remove-file-btn text-gray-400 hover:text-red-500 transition-colors p-1" data-id="${id}" title="Remove file">
                        <i class="fas fa-times text-base"></i>
                    </button>
                </div>
            </div>
        `;
        $('#files-container').append(row);
    }

    // Update specific file row statuses dynamically
    function updateFileStatusUIById(id, type, details) {
        if (type === 'generating_thumbnail') {
            $(`#thumb-status-${id}`).html('<i class="fas fa-spinner fa-spin text-blue-500 mr-2" title="Generating cover..."></i>');
        } else if (type === 'thumbnail_ready') {
            $(`#thumb-status-${id}`).html('<i class="fas fa-image text-green-500 mr-2" title="Cover thumbnail ready"></i>');
        } else if (type === 'thumbnail_failed') {
            $(`#thumb-status-${id}`).html('<span class="text-gray-400 text-xs mr-2" title="No cover generated">No cover</span>');
        } else if (type === 'uploading') {
            const badge = $(`#upload-status-${id}`);
            badge.removeClass().addClass('upload-status-badge font-semibold text-xs px-2 py-0.5 rounded bg-blue-100 text-blue-700');
            badge.html(`<i class="fas fa-spinner fa-spin mr-1"></i> ${details || 'Uploading...'}`);
        } else if (type === 'success') {
            const badge = $(`#upload-status-${id}`);
            badge.removeClass().addClass('upload-status-badge font-semibold text-xs px-2 py-0.5 rounded bg-green-100 text-green-700');
            badge.text('Success');
            $(`#file-row-${id}`).addClass('bg-green-50/50 border-green-200');
            $(`#file-row-${id} .remove-file-btn`).addClass('hidden');
        } else if (type === 'duplicate') {
            const badge = $(`#upload-status-${id}`);
            badge.removeClass().addClass('upload-status-badge font-semibold text-xs px-2 py-0.5 rounded bg-yellow-100 text-yellow-700');
            badge.text('Duplicate (Skipped)');
            $(`#file-row-${id}`).addClass('bg-yellow-50/30 border-yellow-200');
            $(`#file-row-${id} .remove-file-btn`).addClass('hidden');
        } else if (type === 'failed') {
            const badge = $(`#upload-status-${id}`);
            badge.removeClass().addClass('upload-status-badge font-semibold text-xs px-2 py-0.5 rounded bg-red-100 text-red-700 cursor-help');
            badge.text('Failed');
            badge.attr('title', details || 'Upload failed');
            $(`#file-row-${id}`).addClass('bg-red-50/50 border-red-200');
        }
    }

    // HTML Escaper helper
    function escapeHtml(string) {
        if (!string) return '';
        return String(string)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // File input selection event
    $('#pdfs').on('change', function(e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            $('#file-list').removeClass('hidden');
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                // Check if file is already selected (duplicate check by name and size)
                const exists = selectedFiles.some(item => item.file.name === file.name && item.file.size === file.size);
                if (!exists) {
                    if (file.type === 'application/pdf') {
                        fileIdCounter++;
                        const fileObj = {
                            id: fileIdCounter,
                            file: file,
                            status: 'pending',
                            error: null,
                            thumbnailStatus: 'queued'
                        };
                        selectedFiles.push(fileObj);
                        addFileRowToUI(fileObj);
                        enqueueThumbnail(fileObj);
                    } else {
                        showAlert('error', `<i class="fas fa-exclamation-circle mr-2"></i>Invalid file type skipped: ${file.name}`);
                    }
                }
            }
        }
        $(this).val('');
    });

    // Remove file handler
    $(document).on('click', '.remove-file-btn', function(e) {
        e.preventDefault();
        
        // Block removal during active upload
        if ($('#upload-btn').prop('disabled')) {
            return;
        }

        const id = parseInt($(this).attr('data-id'));
        const index = selectedFiles.findIndex(item => item.id === id);
        if (index !== -1) {
            const removedFileObj = selectedFiles[index];
            selectedFiles.splice(index, 1);
            
            // Remove DOM row
            $(`#file-row-${id}`).remove();
            
            // Remove from thumbnail generation queue
            thumbnailQueue = thumbnailQueue.filter(item => item.id !== id);

            // Clean up its cached thumbnail
            const stillExists = selectedFiles.some(item => item.file.name === removedFileObj.file.name);
            if (!stillExists) {
                delete generatedThumbnails[removedFileObj.file.name];
            }
            
            if (selectedFiles.length === 0) {
                $('#file-list').addClass('hidden');
            }
        }
    });

    // Form submission sequential runner
    $('#bulk-upload-form').on('submit', function(e) {
        e.preventDefault();
        
        // Block removal during active upload
        if ($('#upload-btn').prop('disabled')) {
            return;
        }

        if (selectedFiles.length === 0) {
            showAlert('error', '<i class="fas fa-exclamation-circle mr-2"></i>Please select at least one PDF file.');
            return;
        }

        // Check if there are still thumbnails generating
        if (isGeneratingThumbnail || thumbnailQueue.length > 0) {
            showAlert('warning', '<i class="fas fa-clock mr-2"></i>Please wait for all PDF cover thumbnails to finish generating before uploading.');
            return;
        }

        const uploadBtn = $('#upload-btn');
        
        // Disable controls to prevent modification during upload
        uploadBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Uploading...');
        $('#pdfs').prop('disabled', true);
        $('.remove-file-btn').addClass('hidden');

        // Show overall progress
        $('#progress-section').removeClass('hidden');
        $('#results-section').addClass('hidden');
        $('#progress-bar').css('width', '0%');
        $('#progress-percent').text('0%');

        let uploadedCount = 0;
        let failedCount = 0;
        let duplicatesCount = 0;
        let errors = [];
        let createdBooks = [];

        // Upload next file recursively
        function uploadNext(index) {
            if (index >= selectedFiles.length) {
                // All uploads completed
                $('#progress-bar').css('width', '100%');
                $('#progress-percent').text('100%');

                const summaryMessage = `Upload complete. Successfully uploaded ${uploadedCount} file(s).` + 
                    (duplicatesCount > 0 ? ` ${duplicatesCount} duplicate(s) skipped.` : '') + 
                    (failedCount > 0 ? ` ${failedCount} file(s) failed.` : '');

                showAlert(failedCount === 0 ? 'success' : 'warning', `<i class="fas fa-check-circle mr-2"></i>` + summaryMessage);

                displayResults({
                    uploaded: uploadedCount,
                    failed: failedCount,
                    duplicates_skipped: duplicatesCount,
                    created_books: createdBooks,
                    errors: errors
                });

                // Clear states and re-enable controls
                selectedFiles = [];
                $('#pdfs').prop('disabled', false);
                uploadBtn.prop('disabled', false).html('<i class="fas fa-upload mr-2"></i><span>Upload PDFs</span>');
                
                // Refresh storage info
                loadStorageInfo();
                return;
            }

            const fileObj = selectedFiles[index];
            const file = fileObj.file;
            const id = fileObj.id;

            updateFileStatusUIById(id, 'uploading', '0%');

            const formData = new FormData();
            formData.append('pdfs[]', file); // Wrap in array format for controller expectation

            const base64 = generatedThumbnails[file.name];
            if (base64) {
                formData.append('thumbnail', base64);
                formData.append('thumbnails[' + file.name + ']', base64);
            }

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
                            const percent = Math.round((e.loaded / e.total) * 100);
                            updateFileStatusUIById(id, 'uploading', percent + '%');
                            
                            // Overall progress bar update
                            const totalFiles = selectedFiles.length;
                            const overallPercent = Math.round(((index + (e.loaded / e.total)) / totalFiles) * 100);
                            $('#progress-bar').css('width', overallPercent + '%');
                            $('#progress-percent').text(overallPercent + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const data = response.data;
                        
                        // Always capture non-fatal errors even if upload was successful
                        if (data.errors && data.errors.length > 0) {
                            data.errors.forEach(function(err) {
                                errors.push(err);
                            });
                        }

                        if (data.uploaded > 0) {
                            uploadedCount++;
                            updateFileStatusUIById(id, 'success');
                            if (data.created_books && data.created_books.length > 0) {
                                createdBooks.push(...data.created_books);
                            }
                            if (data.covers_generated > 0) {
                                coversGeneratedCount++;
                            }
                        } else if (data.duplicates_skipped > 0) {
                            duplicatesCount++;
                            updateFileStatusUIById(id, 'duplicate');
                        } else {
                            failedCount++;
                            const errStr = data.errors && data.errors.length > 0 ? data.errors[0] : 'Unknown error';
                            updateFileStatusUIById(id, 'failed', errStr);
                            // Avoid duplicating the error since we added it above
                            if (!data.errors || data.errors.length === 0) {
                                errors.push(errStr);
                            }
                        }
                    } else {
                        failedCount++;
                        const errStr = response.message || 'Server rejected file';
                        updateFileStatusUIById(id, 'failed', errStr);
                        errors.push(`Failed to upload ${file.name}: ${errStr}`);
                    }
                },
                error: function(xhr) {
                    failedCount++;
                    let errMsg = `Upload failed (HTTP ${xhr.status})`;
                    if (xhr.status === 413) {
                        errMsg = 'File is too large for the PHP server configuration.';
                    } else if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        }
                        if (xhr.responseJSON.errors) {
                            errMsg += ' - ' + Object.values(xhr.responseJSON.errors).flat().join(', ');
                        }
                    }
                    updateFileStatusUIById(id, 'failed', errMsg);
                    errors.push(`Failed to upload ${file.name}: ${errMsg}`);
                },
                complete: function() {
                    const overallPercent = Math.round(((index + 1) / selectedFiles.length) * 100);
                    $('#progress-bar').css('width', overallPercent + '%');
                    $('#progress-percent').text(overallPercent + '%');
                    
                    // Upload next file
                    uploadNext(index + 1);
                }
            });
        }

        // Initiate sequential uploading
        uploadNext(0);
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
        const alertClass = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 
                           (type === 'warning' ? 'bg-yellow-100 border-yellow-400 text-yellow-700' : 'bg-red-100 border-red-400 text-red-700');
        const html = `
            <div class="alert-${type} ${alertClass} border rounded-lg p-4 mb-4">
                ${message}
            </div>
        `;
        $('#alert-container').html(html);
        
        // Auto-hide after 15 seconds
        setTimeout(() => {
            $('.alert-' + type).fadeOut();
        }, 15000);
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
