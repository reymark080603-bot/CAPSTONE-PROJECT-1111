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

    async validatePDFFile(url) {
        try {
            console.log('Validating PDF URL:', url);
            const response = await fetch(url, { method: 'HEAD' });
            
            if (!response.ok) {
                if (response.status === 404) {
                    throw new Error(`PDF file not found on server (404)`);
                } else {
                    throw new Error(`PDF file not accessible (${response.status})`);
                }
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/pdf')) {
                throw new Error('File is not a valid PDF format');
            }
            
            return true;
        } catch (error) {
            console.error('PDF validation error:', error);
            throw error;
        }
    }

    async init() {
        try {
            // Configure worker
            if (!pdfjsLib.GlobalWorkerOptions.workerSrc) {
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            }

            // Validate PDF file first
            await this.validatePDFFile(this.pdfUrl);

            // Load the PDF document
            const loadingTask = pdfjsLib.getDocument(this.pdfUrl);
            
            loadingTask.onProgress = function(progress) {
                console.log('PDF loading progress:', progress.loaded, '/', progress.total);
            };
            
            this.pdfDoc = await loadingTask.promise;
            
            if (!this.pdfDoc || !this.pdfDoc.numPages || this.pdfDoc.numPages <= 0) {
                throw new Error('PDF document is empty or corrupted');
            }

            // Create canvas and controls
            this.createViewerElements();
            this.renderAllPages();

        } catch (error) {
            console.error('Error loading PDF:', error);
            this.showError('Failed to load PDF document: ' + error.message);
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
                <span id="current-page">1</span> of <span id="total-pages">${this.pdfDoc.numPages}</span>
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
            window.location.replace(`/student/books/${this.getBookId()}`);
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
                window.location.replace(`/student/books/${this.getBookId()}`);
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
                <button onclick="window.location.replace('/student/books/${this.getBookId()}')" class="viewer-btn">
                    <i class="fas fa-arrow-left"></i> Return to Book Details
                </button>
            </div>
        `;
    }
}

// Make globally available
window.ViewOnlyPDFViewer = ViewOnlyPDFViewer;

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
        
        try {
            const loadingDiv = document.getElementById('pdf-loading');
            const viewerContainer = document.getElementById('pdf-viewer-container');

            // Check if PDF.js is loaded
            if (typeof pdfjsLib === 'undefined') {
                throw new Error('PDF.js library failed to load from CDN');
            }
            if (typeof ViewOnlyPDFViewer === 'undefined') {
                throw new Error('ViewOnlyPDFViewer class is not loaded');
            }

            // Get PDF data from window
            const pdfUrl = window.pdfUrl || null;
            const bookTitle = window.bookTitle || '';

            console.log('PDF data:', { pdfUrl, bookTitle });

            if (!pdfUrl || pdfUrl === 'null' || pdfUrl === '') {
                throw new Error('PDF file not found or URL is invalid');
            }

            // Initialize PDF viewer
            const viewer = new ViewOnlyPDFViewer('pdf-viewer-container', pdfUrl, bookTitle);

            // Hide loading, show viewer
            if (loadingDiv) loadingDiv.style.display = 'none';
            if (viewerContainer) viewerContainer.style.display = 'flex';

        } catch (error) {
            console.error('Error initializing PDF viewer:', error);
            const loadingDiv = document.getElementById('pdf-loading');
            if (loadingDiv) {
                loadingDiv.innerHTML = `
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Failed to load PDF viewer: ${error.message}</span>
                    <button onclick="window.location.href=document.referrer || '/student/books'" class="viewer-btn">
                        <i class="fas fa-arrow-left"></i> Back to Books
                    </button>
                `;
            }
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
                window.history.back();
            }
        }
    });
    
    console.log('read-book.js initialization complete');
});
