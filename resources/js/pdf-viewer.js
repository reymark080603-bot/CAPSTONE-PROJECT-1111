// CustomPDFViewer - No top-level pdfjsLib configuration needed
// The worker is configured in the HTML before this script loads

class CustomPDFViewer {
    constructor(containerId, pdfUrl) {
        this.container = document.getElementById(containerId);
        this.pdfUrl = pdfUrl;
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

    async init() {
        try {
            // Configure worker here, inside the class where we know pdfjsLib exists
            if (!pdfjsLib.GlobalWorkerOptions.workerSrc) {
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.worker.min.js';
            }

            // Load the PDF document
            const loadingTask = pdfjsLib.getDocument(this.pdfUrl);
            this.pdfDoc = await loadingTask.promise;

            // Create canvas and controls
            this.createViewerElements();

            // Render all pages
            this.renderAllPages();

        } catch (error) {
            console.error('Error loading PDF:', error);
            this.showError('Failed to load PDF document');
        }
    }

    createViewerElements() {
        // Clear container but keep the existing structure
        const existingScrollContainer = this.container.querySelector('.pdf-scroll-container');
        const existingPagesContainer = this.container.querySelector('.pdf-pages-container');
        
        if (existingScrollContainer && existingPagesContainer) {
            // Use existing containers from HTML
            this.pagesContainer = existingPagesContainer;
            // Clear any existing content
            this.pagesContainer.innerHTML = '';
        } else {
            // Fallback: create new structure
            this.container.innerHTML = '';
            
            // Create scrollable container for all pages
            const scrollContainer = document.createElement('div');
            scrollContainer.className = 'pdf-scroll-container';
            scrollContainer.style.scrollBehavior = 'smooth';
            scrollContainer.style.overflowY = 'auto';
            scrollContainer.style.overflowX = 'auto';
            this.container.appendChild(scrollContainer);

            // Create pages container
            this.pagesContainer = document.createElement('div');
            this.pagesContainer.className = 'pdf-pages-container';
            this.pagesContainer.style.width = '100%';
            this.pagesContainer.style.margin = '0 auto';
            scrollContainer.appendChild(this.pagesContainer);
        }

        // Create toolbar at the top
        const toolbar = document.createElement('div');
        toolbar.className = 'pdf-toolbar';
        toolbar.innerHTML = `
            <div class="pdf-zoom-controls">
                <button id="zoom-out" class="pdf-btn" title="Zoom Out">
                    <i class="fas fa-search-minus"></i>
                </button>
                <span id="zoom-level">${Math.round(this.scale * 100)}%</span>
                <button id="zoom-in" class="pdf-btn" title="Zoom In">
                    <i class="fas fa-search-plus"></i>
                </button>
                <button id="zoom-fit" class="pdf-btn" title="Fit to Width">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
            <button id="pdf-back-btn" class="pdf-btn pdf-back-btn" title="Back to Book Details">
                <i class="fas fa-arrow-left"></i> Back to Book
            </button>
        `;
        // Insert toolbar at the beginning of the container
        this.container.insertBefore(toolbar, this.container.firstChild);

        // Bind events
        this.bindEvents();
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

        // Back button
        document.getElementById('pdf-back-btn').addEventListener('click', () => {
            window.location.href = document.referrer || '/dashboard/books';
        });

        // Keyboard zoom and navigation
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
                window.location.href = document.referrer || '/dashboard/books';
            } else if (e.key === 'ArrowUp' || e.key === 'ArrowDown' || e.key === 'Home' || e.key === 'End') {
                // Allow normal browser scrolling behavior like Chrome
                return; // Don't prevent default behavior
            }
        });

        // Optimized smooth scrolling without lag
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
            // Allow native smooth scrolling - no interference
        }, { passive: true });

        // Prevent right-click context menu on the entire document
        document.addEventListener('contextmenu', (e) => {
            if (e.target.closest('.pdf-page-canvas')) {
                e.preventDefault();
                return false;
            }
        });

        // Prevent text selection and copying on canvas elements
        document.addEventListener('selectstart', (e) => {
            if (e.target.closest('.pdf-page-canvas')) {
                e.preventDefault();
                return false;
            }
        });

        document.addEventListener('copy', (e) => {
            if (e.target.closest('.pdf-page-canvas')) {
                e.preventDefault();
                return false;
            }
        });

        document.addEventListener('cut', (e) => {
            if (e.target.closest('.pdf-page-canvas')) {
                e.preventDefault();
                return false;
            }
        });

        document.addEventListener('paste', (e) => {
            if (e.target.closest('.pdf-page-canvas')) {
                e.preventDefault();
                return false;
            }
        });

        // Prevent drag and drop
        document.addEventListener('dragstart', (e) => {
            if (e.target.closest('.pdf-page-canvas')) {
                e.preventDefault();
                return false;
            }
        });

        // Prevent print screen (as much as possible)
        document.addEventListener('keyup', (e) => {
            if (e.key === 'PrintScreen') {
                // Clear clipboard if possible
                if (navigator.clipboard) {
                    navigator.clipboard.writeText('');
                }
                alert('Screenshots are not allowed in this viewer.');
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
            pageContainer.className = 'pdf-page-container';
            // Remove all transitions for instant loading
            pageContainer.style.transition = 'none';
            
            // Add page number indicator (optional)
            const pageNumber = document.createElement('div');
            pageNumber.className = 'pdf-page-number';
            pageNumber.textContent = `Page ${num} of ${this.pdfDoc.numPages}`;
            pageContainer.appendChild(pageNumber);
            
            // Create canvas for this page
            const canvas = document.createElement('canvas');
            canvas.className = 'pdf-page-canvas';
            // Remove opacity and transform effects for simple loading
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
            
            // Add margin between pages (except the last one)
            if (num < this.pdfDoc.numPages) {
                pageContainer.style.marginBottom = '20px';
            }
            
            // Render the page
            await page.render({
                canvasContext: context,
                viewport: viewport
            }).promise;
            
            // Remove fade-in animation for simple loading
            
        } catch (error) {
            console.error(`Error rendering page ${num}:`, error);
            const errorDiv = document.createElement('div');
            errorDiv.className = 'pdf-page-error';
            errorDiv.textContent = `Error loading page ${num}`;
            this.pagesContainer.appendChild(errorDiv);
        }
    }

    queueRenderPage() {
        // No longer needed as we render all pages at once
    }

    updateZoomDisplay() {
        document.getElementById('zoom-level').textContent = Math.round(this.scale * 100) + '%';
    }

    async fitToWidth() {
        if (!this.pdfDoc) return;

        try {
            const containerWidth = this.container.clientWidth - 40; // Account for padding
            const page = await this.pdfDoc.getPage(1);
            const viewport = page.getViewport({ scale: 1 });
            this.scale = containerWidth / viewport.width;
            this.scale = Math.max(0.5, Math.min(this.scale, 3.0)); // Clamp scale
            this.updateZoomDisplay();
            this.renderAllPages(); // Render all pages with new scale
        } catch (error) {
            console.error('Error fitting to width:', error);
        }
    }

    // Page jumping navigation methods
    scrollToNextPage() {
        const scrollContainer = this.container.querySelector('.pdf-scroll-container');
        const currentScrollTop = scrollContainer.scrollTop;
        const pageContainers = scrollContainer.querySelectorAll('.pdf-page-container');
        
        for (let i = 0; i < pageContainers.length; i++) {
            const container = pageContainers[i];
            if (container.offsetTop > currentScrollTop + 10) {
                container.scrollIntoView({ behavior: 'auto', block: 'start' });
                break;
            }
        }
    }

    scrollToPreviousPage() {
        const scrollContainer = this.container.querySelector('.pdf-scroll-container');
        const currentScrollTop = scrollContainer.scrollTop;
        const pageContainers = scrollContainer.querySelectorAll('.pdf-page-container');
        
        for (let i = pageContainers.length - 1; i >= 0; i--) {
            const container = pageContainers[i];
            if (container.offsetTop < currentScrollTop - 10) {
                container.scrollIntoView({ behavior: 'auto', block: 'start' });
                break;
            }
        }
    }

    scrollToPage(pageNumber) {
        const pageContainers = this.container.querySelectorAll('.pdf-page-container');
        if (pageContainers[pageNumber - 1]) {
            pageContainers[pageNumber - 1].scrollIntoView({ behavior: 'auto', block: 'start' });
        }
    }

    showError(message) {
        this.container.innerHTML = `
            <div class="pdf-error">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Error Loading PDF</h3>
                <p>${message}</p>
                <button onclick="window.location.href=document.referrer || '/dashboard/books'" class="pdf-btn">
                    <i class="fas fa-arrow-left"></i> Back to Books
                </button>
            </div>
        `;
    }
}

// Make globally available
window.CustomPDFViewer = CustomPDFViewer;