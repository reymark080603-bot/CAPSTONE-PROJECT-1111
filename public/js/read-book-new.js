console.log('read-book-new.js loaded - CLEAN VERSION');

// ViewOnlyPDFViewer - Secure PDF viewer for online library (no downloads)
class ViewOnlyPDFViewer {
    constructor(containerId, pdfUrl, bookTitle = '') {
        this.container = document.getElementById(containerId);
        this.pdfUrl = pdfUrl;
        this.bookTitle = bookTitle;
        this.pdfDoc = null;
        this.pageNum = 1;
        this.pageRendering = false;
        this.pageNumPending = null;
        this.scale = 1.5;
        this.canvas = null;
        this.ctx = null;

        // Verify PDF.js is loaded
        if (typeof pdfjsLib === 'undefined') {
            throw new Error('PDF.js library is not loaded');
        }

        this.init();
    }

    getBookId() {
        const currentUrl = window.location.pathname;
        const match = currentUrl.match(/\/books\/(\d+)/);
        if (match) {
            return match[1];
        }
        return '1';
    }

    getBookDetailsUrl() {
        return window.readBookRoutes?.bookDetails || `/student/books/${this.getBookId()}`;
    }

    getBooksIndexUrl() {
        return window.readBookRoutes?.booksIndex || '/student/books';
    }

    goToBookDetails() {
        window.location.assign(this.getBookDetailsUrl());
    }

    async validatePDFFile(url) {
        try {
            console.log('Validating PDF URL:', url);
            // Use simple fetch to check if file exists and is accessible
            // We only need the headers, so we can try HEAD first then GET if needed
            let response;
            try {
                response = await fetch(url, { method: 'HEAD' });
            } catch (e) {
                console.warn('HEAD request failed, trying GET:', e);
                response = await fetch(url);
            }
            
            if (!response.ok) {
                if (response.status === 404) {
                    throw new Error(`PDF file not found on server (404). Path: ${url}`);
                } else if (response.status === 403) {
                    throw new Error(`Access to PDF file is forbidden (403). Check server permissions.`);
                } else {
                    throw new Error(`PDF file not accessible (Status: ${response.status}).`);
                }
            }
            
            const contentType = response.headers.get('content-type');
            if (contentType && !contentType.includes('application/pdf') && !contentType.includes('application/octet-stream')) {
                console.warn('Unexpected content type:', contentType);
                // We'll still try to load it, as some servers send wrong content-type
            }
            
            return true;
        } catch (error) {
            console.error('PDF validation error:', error);
            throw error;
        }
    }

    async init() {
        try {
            console.log('ViewOnlyPDFViewer.init() starting...');
            // Configure worker
            if (!pdfjsLib.GlobalWorkerOptions.workerSrc) {
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            }

            // Validate PDF file first
            await this.validatePDFFile(this.pdfUrl);

            // Load the PDF document
            const loadingTask = pdfjsLib.getDocument(this.pdfUrl);
            
            loadingTask.onProgress = function(progress) {
                if (progress.total > 0) {
                    const percent = Math.round((progress.loaded / progress.total) * 100);
                    const loadingText = document.querySelector('#pdf-loading span');
                    if (loadingText) loadingText.textContent = `Downloading PDF: ${percent}%`;
                }
            };
            
            this.pdfDoc = await loadingTask.promise;
            console.log('PDF document loaded successfully');
            
            if (!this.pdfDoc || !this.pdfDoc.numPages || this.pdfDoc.numPages <= 0) {
                throw new Error('PDF document is empty or corrupted');
            }

            // Create canvas and controls
            this.createViewerElements();
            
            // Hide loading now that UI is ready
            const loadingDiv = document.getElementById('pdf-loading');
            if (loadingDiv) loadingDiv.style.display = 'none';
            
            this.renderAllPages();

        } catch (error) {
            console.error('Error loading PDF:', error);
            this.showError('Failed to load PDF document: ' + error.message);
            const loadingDiv = document.getElementById('pdf-loading');
            if (loadingDiv) loadingDiv.style.display = 'none';
        }
    }

    createViewerElements() {
        // Clear container
        this.container.innerHTML = '';

        // Create header
        const header = document.createElement('div');
        header.className = 'viewer-header';
        header.innerHTML = `
            <div class="viewer-title">
                <i class="fas fa-book-open"></i>
                <span>${this.bookTitle}</span>
            </div>
            <button id="viewer-close-btn" class="viewer-btn viewer-close" title="Close Viewer">
                <i class="fas fa-times"></i>
            </button>
        `;
        this.container.appendChild(header);

        // Create toolbar
        const toolbar = document.createElement('div');
        toolbar.className = 'viewer-toolbar';
        toolbar.innerHTML = `
            <div class="viewer-page-info">
                <input type="number" id="page-input" min="1" max="${this.pdfDoc.numPages}" value="1" style="width: 50px; padding: 4px 8px; border: 1px solid #666; border-radius: 4px; background: #333; color: white; text-align: center;">
                of <span id="total-pages">${this.pdfDoc.numPages}</span>
            </div>
            <div class="viewer-zoom">
                <button id="zoom-out" class="viewer-btn" title="Zoom Out">
                    <i class="fas fa-search-minus"></i>
                </button>
                <span id="zoom-level">${Math.round(this.scale * 100)}%</span>
                <button id="zoom-in" class="viewer-btn" title="Zoom In">
                    <i class="fas fa-search-plus"></i>
                </button>
                <button id="zoom-fit" class="viewer-btn" title="Fit to Width">
                    <i class="fas fa-arrows-alt-h"></i>
                </button>
            </div>
        `;
        this.container.appendChild(toolbar);

        // Create scrollable container
        const scrollContainer = document.createElement('div');
        scrollContainer.className = 'viewer-scroll-container';
        scrollContainer.style.overflowY = 'auto';
        scrollContainer.style.overflowX = 'auto';
        scrollContainer.style.padding = '20px';
        scrollContainer.style.background = '#525659';
        scrollContainer.style.height = 'calc(100vh - 120px)';
        this.container.appendChild(scrollContainer);

        // Create pages container
        this.pagesContainer = document.createElement('div');
        this.pagesContainer.className = 'viewer-pages-container';
        this.pagesContainer.style.width = 'fit-content';
        this.pagesContainer.style.margin = '0 auto';
        this.pagesContainer.style.display = 'flex';
        this.pagesContainer.style.flexDirection = 'column';
        this.pagesContainer.style.alignItems = 'center';
        scrollContainer.appendChild(this.pagesContainer);

        // Bind events
        this.bindEvents();
        this.applySecurityMeasures();
    }

    bindEvents() {
        // Page input for jumping to specific page
        const pageInput = document.getElementById('page-input');
        if (pageInput) {
            pageInput.addEventListener('change', (e) => {
                let pageNum = parseInt(e.target.value, 10);
                if (isNaN(pageNum) || pageNum < 1) pageNum = 1;
                if (pageNum > this.pdfDoc.numPages) pageNum = this.pdfDoc.numPages;
                
                // Update input value to valid number
                pageInput.value = pageNum;
                
                // Scroll to the requested page
                this.goToPage(pageNum);
            });

            // Allow pressing Enter to jump to page
            pageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.target.blur();
                    let pageNum = parseInt(e.target.value, 10);
                    if (isNaN(pageNum) || pageNum < 1) pageNum = 1;
                    if (pageNum > this.pdfDoc.numPages) pageNum = this.pdfDoc.numPages;
                    
                    pageInput.value = pageNum;
                    this.goToPage(pageNum);
                }
            });
        }

        // Scroll container to track current page
        const scrollContainer = document.querySelector('.viewer-scroll-container');
        if (scrollContainer) {
            scrollContainer.addEventListener('scroll', () => {
                this.updateCurrentPage();
            });
        }

        // Zoom controls
        document.getElementById('zoom-in').addEventListener('click', () => {
            this.scale = Math.min(this.scale + 0.25, 3.0);
            this.updateZoomDisplay();
            this.renderAllPages();
        });

        document.getElementById('zoom-out').addEventListener('click', () => {
            this.scale = Math.max(this.scale - 0.25, 0.5);
            this.updateZoomDisplay();
            this.renderAllPages();
        });

        document.getElementById('zoom-fit').addEventListener('click', () => {
            this.fitToWidth();
        });

        // Close button
        document.getElementById('viewer-close-btn').addEventListener('click', () => {
            this.goToBookDetails();
        });

        // Keyboard controls
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && (e.key === '+' || e.key === '=')) {
                e.preventDefault();
                this.scale = Math.min(this.scale + 0.25, 3.0);
                this.updateZoomDisplay();
                this.renderAllPages();
            } else if (e.ctrlKey && e.key === '-') {
                e.preventDefault();
                this.scale = Math.max(this.scale - 0.25, 0.5);
                this.updateZoomDisplay();
                this.renderAllPages();
            } else if (e.key === 'Escape') {
                this.goToBookDetails();
            }
        });
    }

    applySecurityMeasures() {
        // Prevent right-click context menu
        document.addEventListener('contextmenu', (e) => {
            if (e.target.closest('.viewer-page-canvas')) {
                e.preventDefault();
                return false;
            }
        });

        // Prevent text selection
        document.addEventListener('selectstart', (e) => {
            if (e.target.closest('.viewer-page-canvas')) {
                e.preventDefault();
                return false;
            }
        });

        // Prevent copying
        document.addEventListener('copy', (e) => {
            if (e.target.closest('.viewer-page-canvas')) {
                e.preventDefault();
                return false;
            }
        });
    }

    async renderAllPages() {
        this.pageRendering = true;
        
        try {
            this.pagesContainer.innerHTML = '';
            
            for (let i = 1; i <= this.pdfDoc.numPages; i++) {
                await this.renderPage(i);
            }
            
            this.pageRendering = false;
        } catch (error) {
            console.error('Error rendering pages:', error);
            this.showError('Error rendering PDF pages');
            this.pageRendering = false;
        }
    }
    
    async renderPage(num) {
        try {
            // Create page container
            const pageContainer = document.createElement('div');
            pageContainer.className = 'viewer-page-container';
            pageContainer.style.marginBottom = '20px';
            pageContainer.style.background = 'white';
            pageContainer.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.4)';
            pageContainer.style.borderRadius = '4px';
            pageContainer.style.overflow = 'hidden';
            pageContainer.style.position = 'relative';
            
            // Add page number
            const pageNumber = document.createElement('div');
            pageNumber.className = 'viewer-page-number';
            pageNumber.textContent = `Page ${num} of ${this.pdfDoc.numPages}`;
            pageNumber.style.position = 'absolute';
            pageNumber.style.top = '10px';
            pageNumber.style.right = '10px';
            pageNumber.style.background = 'rgba(0, 0, 0, 0.5)';
            pageNumber.style.color = 'white';
            pageNumber.style.padding = '2px 8px';
            pageNumber.style.borderRadius = '3px';
            pageNumber.style.fontSize = '12px';
            pageNumber.style.pointerEvents = 'none';
            pageContainer.appendChild(pageNumber);
            
            // Create canvas
            const canvas = document.createElement('canvas');
            canvas.className = 'viewer-page-canvas';
            canvas.style.maxWidth = '100%';
            canvas.style.height = 'auto';
            canvas.style.display = 'block';
            pageContainer.appendChild(canvas);
            
            // Add to container
            this.pagesContainer.appendChild(pageContainer);
            
            // Get and render page
            const page = await this.pdfDoc.getPage(num);
            const viewport = page.getViewport({ scale: this.scale });
            const context = canvas.getContext('2d');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            
            await page.render({
                canvasContext: context,
                viewport: viewport
            }).promise;
            
        } catch (error) {
            console.error(`Error rendering page ${num}:`, error);
        }
    }

    updateZoomDisplay() {
        document.getElementById('zoom-level').textContent = Math.round(this.scale * 100) + '%';
    }

    updateCurrentPage() {
        // Find which page is currently visible in the viewport
        const scrollContainer = document.querySelector('.viewer-scroll-container');
        const pageContainers = document.querySelectorAll('.viewer-page-container');
        
        if (!scrollContainer || pageContainers.length === 0) return;
        
        let currentPage = 1;
        const scrollTop = scrollContainer.scrollTop;
        const containerHeight = scrollContainer.clientHeight;
        const centerViewport = scrollTop + containerHeight / 2;
        
        for (let i = 0; i < pageContainers.length; i++) {
            const pageElement = pageContainers[i];
            const pageTop = pageElement.offsetTop;
            const pageBottom = pageTop + pageElement.clientHeight;
            
            // Check if page is in the middle of the viewport
            if (pageTop <= centerViewport && centerViewport <= pageBottom) {
                currentPage = i + 1;
                break;
            }
        }
        
        // Update the page input
        const pageInput = document.getElementById('page-input');
        if (pageInput) {
            pageInput.value = currentPage;
        }
    }

    goToPage(pageNum) {
        // Validate page number
        if (isNaN(pageNum) || pageNum < 1 || pageNum > this.pdfDoc.numPages) {
            return;
        }
        
        // Find the page container and scroll to it
        const pageContainers = document.querySelectorAll('.viewer-page-container');
        if (pageNum > 0 && pageNum <= pageContainers.length) {
            const targetPage = pageContainers[pageNum - 1];
            const scrollContainer = document.querySelector('.viewer-scroll-container');
            
            if (scrollContainer && targetPage) {
                // Scroll to the target page with smooth animation
                targetPage.scrollIntoView({ behavior: 'smooth', block: 'start' });
                
                // Update the page input immediately
                const pageInput = document.getElementById('page-input');
                if (pageInput) {
                    pageInput.value = pageNum;
                }
            }
        }
    }

    async fitToWidth() {
        if (!this.pdfDoc) return;

        try {
            const containerWidth = this.container.clientWidth - 80;
            const page = await this.pdfDoc.getPage(1);
            const viewport = page.getViewport({ scale: 1 });
            this.scale = containerWidth / viewport.width;
            this.scale = Math.max(0.5, Math.min(this.scale, 3.0));
            this.updateZoomDisplay();
            this.renderAllPages();
        } catch (error) {
            console.error('Error fitting to width:', error);
        }
    }

    showError(message) {
        this.container.innerHTML = `
            <div class="viewer-error">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Unable to Load PDF</h3>
                <p>${message}</p>
                <button onclick="window.location.replace('${this.getBookDetailsUrl()}')" class="viewer-btn">
                    <i class="fas fa-arrow-left"></i> Return to Book Details
                </button>
            </div>
        `;
    }
}

// Make globally available
window.ViewOnlyPDFViewer = ViewOnlyPDFViewer;

// Define initializePDFViewer globally so it can be called from the head script
window.initializePDFViewer = async function() {
    if (window.__pdfViewerInitialized) {
        console.log('PDF viewer already initialized, skipping duplicate call');
        return;
    }

    try {
        console.log('initializePDFViewer starting...');
        const loadingDiv = document.getElementById('pdf-loading');
        const viewerContainer = document.getElementById('pdf-viewer-container');

        if (!window.__pdfJsReady || typeof pdfjsLib === 'undefined') {
            console.log('PDF.js is not ready yet, waiting before initializing viewer');
            return;
        }

        // Show viewer container immediately (but it will be empty or show loading)
        if (viewerContainer) {
            viewerContainer.style.display = 'flex';
            viewerContainer.style.zIndex = '9999';
        }

        // Get PDF data from window
        const pdfUrl = window.pdfUrl || null;
        const bookTitle = window.bookTitle || '';

        console.log('PDF data:', { pdfUrl, bookTitle });

        if (!pdfUrl || pdfUrl === 'null' || pdfUrl === '') {
            throw new Error('PDF file URL is missing or invalid. Please re-upload the book.');
        }

        window.__pdfViewerInitialized = true;

        // Initialize PDF viewer
        new ViewOnlyPDFViewer('pdf-viewer-container', pdfUrl, bookTitle);

    } catch (error) {
        console.error('Error initializing PDF viewer:', error);
        window.__pdfViewerInitialized = false;
        const loadingDiv = document.getElementById('pdf-loading');
        const viewerContainer = document.getElementById('pdf-viewer-container');
        
        const errorHtml = `
            <div style="background: rgba(31, 41, 55, 0.95); display: flex; align-items: center; justify-content: center; width: 100vw; height: 100vh; position: fixed; top: 0; left: 0; z-index: 10000;">
                <div style="background: #1f2937; border: 1px solid #dc2626; padding: 2.5rem; border-radius: 1rem; max-width: 450px; text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
                    <i class="fas fa-exclamation-circle" style="font-size: 3.5rem; color: #ef4444; margin-bottom: 1.5rem;"></i>
                    <h3 style="color: #fff; font-size: 1.5rem; margin-bottom: 0.75rem; font-weight: 700;">Viewer Error</h3>
                    <p style="color: #d1d5db; font-size: 1rem; margin-bottom: 2rem; line-height: 1.5;">${error.message}</p>
                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <button onclick="window.location.reload()" style="background: #2563eb; color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 0.5rem; cursor: pointer; font-weight: 600; transition: all 0.2s;">Retry</button>
                        <button onclick="window.location.href='${window.readBookRoutes?.booksIndex || '/student/books'}'" style="background: #4b5563; color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 0.5rem; cursor: pointer; font-weight: 600; transition: all 0.2s;">Back to Library</button>
                    </div>
                </div>
            </div>
        `;

        if (viewerContainer) {
            viewerContainer.style.display = 'flex';
            viewerContainer.innerHTML = errorHtml;
        } else {
            document.body.insertAdjacentHTML('beforeend', errorHtml);
        }
        
        if (loadingDiv) loadingDiv.style.display = 'none';
    }
};

// Main initialization - SIMPLE AND CLEAN
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - initializing read-book.js');
    
    // Simple content detection
    const hasHtmlContent = document.getElementById('reading-text') !== null;
    const hasPdfContent = document.getElementById('pdf-viewer-container') !== null;
    
    console.log('Content detection:', { hasHtmlContent, hasPdfContent });
    
    // Handle PDF content
    if (hasPdfContent) {
        console.log('Initializing PDF viewer');
        if (window.__pdfJsReady) {
            window.initializePDFViewer();
        } else {
            console.log('Waiting for PDF.js loader before creating the viewer');
        }
    }
    
    // Handle HTML content (basic font size controls)
    if (hasHtmlContent) {
        console.log('Initializing HTML content reader');
        
        const readingText = document.getElementById('reading-text');
        const increaseBtn = document.getElementById('increase-font');
        const decreaseBtn = document.getElementById('decrease-font');
        
        let currentFontSize = 18;
        
        if (readingText && increaseBtn && decreaseBtn) {
            increaseBtn.addEventListener('click', () => {
                currentFontSize = Math.min(currentFontSize + 2, 24);
                readingText.style.fontSize = currentFontSize + 'px';
            });

            decreaseBtn.addEventListener('click', () => {
                currentFontSize = Math.max(currentFontSize - 2, 12);
                readingText.style.fontSize = currentFontSize + 'px';
            });
        }
    }
    
    // Basic download prevention
    document.addEventListener('keydown', function(e) {
        const pdfContainer = document.getElementById('pdf-viewer-container');
        if (pdfContainer && pdfContainer.contains(e.target)) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'p')) {
                e.preventDefault();
                alert('This document is for viewing only. Downloads and printing are not allowed.');
                return false;
            }
        }
    });
    
    // Escape key to go back
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const pdfContainer = document.getElementById('pdf-viewer-container');
            if (pdfContainer) {
                window.location.assign(window.readBookRoutes?.bookDetails || '/student/books');
            }
        }
    });
    
    console.log('read-book.js initialization complete');
});
