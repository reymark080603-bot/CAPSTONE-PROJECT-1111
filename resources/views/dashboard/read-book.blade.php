<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $book->title }} - Knowly Library</title>
    <link href="{{ asset('css/read-book.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Try Vite, but add fallback for styles -->
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/view-only-pdf.css'])
    @else
        <style>
            .pdf-viewer-container { display: flex; flex-direction: column; height: 100vh; width: 100vw; background: #1a1a1a; z-index: 9999; }
            .pdf-loading { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; background: #1a1a1a; color: #60a5fa; }
            @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
            .fa-spin { animation: spin 2s linear infinite; }
        </style>
    @endif
    <style>
        .mobile-watermark {
            display: none;
        }

        @media (max-width: 768px) {
            .mobile-watermark {
                display: block;
                position: fixed;
                inset: 0;
                z-index: 10001;
                pointer-events: none;
                opacity: 0.18;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='220' height='220' viewBox='0 0 220 220'%3E%3Cg transform='rotate(-28 110 110)'%3E%3Ctext x='20' y='110' fill='%23ffffff' fill-opacity='0.95' font-size='26' font-family='Arial, Helvetica, sans-serif' font-weight='700' letter-spacing='3'%3EKNOWLY%3C/text%3E%3C/g%3E%3C/svg%3E");
                background-repeat: repeat;
                background-size: 220px 220px;
                background-position: center;
            }
        }
    </style>
    @if($book->hasPdfFile())
    <!-- Load PDF.js with multiple CDN fallbacks -->
    <script>
        // Try multiple CDNs for PDF.js
        function loadPDFJS() {
            const cdns = [
                'https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.min.js',
                'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js',
                'https://jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js'
            ];
            
            let currentCdn = 0;
            
            function tryNextCdn() {
                if (currentCdn >= cdns.length) {
                    console.error('All PDF.js CDNs failed to load');
                    document.getElementById('pdf-loading').innerHTML = `
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Failed to load PDF viewer. Please check your internet connection.</span>
                        <button onclick="window.location.reload()" class="pdf-btn">
                            <i class="fas fa-refresh"></i> Retry
                        </button>
                    `;
                    return;
                }
                
                const script = document.createElement('script');
                script.src = cdns[currentCdn];
                script.onload = function() {
                    if (typeof pdfjsLib !== 'undefined') {
                        window.__pdfJsReady = true;
                        pdfjsLib.GlobalWorkerOptions.workerSrc = cdns[currentCdn].replace('pdf.min.js', 'pdf.worker.min.js');
                        console.log('PDF.js loaded successfully from CDN ' + (currentCdn + 1));
                        
                        // Initialize the viewer now that PDF.js is loaded
                        if (typeof window.initializePDFViewer === 'function') {
                            console.log('PDF.js loaded, calling initializePDFViewer');
                            window.initializePDFViewer();
                        }
                    } else {
                        tryNextCdn();
                    }
                };
                script.onerror = tryNextCdn;
                document.head.appendChild(script);
                currentCdn++;
            }
            
            tryNextCdn();
        }
        
        // Define a placeholder for initializePDFViewer in case it's called before JS loads
        window.initializePDFViewer = function() {
            console.log('initializePDFViewer placeholder called');
            // This will be overridden by read-book-new.js
        };
        window.__pdfJsReady = false;
        window.__pdfViewerInitialized = false;
        
        // Load PDF.js immediately
        loadPDFJS();
        
        // Pass PDF data to JavaScript
        window.pdfUrl = '{{ $book->getPdfUrl() }}';
        window.bookTitle = '{{ addslashes($book->title) }}';
        window.bookId = {{ $book->id }};
        window.hasPdfFile = {{ $book->hasPdfFile() ? 'true' : 'false' }};
        
        console.log('PDF URL:', window.pdfUrl);
        console.log('Book Title:', window.bookTitle);
        console.log('Book ID:', window.bookId);
        console.log('Has PDF File:', window.hasPdfFile);
        
        // The actual viewer initialization is handled by read-book-new.js once PDF.js is ready.
    </script>
    @endif
</head>
<body class="bg-black">
    <div class="mobile-watermark" aria-hidden="true"></div>
    <!-- PDF Viewer Container -->
    @if($book->hasPdfFile())
        <!-- Loading indicator -->
        <div id="pdf-loading" class="pdf-loading">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Loading PDF...</span>
        </div>

        <!-- Custom PDF Viewer Container -->
        <div class="pdf-viewer-container" id="pdf-viewer-container" style="display: none; width: 100vw; height: 100vh; overflow: hidden;">
            <!-- PDF content will be rendered here by JavaScript -->
        </div>

        <!-- PDF Viewer JavaScript is now loaded in the head -->
    @elseif($book->hasEpubFile())
        <!-- EPUB Reader with iframe viewer -->
        <div class="epub-viewer-container" style="width: 100%; height: 100vh; display: flex; flex-direction: column;">
            <div class="bg-gray-800 text-white p-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="{{ route('student.books.show', $book->id) }}" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </a>
                    <h3 class="text-lg font-semibold">{{ $book->title }}</h3>
                </div>
                <a href="{{ asset($book->epub_file) }}" download class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-download mr-2"></i>Download EPUB
                </a>
            </div>
            <iframe src="{{ asset($book->epub_file) }}" style="width: 100%; height: 100%; border: none; background: white;"></iframe>
        </div>
    @elseif($book->hasDocFile())
        <!-- DOC Viewer with Office Online or Google Docs Viewer -->
        <div class="doc-viewer-container" style="width: 100%; height: 100vh; display: flex; flex-direction: column;">
            <div class="bg-gray-800 text-white p-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="{{ route('student.books.show', $book->id) }}" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </a>
                    <h3 class="text-lg font-semibold">{{ $book->title }}</h3>
                </div>
                <a href="{{ asset($book->doc_file) }}" download class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-download mr-2"></i>Download DOC
                </a>
            </div>
            <iframe src="https://view.officeapps.live.com/op/embed.aspx?src={{ urlencode(url($book->doc_file)) }}" style="width: 100%; height: 100%; border: none;"></iframe>
        </div>
    @else
        <!-- No PDF File Available -->
        <div class="bg-gray-900 text-white p-8" style="min-height: 100vh;">
            <div class="max-w-4xl mx-auto text-center">
                <!-- Back Button -->
                <a href="{{ route('student.books.show', $book->id) }}" class="inline-block mb-6 px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Book Details
                </a>

                <!-- Error Message -->
                <div class="bg-red-900 bg-opacity-50 border border-red-700 rounded-lg p-8 mb-8">
                    <i class="fas fa-file-pdf text-red-400 text-6xl mb-4"></i>
                    <h2 class="text-2xl font-bold text-red-300 mb-4">PDF File Not Available</h2>
                    <p class="text-gray-300 mb-4">
                        The PDF file for "{{ $book->title }}" is not available for online reading.
                    </p>
                    <div class="text-left bg-gray-800 rounded p-4 mb-4">
                        <h3 class="text-lg font-semibold text-white mb-2">Possible reasons:</h3>
                        <ul class="text-gray-300 space-y-2">
                            <li><i class="fas fa-times-circle text-red-400 mr-2"></i>No PDF file has been uploaded for this book</li>
                            <li><i class="fas fa-times-circle text-red-400 mr-2"></i>The PDF file has been deleted or moved</li>
                            <li><i class="fas fa-times-circle text-red-400 mr-2"></i>The file reference in the database is incorrect</li>
                        </ul>
                    </div>
                    @if($book->content)
                        <p class="text-gray-300">
                            However, this book has text content available. You can read it below.
                        </p>
                    @endif
                </div>

                @if($book->content)
                    <!-- Book Text Content -->
                    <div class="text-left">
                        <h1 class="text-3xl font-bold text-white mb-2">{{ $book->title }}</h1>
                        <p class="text-gray-300 mb-6">by {{ $book->author }}</p>
                        <div class="prose prose-lg prose-invert max-w-none text-content bg-gray-800 rounded-lg p-6">
                            {!! nl2br(e($book->content)) !!}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
    
    <script>
        window.readBookRoutes = {
            booksIndex: @json(route('student.books')),
            bookDetails: @json(route('student.books.show', $book->id)),
        };
    </script>
    @if($book->hasPdfFile())
    <script src="{{ asset('js/read-book-new.js') }}?v={{ filemtime(public_path('js/read-book-new.js')) }}"></script>
    @else
    @vite(['resources/js/read-book.js'])
    @endif
</body>
</html>
