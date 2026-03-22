document.addEventListener('DOMContentLoaded', function() {
    let currentFontSize = 18; // Default font size in pixels
    let currentChapter = 1;
    let totalChapters = 12;
    let readingProgress = 0;

    // Basic Download Prevention - Only prevent direct save/print shortcuts
    document.addEventListener('keydown', function(e) {
        // Only prevent Ctrl+S (Save) and Ctrl+P (Print) on the PDF container
        const pdfContainer = document.querySelector('.pdf-viewer-container');
        if (pdfContainer && pdfContainer.contains(e.target)) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'p')) {
                e.preventDefault();
                // Show a friendly message
                alert('This document is for viewing only. Downloads and printing are not allowed.');
                return false;
            }
        }
    });

    const bookContent = document.getElementById('book-content');
    const readingText = document.getElementById('reading-text');

    // Font size controls (only for HTML text mode)
    const increaseBtn = document.getElementById('increase-font');
    const decreaseBtn = document.getElementById('decrease-font');
    if (readingText && increaseBtn && decreaseBtn) {
        increaseBtn.addEventListener('click', () => {
            currentFontSize += 2;
            if (currentFontSize > 24) currentFontSize = 24;
            readingText.style.fontSize = currentFontSize + 'px';
        });

        decreaseBtn.addEventListener('click', () => {
            currentFontSize -= 2;
            if (currentFontSize < 12) currentFontSize = 12;
            readingText.style.fontSize = currentFontSize + 'px';
        });
    }

    // Hide PDF download button only (with aggressive retry for browser PDF viewer)
    const pdfIframe = document.getElementById('pdf-iframe');
    if (pdfIframe) {
        let hideAttempts = 0;
        const maxHideAttempts = 50; // Try for up to 5 seconds

        function hideDownloadButton() {
            hideAttempts++;
            try {
                const iframeDoc = pdfIframe.contentDocument || pdfIframe.contentWindow.document;
                if (!iframeDoc) {
                    if (hideAttempts < maxHideAttempts) {
                        setTimeout(hideDownloadButton, 100);
                    }
                    return;
                }

                // Comprehensive selectors for download button in browser PDF viewers (Chrome, Firefox, Brave, etc.)
                const downloadSelectors = [
                    'button[title*="Download"]',
                    'button[title="Download"]',
                    '.download',
                    '#download',
                    '[data-l10n-id="download"]',
                    'button[aria-label*="Download"]',
                    '.toolbarButton.download',
                    '#toolbarViewer-download',
                    // Additional selectors for different browsers
                    'button[data-tooltip*="Download"]',
                    '.downloadButton',
                    '[class*="download"]',
                    // Generic search for buttons containing download text
                    'button'
                ];

                let downloadButton = null;
                for (const selector of downloadSelectors) {
                    try {
                        if (selector === 'button') {
                            // Special handling for generic button search
                            const buttons = iframeDoc.querySelectorAll('button');
                            for (const btn of buttons) {
                                const text = (btn.textContent || '').toLowerCase();
                                const title = (btn.getAttribute('title') || '').toLowerCase();
                                const ariaLabel = (btn.getAttribute('aria-label') || '').toLowerCase();
                                if (text.includes('download') || title.includes('download') || ariaLabel.includes('download')) {
                                    downloadButton = btn;
                                    break;
                                }
                            }
                        } else {
                            downloadButton = iframeDoc.querySelector(selector);
                        }
                        if (downloadButton) break;
                    } catch (e) {
                        continue;
                    }
                }

                if (downloadButton) {
                    downloadButton.style.display = 'none';
                    downloadButton.style.visibility = 'hidden';
                    downloadButton.disabled = true;
                    downloadButton.onclick = function(e) { e.preventDefault(); return false; };
                    
                    return; // Success, stop trying
                }

                // Also search in toolbar containers
                const toolbarSelectors = ['.toolbar', '#toolbar', '[class*="toolbar"]', '#viewerContainer', '.viewer'];
                for (const toolbarSel of toolbarSelectors) {
                    const toolbar = iframeDoc.querySelector(toolbarSel);
                    if (toolbar) {
                        const buttons = toolbar.querySelectorAll('button, [role="button"]');
                        buttons.forEach(btn => {
                            const text = (btn.textContent || '').toLowerCase();
                            const title = (btn.getAttribute('title') || '').toLowerCase();
                            const ariaLabel = (btn.getAttribute('aria-label') || '').toLowerCase();
                            const className = (btn.className || '').toLowerCase();
                            const id = (btn.id || '').toLowerCase();

                            if (text.includes('download') || title.includes('download') ||
                                ariaLabel.includes('download') || className.includes('download') ||
                                id.includes('download')) {
                                btn.style.display = 'none';
                                btn.style.visibility = 'hidden';
                                btn.disabled = true;
                                btn.onclick = function(e) { e.preventDefault(); return false; };
                                
                            }
                        });
                    }
                }

                // Prevent right-click download on PDF content
                iframeDoc.addEventListener('contextmenu', function(e) {
                    const target = e.target;
                    if (target.tagName === 'CANVAS' || target.closest('.pdfViewer') || target.closest('#viewerContainer')) {
                        e.preventDefault();
                        return false;
                    }
                });

                // Also disable any download links
                const downloadLinks = iframeDoc.querySelectorAll('a[href*=".pdf"], a[download]');
                downloadLinks.forEach(link => {
                    link.style.display = 'none';
                    link.href = '#';
                });

            } catch (error) {
                
            }

            // Continue trying if not found and not max attempts
            if (hideAttempts < maxHideAttempts) {
                setTimeout(hideDownloadButton, 100);
            } else {
                
            }
        }

        // Start hiding process
        pdfIframe.onload = function() {
            hideAttempts = 0; // Reset attempts on load
            setTimeout(hideDownloadButton, 200); // Start after short delay
        };

        // Also try immediately and after delays in case onload doesn't fire
        setTimeout(hideDownloadButton, 500);
        setTimeout(hideDownloadButton, 1000);
        setTimeout(hideDownloadButton, 2000);
        setTimeout(hideDownloadButton, 3000);
    }

    // Escape key listener for PDF viewer (go back to previous page)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const pdfContainer = document.getElementById('pdf-viewer-container');
            if (pdfContainer) {
                window.history.back();
            }
        }
    });

    // Chapter navigation (only for HTML text mode)
    if (hasHtmlContent && nextChapterBtn) {
        nextChapterBtn.addEventListener('click', () => {
            if (currentChapter < totalChapters) {
                currentChapter++;
                if (chapterTitle) chapterTitle.textContent = `Chapter ${currentChapter}: ${getChapterTitle(currentChapter)}`;
                loadChapterContent(currentChapter);
                updateChapterNavigation();

                // Scroll to top
                bookContent.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }

    if (hasHtmlContent && prevChapterBtn) {
        prevChapterBtn.addEventListener('click', () => {
            if (currentChapter > 1) {
                currentChapter--;
                if (chapterTitle) chapterTitle.textContent = `Chapter ${currentChapter}: ${getChapterTitle(currentChapter)}`;
                loadChapterContent(currentChapter);
                updateChapterNavigation();

                // Scroll to top
                bookContent.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }

    // Initialize (only for HTML text mode)
    if (hasHtmlContent) {
        loadChapterContent(currentChapter); // Load first chapter content
        updateChapterNavigation();

        // Auto-save reading progress periodically
        setInterval(() => {
            // Here you could send AJAX request to save current chapter/progress
            console.log(`Auto-saving progress: Chapter ${currentChapter}, ${Math.round(readingProgress)}%`);
        }, 30000); // Save every 30 seconds
    }
});
