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

            // Render first page
            this.renderPage(this.pageNum);

        } catch (error) {
            
            this.showError('Failed to load PDF document');
        }
    }

    createViewerElements() {
        // Clear container
        this.container.innerHTML = '';

        // Create toolbar
        const toolbar = document.createElement('div');
        toolbar.className = 'pdf-toolbar';
        toolbar.innerHTML = `
            <button id="prev-page" class="pdf-btn" title="Previous Page">
                <i class="fas fa-chevron-left"></i>
            </button>
            <span id="page-info">Page <span id="page-num">${this.pageNum}</span> of <span id="page-count">${this.pdfDoc.numPages}</span></span>
            <button id="next-page" class="pdf-btn" title="Next Page">
                <i class="fas fa-chevron-right"></i>
            </button>
            <div class="pdf-zoom-controls">
                <button id="zoom-out" class="pdf-btn" title="Zoom Out">
                    <i class="fas fa-minus"></i>
                </button>
                <span id="zoom-level">${Math.round(this.scale * 100)}%</span>
                <button id="zoom-in" class="pdf-btn" title="Zoom In">
                    <i class="fas fa-plus"></i>
                </button>
                <button id="zoom-fit" class="pdf-btn" title="Fit to Width">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
            <button id="pdf-back-btn" class="pdf-btn pdf-back-btn" title="Back to Book Details">
                <i class="fas fa-arrow-left"></i> Back
            </button>
        `;
        this.container.appendChild(toolbar);

        // Create canvas container
        const canvasContainer = document.createElement('div');
        canvasContainer.className = 'pdf-canvas-container';
        this.container.appendChild(canvasContainer);

        // Create canvas
        this.canvas = document.createElement('canvas');
        this.canvas.className = 'pdf-canvas';
        this.ctx = this.canvas.getContext('2d');
        canvasContainer.appendChild(this.canvas);

        // Bind events
        this.bindEvents();
    }

    bindEvents() {
        // Navigation buttons
        document.getElementById('prev-page').addEventListener('click', () => {
            if (this.pageNum <= 1) return;
            this.pageNum--;
            this.queueRenderPage(this.pageNum);
        });

        document.getElementById('next-page').addEventListener('click', () => {
            if (this.pageNum >= this.pdfDoc.numPages) return;
            this.pageNum++;
            this.queueRenderPage(this.pageNum);
        });

        // Zoom controls
        document.getElementById('zoom-in').addEventListener('click', () => {
            this.scale = Math.min(this.scale + 0.25, 3.0);
            this.updateZoomDisplay();
            this.queueRenderPage(this.pageNum);
        });

        document.getElementById('zoom-out').addEventListener('click', () => {
            this.scale = Math.max(this.scale - 0.25, 0.5);
            this.updateZoomDisplay();
            this.queueRenderPage(this.pageNum);
        });

        document.getElementById('zoom-fit').addEventListener('click', () => {
            this.fitToWidth();
        });

        // Back button
        document.getElementById('pdf-back-btn').addEventListener('click', () => {
            window.location.href = document.referrer || '/dashboard/books';
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft' && this.pageNum > 1) {
                e.preventDefault();
                this.pageNum--;
                this.queueRenderPage(this.pageNum);
            } else if (e.key === 'ArrowRight' && this.pageNum < this.pdfDoc.numPages) {
                e.preventDefault();
                this.pageNum++;
                this.queueRenderPage(this.pageNum);
            } else if (e.key === '+' || e.key === '=') {
                e.preventDefault();
                this.scale = Math.min(this.scale + 0.25, 3.0);
                this.updateZoomDisplay();
                this.queueRenderPage(this.pageNum);
            } else if (e.key === '-') {
                e.preventDefault();
                this.scale = Math.max(this.scale - 0.25, 0.5);
                this.updateZoomDisplay();
                this.queueRenderPage(this.pageNum);
            } else if (e.key === 'Escape') {
                window.location.href = document.referrer || '/dashboard/books';
            }
        });

        // Mouse wheel zoom
        this.canvas.addEventListener('wheel', (e) => {
            if (e.ctrlKey) {
                e.preventDefault();
                if (e.deltaY < 0) {
                    this.scale = Math.min(this.scale + 0.1, 3.0);
                } else {
                    this.scale = Math.max(this.scale - 0.1, 0.5);
                }
                this.updateZoomDisplay();
                this.queueRenderPage(this.pageNum);
            }
        });

        // Prevent right-click context menu
        this.canvas.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            return false;
        });

        // Prevent text selection and copying
        this.canvas.addEventListener('selectstart', (e) => {
            e.preventDefault();
            return false;
        });

        this.canvas.addEventListener('copy', (e) => {
            e.preventDefault();
            return false;
        });

        this.canvas.addEventListener('cut', (e) => {
            e.preventDefault();
            return false;
        });

        this.canvas.addEventListener('paste', (e) => {
            e.preventDefault();
            return false;
        });

        // Prevent drag and drop
        this.canvas.addEventListener('dragstart', (e) => {
            e.preventDefault();
            return false;
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

    async renderPage(num) {
        this.pageRendering = true;

        try {
            const page = await this.pdfDoc.getPage(num);

            const viewport = page.getViewport({ scale: this.scale });
            this.canvas.height = viewport.height;
            this.canvas.width = viewport.width;

            const renderContext = {
                canvasContext: this.ctx,
                viewport: viewport
            };

            await page.render(renderContext).promise;

            this.pageRendering = false;

            if (this.pageNumPending !== null) {
                this.renderPage(this.pageNumPending);
                this.pageNumPending = null;
            }

            // Update page info
            document.getElementById('page-num').textContent = num;
            document.getElementById('page-count').textContent = this.pdfDoc.numPages;

            // Update navigation buttons
            document.getElementById('prev-page').disabled = num <= 1;
            document.getElementById('next-page').disabled = num >= this.pdfDoc.numPages;

        } catch (error) {
            
            this.pageRendering = false;
        }
    }

    queueRenderPage(num) {
        if (this.pageRendering) {
            this.pageNumPending = num;
        } else {
            this.renderPage(num);
        }
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
            this.queueRenderPage(this.pageNum);
        } catch (error) {
            
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
