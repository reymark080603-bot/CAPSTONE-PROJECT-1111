<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $book->title }} - Knowly Library</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    @vite(['resources/css/view-only-pdf.css'])
    
    @if($book->hasPdfFile())
    <!-- Load PDF.js library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js" crossorigin="anonymous"></script>
    <script>
        // Configure PDF.js worker immediately
        if (typeof pdfjsLib !== 'undefined') {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }
    </script>
    @endif
</head>
<body>
    @if($book->hasPdfFile())
        <!-- Loading indicator -->
        <div id="pdf-loading" class="pdf-loading">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Loading your book...</span>
        </div>

        <!-- View-Only PDF Viewer Container -->
        <div class="pdf-viewer-container" id="view-only-container" style="display: none;">
            <!-- PDF viewer will be initialized here by JavaScript -->
        </div>

        <!-- Include view-only viewer script -->
        @vite(['resources/js/view-only-pdf.js'])
        <script>
            // Wait for both DOM and PDF.js to be ready
            let pdfJsReady = false;
            let domReady = false;
            
            // Check if PDF.js loaded
            if (typeof pdfjsLib !== 'undefined') {
                pdfJsReady = true;
            } else {
                // Listen for script load
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(function() {
                        if (typeof pdfjsLib !== 'undefined') {
                            pdfJsReady = true;
                            initViewer();
                        }
                    }, 100);
                });
            }
            
            // Initialize viewer function
            function initViewer() {
                if (!pdfJsReady || !domReady) return;
                
                const loadingDiv = document.getElementById('pdf-loading');
                const viewerContainer = document.getElementById('view-only-container');

                try {
                    // Check if libraries are loaded
                    if (typeof pdfjsLib === 'undefined') {
                        throw new Error('PDF.js library failed to load from CDN');
                    }
                    if (typeof ViewOnlyPDFViewer === 'undefined') {
                        throw new Error('ViewOnlyPDFViewer class is not loaded');
                    }

                    console.log('Initializing ViewOnlyPDFViewer...');
                    console.log('PDF URL:', '{{ $book->getPdfUrl() }}');

                    // Initialize view-only PDF viewer
                    const viewer = new ViewOnlyPDFViewer(
                        'view-only-container',
                        '{{ $book->getPdfUrl() }}',
                        '{{ addslashes($book->title) }}'
                    );

                    // Hide loading and show viewer
                    loadingDiv.style.display = 'none';
                    viewerContainer.style.display = 'flex';

                } catch (error) {
                    console.error('Error initializing PDF viewer:', error);
                    loadingDiv.innerHTML = `
                        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #ef4444; margin-bottom: 1rem;"></i>
                        <span style="color: #fff; font-size: 1.125rem; margin-bottom: 0.5rem;">Failed to load PDF viewer</span>
                        <span style="color: #9ca3af; font-size: 0.875rem; margin-bottom: 1.5rem;">${error.message}</span>
                        <button onclick="location.reload()" class="viewer-btn" style="margin-top: 1.5rem; padding: 0.75rem 1.5rem; background: rgba(96, 165, 250, 0.15); color: #60a5fa; border: 1px solid rgba(96, 165, 250, 0.3); border-radius: 0.5rem; cursor: pointer;">
                            <i class="fas fa-redo"></i> Retry
                        </button>
                    `;
                }
            }
            
            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    domReady = true;
                    initViewer();
                });
            } else {
                domReady = true;
                initViewer();
            }
        </script>
    @else
        <!-- Fallback for non-PDF books -->
        <div class="viewer-error">
            <i class="fas fa-file-alt"></i>
            <h3>PDF Not Available</h3>
            <p>This book is not available in PDF format.</p>
            <button onclick="window.location.href='{{ route('student.books.show', $book->id) }}'" class="viewer-btn">
                <i class="fas fa-arrow-left"></i> Back to Book Details
            </button>
        </div>
    @endif
</body>
</html>
