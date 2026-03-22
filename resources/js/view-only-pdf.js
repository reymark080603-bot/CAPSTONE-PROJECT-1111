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
        // Extract book ID from current URL or from the PDF URL
        const currentUrl = window.location.pathname;
        const match = currentUrl.match(/\/books\/(\d+)/);
        if (match) {
            return match[1];
        }
        
        // Fallback: try to extract from PDF URL
        const pdfMatch = this.pdfUrl.match(/\/books\/(\d+)\//);
        if (pdfMatch) {
            return pdfMatch[1];
        }
        
        return '1'; // Default fallback
    }

    async validatePDFFile(url) {
        try {
            console.log('Validating PDF URL:', url);
            const response = await fetch(url, { method: 'HEAD' });
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                if (response.status === 404) {
                    throw new Error(`PDF file not found on server (404). The file may have been deleted or moved.`);
                } else if (response.status === 403) {
                    throw new Error(`Access to PDF file forbidden (403). Check file permissions.`);
                } else {
                    throw new Error(`PDF file not accessible (${response.status}). Server error occurred.`);
                }
            }
            
            const contentType = response.headers.get('content-type');
            console.log('Content type:', contentType);
            
            if (!contentType || !contentType.includes('application/pdf')) {
                throw new Error(`File is not a valid PDF format. Content type: ${contentType || 'unknown'}`);
            }
            
            const contentLength = response.headers.get('content-length');
            console.log('Content length:', contentLength);
            
            if (contentLength && parseInt(contentLength) === 0) {
                throw new Error('PDF file is empty');
            }
            
            // Check if file is too small to be a valid PDF (less than 1KB)
            if (contentLength && parseInt(contentLength) < 1024) {
                throw new Error('PDF file is too small and appears to be corrupted');
            }
            
            console.log('PDF validation passed');
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
            
            // Add error handling for invalid PDF
            loadingTask.onProgress = function(progress) {
                console.log('PDF loading progress:', progress.loaded, '/', progress.total);
            };
            
            this.pdfDoc = await loadingTask.promise;
            
            // Validate PDF document
            if (!this.pdfDoc || !this.pdfDoc.numPages || this.pdfDoc.numPages <= 0) {
                throw new Error('PDF document is empty or corrupted');
            }

            // Create canvas and controls
            this.createViewerElements();

            // Render all pages for continuous scrolling
            this.renderAllPages();

        } catch (error) {
            console.error('Error loading PDF:', error);
            
            // Handle specific PDF errors
            let errorMessage = 'Failed to load PDF document';
            if (error.name === 'InvalidPDFException') {
                errorMessage = 'The PDF file is corrupted or invalid. The file may be incomplete or damaged during upload. You can try downloading the file to open it with a local PDF reader.';
            } else if (error.name === 'PasswordException') {
                errorMessage = 'The PDF file is password protected and cannot be opened.';
            } else if (error.name === 'UnknownErrorException') {
                errorMessage = 'An unknown error occurred while loading the PDF.';
            } else if (error.message.includes('network') || error.message.includes('not accessible')) {
                errorMessage = 'Network error: Unable to load PDF file. Please check your connection and file path.';
            } else if (error.message.includes('not a valid PDF')) {
                errorMessage = 'The file is not a valid PDF format. Please upload a proper PDF file.';
            } else if (error.message.includes('empty') || error.message.includes('corrupted')) {
                errorMessage = 'The PDF file appears to be empty or corrupted. Please re-upload the file.';
            }
            
            this.showError(errorMessage);
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

        // Create scrollable container for continuous pages
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
        
        // Add scroll detection for page tracking
        this.setupScrollTracking();
    }

    bindEvents() {
        // Zoom controls only - no page navigation needed for continuous scroll
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
            // Use replace to avoid adding to history
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
                // Use replace to avoid adding to history
                window.location.replace(`/student/books/${this.getBookId()}`);
            }
            // Allow normal scrolling for arrow keys
        });

        // Mouse wheel zoom
        this.container.addEventListener('wheel', (e) => {
            if (e.ctrlKey) {
                e.preventDefault();
                if (e.deltaY < 0) {
                    this.scale = Math.min(this.scale + 0.1, 3.0);
                } else {
                    this.scale = Math.max(this.scale - 0.1, 0.5);
                }
                this.updateZoomDisplay();
                this.renderAllPages();
            }
            // Allow native scrolling without Ctrl
        }, { passive: true });
    }

    setupScrollTracking() {
        const scrollContainer = this.container.querySelector('.viewer-scroll-container');
        
        scrollContainer.addEventListener('scroll', () => {
            this.updateCurrentPage();
        }, { passive: true });
    }

    updateCurrentPage() {
        const scrollContainer = this.container.querySelector('.viewer-scroll-container');
        const pageContainers = scrollContainer.querySelectorAll('.viewer-page-container');
        const scrollTop = scrollContainer.scrollTop;
        const containerHeight = scrollContainer.clientHeight;
        
        let currentPage = 1;
        
        for (let i = 0; i < pageContainers.length; i++) {
            const pageContainer = pageContainers[i];
            const pageTop = pageContainer.offsetTop;
            const pageHeight = pageContainer.offsetHeight;
            
            // Check if this page is visible in the viewport
            if (pageTop <= scrollTop + containerHeight / 2 && pageTop + pageHeight > scrollTop + containerHeight / 2) {
                currentPage = i + 1;
                break;
            }
        }
        
        // Update the page display
        const currentPageElement = document.getElementById('current-page');
        if (currentPageElement) {
            currentPageElement.textContent = currentPage;
        }
    }

    applySecurityMeasures() {
        // Prevent right-click context menu on all canvases
        document.addEventListener('contextmenu', (e) => {
            if (e.target.closest('.viewer-page-canvas')) {
                e.preventDefault();
                return false;
            }
        });

        // Prevent text selection on canvas elements
        document.addEventListener('selectstart', (e) => {
            if (e.target.closest('.viewer-page-canvas')) {
                e.preventDefault();
                return false;
            }
        });

        // Prevent copying on canvas elements
        document.addEventListener('copy', (e) => {
            if (e.target.closest('.viewer-page-canvas')) {
                e.preventDefault();
                return false;
            }
        });

        document.addEventListener('cut', (e) => {
            if (e.target.closest('.viewer-page-canvas')) {
                e.preventDefault();
                return false;
            }
        });

        // Prevent drag on canvas elements
        document.addEventListener('dragstart', (e) => {
            if (e.target.closest('.viewer-page-canvas')) {
                e.preventDefault();
                return false;
            }
        });

        // Disable print screen alert
        document.addEventListener('keyup', (e) => {
            if (e.key === 'PrintScreen') {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText('');
                }
            }
        });

        // Disable F12 and inspect element attempts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'F12' || 
                (e.ctrlKey && e.shiftKey && e.key === 'I') ||
                (e.ctrlKey && e.shiftKey && e.key === 'J') ||
                (e.ctrlKey && e.key === 'U')) {
                e.preventDefault();
                return false;
            }
        });
    }

    async renderAllPages() {
        this.pageRendering = true;
        
        try {
            // Clear existing pages
            this.pagesContainer.innerHTML = '';
            
            // Sequential rendering - one page at a time
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
            
            // Add page number indicator
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
            
            // Create canvas for this page
            const canvas = document.createElement('canvas');
            canvas.className = 'viewer-page-canvas';
            canvas.style.maxWidth = '100%';
            canvas.style.height = 'auto';
            canvas.style.display = 'block';
            pageContainer.appendChild(canvas);
            
            // Add to pages container
            this.pagesContainer.appendChild(pageContainer);
            
            // Get the page
            const page = await this.pdfDoc.getPage(num);
            
            // Set canvas size to match page dimensions
            const viewport = page.getViewport({ scale: this.scale });
            const context = canvas.getContext('2d');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            
            // Render the page
            await page.render({
                canvasContext: context,
                viewport: viewport
            }).promise;
            
        } catch (error) {
            console.error(`Error rendering page ${num}:`, error);
            const errorDiv = document.createElement('div');
            errorDiv.className = 'viewer-page-error';
            errorDiv.textContent = `Error loading page ${num}`;
            errorDiv.style.padding = '20px';
            errorDiv.style.background = '#f87171';
            errorDiv.style.color = 'white';
            errorDiv.style.borderRadius = '4px';
            errorDiv.style.marginBottom = '20px';
            this.pagesContainer.appendChild(errorDiv);
        }
    }

    queueRenderPage(num) {
        if (this.pageRendering) {
            this.pageNumPending = num;
        } else {
            this.renderPage(num);
        }
    }

    updatePageInput() {
        document.getElementById('page-input').value = this.pageNum;
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
            this.renderAllPages(); // Render all pages with new scale
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
