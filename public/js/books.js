/**
 * Books Management System
 * - Search functionality for books by title, author, or ISBN
 * - Filtering by course, year level, and category
 * - Sorting by relevance, title, author, or year
 */
class BooksManager {
    // Add cleanup function reference
    cleanupModalListeners = null;
    constructor() {
        this.state = {
            searchTerm: '',
            currentPage: 1,
            isLoading: false,
            allBooks: [],
            filteredBooks: [],
            booksPerPage: 12,
            sortBy: 'relevance'
        };

        // Wait for DOM to be ready before initializing
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    init() {
        
        
        // Get all DOM elements
        this.elements = {
            booksContainer: document.getElementById('books-grid'),
            pagination: document.getElementById('pagination'),
            searchInput: document.getElementById('search-books'),
            loadingIndicator: document.getElementById('loading-indicator'),
            noResults: document.getElementById('no-results'),
            resultsCount: document.getElementById('results-count'),
            filterForm: document.getElementById('filter-form')
        };

        console.log('DOM Elements found:', {
            searchInput: !!this.elements.searchInput,
            booksContainer: !!this.elements.booksContainer,
            resultsCount: !!this.elements.resultsCount
        });

        // Initialize search from URL (only if they have values)
        this.initSearchFromURL();
        
        this.setupEventListeners();
        this.loadBooks();
        
        // Add manual test function (for debugging)
        window.testFilter = () => this.testFilterPanel();
        window.forceShowPanel = () => this.forceShowFilterPanel();
        
        // Cleanup event listeners on unmount
        const originalUnload = window.onbeforeunload;
        window.onbeforeunload = (e) => {
            if (this.cleanupModalListeners) {
                this.cleanupModalListeners();
            }
            if (originalUnload) return originalUnload(e);
        };
    }

    testFilterPanel() {
        
        const panel = document.getElementById('filter-panel');
        const toggle = document.getElementById('filter-toggle');
        
        
        
        
        if (panel) {
            panel.classList.remove('hidden');
            panel.style.display = 'block';
            panel.style.border = '3px solid red';
            
        } else {
            
        }
        
        if (toggle) {
            toggle.style.border = '2px solid blue';
            
        }
    }

    forceShowFilterPanel() {
        
        const panel = document.getElementById('filter-panel');
        if (panel) {
            panel.innerHTML = '<div style="background: red; color: white; padding: 20px;">FILTER PANEL IS NOW VISIBLE!</div>';
            panel.style.cssText = 'display: block !important; visibility: visible !important; position: absolute !important; top: 100px !important; left: 100px !important; z-index: 99999 !important; background: red !important; color: white !important; padding: 20px !important;';
            
        }
    }

    resetInitialState() {
        
        
        // Clear search input
        if (this.elements.searchInput) {
            this.elements.searchInput.value = '';
            
        }
        
        // Clear all filter form values
        if (this.elements.filterForm) {
            const selects = this.elements.filterForm.querySelectorAll('select');
            if (selects) {
                selects.forEach(select => {
                    select.value = '';
                    
                });
            }
        }
        
        // Clear URL parameters to prevent empty filters
        if (window.history.pushState) {
            const cleanUrl = window.location.pathname;
            window.history.pushState({}, '', cleanUrl);
            
        }
        
        // Reset state to completely clean values
        this.state.searchTerm = '';
        this.state.currentFilters = {
            course: '',
            category: '',
            availability: 'all'
        };
        this.state.currentPage = 1;
        this.state.activeFilters = 0;
        this.state.allBooks = [];
        this.state.filteredBooks = [];
        
        
        
        // Update UI to show no active filters
        this.updateActiveFilterCount();
        
        // Hide filter panel if it's open
        if (this.elements.filterPanel) {
            this.elements.filterPanel.classList.add('hidden');
            this.elements.filterPanel.style.display = 'none';
            
        }
    }

    setupEventListeners() {
        // Search input event
        if (this.elements.searchInput) {
            let searchTimeout;
            this.elements.searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.state.searchTerm = e.target.value.trim();
                    this.state.currentPage = 1;
                    this.filterBooks();
                }, 300);
            });
        }
        
        // Reset search button
        const resetBtn = document.getElementById('reset-search');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                this.resetSearch();
            });
        }
    }
    
    resetSearch() {
        
        // Clear search input
        if (this.elements.searchInput) {
            this.elements.searchInput.value = '';
        }
        // Clear search state
        this.state.searchTerm = '';
        this.state.currentPage = 1;
        // Reset to show all books
        this.state.filteredBooks = [...this.state.allBooks];
        // Update display
        this.renderBooks();
        this.updateResultsCount();
        // Clear URL parameters
        if (window.history.pushState) {
            const cleanUrl = window.location.pathname;
            window.history.pushState({}, '', cleanUrl);
        }
    }

    initSearchFromURL() {
        
        const params = new URLSearchParams(window.location.search);
        console.log('URL params:', Object.fromEntries(params.entries()));
        
        // Always clear search on page load/refresh to show all books
        this.state.searchTerm = '';
        
        // Handle search term from URL - only set the input value but don't auto-filter
        const q = (params.get('search') || params.get('q') || '').trim();
        if (q && q.length > 0) {
            
            // Set the search input value but don't trigger filtering yet
            if (this.elements.searchInput) this.elements.searchInput.value = q;
            // User needs to manually search to see filtered results
        } else {
            
            // Clear search input
            if (this.elements.searchInput) {
                this.elements.searchInput.value = '';
            }
        }
    }

    async loadBooks() {
        try {
            
            this.setLoading(true);
            
            // Extract books from DOM that were rendered by Blade template
            
            this.extractBooksFromDOM();
            
            
            // Initialize with all books visible (no filtering)
            this.state.filteredBooks = [...this.state.allBooks];
            
            
            // Don't filter initially - just update the count
            // The books are already displayed by the Blade template
            
            this.updateResultsCount();
            
        } catch (error) {
            
            this.showError('Failed to load books. Please try again.');
        } finally {
            this.setLoading(false);
            console.log('Load books completed. Final state:', {
                allBooks: this.state.allBooks.length,
                filteredBooks: this.state.filteredBooks.length,
                searchTerm: this.state.searchTerm
            });
        }
    }

    extractBooksFromDOM() {
        const bookElements = document.querySelectorAll('.book-card');
        
        
        if (bookElements.length === 0) {
            
            // Try other possible selectors
            const altElements = document.querySelectorAll('[data-book-id]');
            
        }
        
        this.state.allBooks = Array.from(bookElements).map(bookEl => {
            const book = {
                id: bookEl.dataset.bookId,
                title: bookEl.querySelector('h3')?.textContent?.trim() || 'Untitled',
                author: bookEl.querySelector('.text-gray-600.font-medium')?.textContent?.trim() || 'Unknown Author',
                category: bookEl.querySelector('.text-gray-500.uppercase')?.textContent?.trim() || 'General',
                course: bookEl.querySelector('.text-gray-500.uppercase')?.textContent?.trim() || 'General',
                description: bookEl.querySelector('.text-gray-600.text-xs')?.textContent?.trim() || '',
                year: parseInt(bookEl.dataset.year) || new Date().getFullYear(),
                available: true, // Open access system
                cover: bookEl.querySelector('.book-cover-img')?.src || ''
            };
            
            return book;
        });
        
        
        if (this.state.allBooks.length > 0) {
            
        } else {
            
            // Log the DOM structure for debugging
            const booksGrid = document.getElementById('books-grid');
            if (booksGrid) {
                
                console.log('Books grid HTML (first 500 chars):', booksGrid.innerHTML.substring(0, 500));
                
            } else {
                
            }
        }
        
        this.state.filteredBooks = [...this.state.allBooks];
    }

    filterBooks() {
        this.setLoading(true);
        
        try {
            
            
            
            
            // Apply search term
            let results = [...this.state.allBooks];
            
            if (this.state.searchTerm) {
                const searchLower = this.state.searchTerm.toLowerCase();
                results = results.filter(book => 
                    (book.title && book.title.toLowerCase().includes(searchLower)) ||
                    (book.author && book.author.toLowerCase().includes(searchLower)) ||
                    (book.category && book.category.toLowerCase().includes(searchLower)) ||
                    (book.description && book.description.toLowerCase().includes(searchLower)) ||
                    (book.course && book.course.toLowerCase().includes(searchLower))
                );
                
            }
            
            // Apply sorting
            this.sortBooks(results);
            
            // Update state
            this.state.filteredBooks = results;
            this.state.totalPages = Math.ceil(results.length / this.state.booksPerPage);
            
            // Render results
            this.renderBooks();
            this.updateResultsCount();
            
        } catch (error) {
            
            this.showError('An error occurred while filtering books.');
        } finally {
            this.setLoading(false);
        }
    }

    sortBooks(books) {
        const { sortBy } = this.state;
        
        return books.sort((a, b) => {
            switch (sortBy) {
                case 'title':
                    return a.title.localeCompare(b.title);
                case 'author':
                    return a.author.localeCompare(b.author);
                case 'year':
                    return (b.year || 0) - (a.year || 0);
                default: // relevance
                    return 0;
            }
        });
    }

    renderBooks() {
        if (!this.elements.booksContainer) return;
        
        const { filteredBooks, currentPage, booksPerPage } = this.state;
        const startIndex = (currentPage - 1) * booksPerPage;
        const paginatedBooks = filteredBooks.slice(startIndex, startIndex + booksPerPage);
        
        // If no search term and no active filters, keep the original Blade template display
        if (!this.state.searchTerm && !this.hasActiveFilters()) {
            
            // Don't modify the DOM, just update the count
            this.updateResultsCount();
            return;
        }
        
        // Show "No books found" only when filtering/searching yields no results
        if (paginatedBooks.length === 0) {
            this.elements.booksContainer.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <div class="text-gray-300 text-6xl mb-4">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No books found</h3>
                    <p class="text-gray-500">Try adjusting your search or filter criteria.</p>
                </div>
            `;
            return;
        }
        
        // Only regenerate cards when filtering/searching
        
        this.elements.booksContainer.innerHTML = paginatedBooks.map(b => this.createBookCard(b)).join('');
        
        // Always update the count
        this.updateResultsCount();
    }
    
    hasActiveFilters() {
        // Only check for search term since filters are disabled
        return this.state.searchTerm && this.state.searchTerm.trim() !== '';
    }

    updateResultsCount() {
        if (!this.elements.resultsCount) return;
        
        // Count actual book elements in the DOM first
        const bookElements = document.querySelectorAll('.book-card');
        const visibleCount = bookElements.length;
        
        // If we have books in state, use that count
        let totalCount = this.state.allBooks.length > 0 ? this.state.allBooks.length : visibleCount;
        
        // Try to get total from the initial Blade data
        const initialCount = document.querySelector('#visible-count');
        if (initialCount && this.state.allBooks.length === 0) {
            totalCount = parseInt(initialCount.textContent) || visibleCount;
        }
        
        
        
        // Update the display
        const visibleElement = this.elements.resultsCount.querySelector('#visible-count');
        const totalElement = this.elements.resultsCount.querySelector('#total-count');
        
        if (visibleElement) visibleElement.textContent = visibleCount;
        if (totalElement) totalElement.textContent = totalCount;
        if (this.elements.noResults) {
            this.elements.noResults.classList.toggle('hidden', visibleCount > 0);
        }
    }

    setLoading(isLoading) {
        this.state.isLoading = isLoading;
        
        if (this.elements.loadingIndicator) {
            this.elements.loadingIndicator.classList.toggle('hidden', !isLoading);
        }
        
        if (this.elements.booksContainer) {
            this.elements.booksContainer.style.opacity = isLoading ? '0.5' : '1';
            this.elements.booksContainer.style.pointerEvents = isLoading ? 'none' : 'auto';
        }
    }

    showError(message) {
        // In a real app, you would show a nice error message to the user
        
        alert(message);
    }

    // Handle book card click events
    handleBookCardClick(e) {
        const card = e.target.closest('.book-card');
        if (!card) return;
        
        const bookId = card.dataset.bookId;
        if (e.target.closest('.btn-borrow')) {
            e.preventDefault();
            this.handleBorrowClick(bookId);
        } else if (e.target.closest('.btn-details')) {
            e.preventDefault();
            this.showBookDetails(bookId);
        }
    }
    
    handleBorrowClick(bookId) {
        // In a real app, you would show a modal or redirect to a borrow page
        
        // Example: window.location.href = `/books/${bookId}/borrow`;
    }
    
    async showBookDetails(bookId) {
        if (!bookId) return;
        
        // Get modal elements
        const modal = document.getElementById('book-modal');
        const bookDetails = document.getElementById('book-details');
        const modalLoading = document.getElementById('modal-loading');
        const modalError = document.getElementById('modal-error');
        const closeBtn = document.getElementById('close-modal');
        const retryBtn = document.getElementById('retry-loading');
        
        // Check if modal exists before proceeding
        if (!modal) {
            
            return;
        }
        
        // Show modal and loading state with null checks
        modal.classList.remove('hidden');
        if (bookDetails) bookDetails.classList.add('hidden');
        if (modalError) modalError.classList.add('hidden');
        if (modalLoading) modalLoading.classList.remove('hidden');
        
        // Close modal function
        const closeModal = () => {
            if (modal) modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        };
        
        // Setup event listeners for modal
        const setupModalListeners = () => {
            // Close on backdrop click
            const backdrop = document.getElementById('modal-backdrop');
            if (backdrop) {
                backdrop.addEventListener('click', closeModal);
            }
            
            // Close on escape key
            const handleEscape = (e) => {
                if (e.key === 'Escape') closeModal();
            };
            document.addEventListener('keydown', handleEscape);
            
            // Close button
            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }
            
            // Retry button
            if (retryBtn) {
                retryBtn.addEventListener('click', () => this.showBookDetails(bookId));
            }
            
            // Cleanup function
            return () => {
                document.removeEventListener('keydown', handleEscape);
                if (closeBtn) closeBtn.removeEventListener('click', closeModal);
                if (retryBtn) retryBtn.removeEventListener('click', () => this.showBookDetails(bookId));
                if (backdrop) backdrop.removeEventListener('click', closeModal);
            };
        };
        
        // Cleanup previous listeners and set up new ones
        if (this.cleanupModalListeners) this.cleanupModalListeners();
        this.cleanupModalListeners = setupModalListeners();
        
        try {
            // Prevent body scroll when modal is open
            document.body.style.overflow = 'hidden';
            
            // Fetch book details
            const response = await fetch(`/student/books/${bookId}/details`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });
            
            if (!response.ok) throw new Error('Failed to fetch book details');
            
            const book = await response.json();
            
            // Hide loading, show content
            if (modalLoading) modalLoading.classList.add('hidden');
            if (bookDetails) {
                bookDetails.classList.remove('hidden');
                bookDetails.innerHTML = this.generateBookDetailsHTML(book);
            }
            
        } catch (error) {
            
            // Show error state
            if (modalLoading) modalLoading.classList.add('hidden');
            if (modalError) modalError.classList.remove('hidden');
        }
    }
    
    performSearch() {
        this.state.currentPage = 1;
        this.showLoading(true);
        this.filterBooks(); // Use the main filterBooks method
        this.updateBookDisplay();
        this.updateResultsCount();
        this.showLoading(false);
    }

    
    updateBookDisplay() {
        if (!this.elements.booksContainer) return;
        const start = (this.state.currentPage - 1) * this.state.booksPerPage;
        const end = start + this.state.booksPerPage;
        const slice = this.state.filteredBooks.slice(start, end);
        if (!slice.length) {
            this.showNoResults(true);
            this.elements.booksContainer.innerHTML = '';
        } else {
            this.showNoResults(false);
            this.elements.booksContainer.innerHTML = slice.map(b => this.createBookCard(b)).join('');
        }
        this.updatePagination();
    }

    createBookCard(book) {
        // Open access system - always show as available
        const statusColor = 'bg-green-500 text-white';
        const categoryKey = (book.category || '').toLowerCase().replace(/\s+/g, '') || 'blue';
        const courseKey = (book.course || '').toLowerCase().replace(/\s+/g, '') || 'general';
        
        // Category-based styling (matching Blade template)
        const categoryColors = {
            'programming': { bg: 'bg-blue-600', bgLight: 'bg-blue-100', text: 'text-blue-600' },
            'mathematics': { bg: 'bg-green-600', bgLight: 'bg-green-100', text: 'text-green-600' },
            'literature': { bg: 'bg-purple-600', bgLight: 'bg-purple-100', text: 'text-purple-600' },
            'science': { bg: 'bg-red-600', bgLight: 'bg-red-100', text: 'text-red-600' },
            'business': { bg: 'bg-amber-600', bgLight: 'bg-amber-100', text: 'text-amber-600' },
            'technology': { bg: 'bg-indigo-600', bgLight: 'bg-indigo-100', text: 'text-indigo-600' },
            'education': { bg: 'bg-pink-600', bgLight: 'bg-pink-100', text: 'text-pink-600' },
            'reference': { bg: 'bg-gray-600', bgLight: 'bg-gray-100', text: 'text-gray-600' }
        };
        
        const categoryIcons = {
            'programming': 'fa-code',
            'mathematics': 'fa-calculator', 
            'literature': 'fa-feather-alt',
            'science': 'fa-flask',
            'business': 'fa-chart-line',
            'technology': 'fa-microchip',
            'education': 'fa-graduation-cap',
            'reference': 'fa-bookmark'
        };
        
        const colors = categoryColors[categoryKey] || categoryColors['programming'];
        const icon = categoryIcons[categoryKey] || 'fa-book';
        
        const coverUrl = this.getCoverUrl(book.cover_photo);
        const cover = coverUrl ? `<img src="${coverUrl}" alt="${this.escapeHtml(book.title)}" class="w-full h-full object-cover book-cover-img" onload="this.style.opacity='1'; this.nextElementSibling.style.display='none';" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';" style="opacity: 0; transition: opacity 0.3s ease-in-out;" loading="lazy">` : '';
        
        // Open access system - always allow borrowing
        const action = `<button class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-4 rounded-lg transition-all text-sm shadow-md hover:shadow-lg transform hover:-translate-y-0.5 btn-borrow" data-book-id="${book.id}"><i class="fas fa-book-reader mr-1"></i>Borrow</button>`;

        return `
        <div class="group relative bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 book-card overflow-hidden" data-book-id="${book.id}">
            <div class="relative aspect-[3/4] bg-gradient-to-br from-gray-100 to-gray-200 rounded-t-xl overflow-hidden shadow-lg border border-gray-300">
                ${cover}
                <div class="absolute inset-0 bg-white default-book-cover" style="display: ${coverUrl ? 'none' : 'block'};">
                    <div class="h-8 ${colors.bg} relative">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                    </div>
                    <div class="p-2 h-full flex flex-col justify-between">
                        <div class="text-center">
                            <h3 class="text-xs font-bold text-gray-900 leading-tight mb-0.5 line-clamp-2">
                                ${this.escapeHtml(this.limitText(book.title, 30))}
                            </h3>
                            <p class="text-xs text-gray-600 font-medium line-clamp-1">
                                ${this.escapeHtml(this.limitText(book.author, 20))}
                            </p>
                        </div>
                        <div class="flex-1 flex items-center justify-center my-1">
                            <div class="w-8 h-8 ${colors.bgLight} rounded-full flex items-center justify-center">
                                <i class="fas ${icon} text-xs ${colors.text}"></i>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold line-clamp-1">
                                ${this.escapeHtml(book.course || 'General')}
                            </div>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 h-4 ${colors.bg}">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                    </div>
                </div>
                <div class="absolute left-0 top-0 w-0.5 h-full bg-gradient-to-r from-black/20 to-transparent"></div>
                <div class="absolute top-0 left-0.5 w-px h-full bg-white/40"></div>
                <div class="absolute right-0 top-0.5 bottom-0.5 w-0.5 bg-gray-300 rounded-r"></div>
            </div>
            <div class="p-4 bg-white">
                <div class="mb-3"><span class="inline-block bg-blue-50 text-blue-700 text-xs px-3 py-1 rounded-full font-medium">${this.escapeHtml(book.category || 'General')}</span></div>
                <p class="text-gray-600 text-sm mb-4 line-clamp-2 min-h-[2.5rem]">${this.escapeHtml(book.description || 'No description available.')}</p>
                <div class="flex gap-2">
                    <a href="/student/books/${book.id}" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-4 rounded-lg transition-all text-sm shadow-md">View</a>
                    ${action}
                </div>
            </div>
        </div>`;
    }

    limitText(text, n) { return text && text.length > n ? text.slice(0, n) + '...' : (text || ''); }
    
    renderBookDetails(book) {
        const bookDetails = document.getElementById('book-details');
        if (!bookDetails) return;

        const coverUrl = this.getCoverUrl(book.cover_photo);
        const program = book.course || book.program || 'General';
        const year = book.published_year || '---';
        const course = book.course || 'All';
        const pages = book.pages || '---';
        const hasBorrowed = !!book.user_has_borrowed;
        const canRead = hasBorrowed && !!book.borrow_record;

        const descriptionHtml = book.description
            ? this.escapeHtml(book.description).replace(/\n/g, '<br>')
            : 'No description available.';

        bookDetails.innerHTML = `
            <div class="flex flex-col lg:flex-row gap-4 sm:gap-6 lg:gap-8 items-start">
                <!-- Book Cover -->
                <div class="w-full sm:w-auto flex justify-center sm:block sm:flex-shrink-0">
                    <div class="w-40 sm:w-48 md:w-56 lg:w-64 aspect-[3/4] rounded-xl shadow-lg sm:shadow-2xl relative overflow-hidden border border-gray-300 mx-auto sm:mx-0 bg-gradient-to-br from-gray-100 to-gray-200">
                        ${coverUrl ? `
                            <img 
                                src="${coverUrl}" 
                                alt="${this.escapeHtml(book.title || 'Book cover')}" 
                                class="w-full h-full object-cover"
                                onerror="this.style.display='none'; this.nextElementSibling && (this.nextElementSibling.style.display='flex');"
                            >
                            <div class="absolute inset-0 hidden items-center justify-center bg-white">
                                <div class="text-gray-400 text-center p-4 flex flex-col items-center justify-center">
                                    <i class="fas fa-book text-3xl mb-2"></i>
                                    <p class="text-xs">No cover</p>
                                </div>
                            </div>
                        ` : `
                            <div class="absolute inset-0 flex items-center justify-center bg-white">
                                <div class="text-gray-400 text-center p-4 flex flex-col items-center justify-center">
                                    <i class="fas fa-book text-3xl mb-2"></i>
                                    <p class="text-xs">No cover</p>
                                </div>
                            </div>
                        `}
                    </div>
                </div>
                
                <!-- Book Information -->
                <div class="flex-1 space-y-6">
                    <!-- Title and Basic Info -->
                    <div>
                        <h1 class="text-2xl sm:text-3xl lg:text-4xl xl:text-5xl font-bold text-gray-900 mb-2">
                            ${this.escapeHtml(book.title || 'Untitled')}
                        </h1>
                        <p class="text-lg lg:text-xl text-gray-600 mb-4">
                            by ${this.escapeHtml(book.author || 'Unknown Author')}
                        </p>
                        <div class="flex items-center gap-4 flex-wrap">
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-500 text-white">
                                Open Access
                            </span>
                            <span class="px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-full">
                                ${this.escapeHtml(program)}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Quick Info Grid -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <div class="text-base lg:text-lg font-bold text-gray-900">${this.escapeHtml(String(year))}</div>
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Year</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <div class="text-base lg:text-lg font-bold text-gray-900">${this.escapeHtml(String(course))}</div>
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Course</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <div class="text-base lg:text-lg font-bold text-gray-900">${this.escapeHtml(String(level))}</div>
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Level</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <div class="text-base lg:text-lg font-bold text-gray-900">${this.escapeHtml(String(pages))}</div>
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Pages</div>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Description</h2>
                        <p class="text-gray-700 leading-relaxed text-lg">
                            ${descriptionHtml}
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons Section -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="flex flex-col sm:flex-row justify-center lg:justify-end gap-4">
                    ${canRead ? `
                        <a href="/student/books/${book.id}/read" class="inline-block px-6 lg:px-8 py-3 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-lg transition-colors text-center">
                            <i class="fas fa-book-open mr-2"></i>Read Book
                        </a>
                    ` : ''}
                    
                    <button 
                        class="btn-borrow px-6 lg:px-8 py-3 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-lg transition-colors text-center"
                        data-book-id="${book.id}"
                    >
                        <i class="fas fa-book-reader mr-2"></i>${hasBorrowed ? 'Borrow Again' : 'Borrow Book'}
                    </button>
                </div>
            </div>
        `;
    }
    escapeHtml(text) { const d=document.createElement('div'); d.textContent=text||''; return d.innerHTML; }
    getCoverUrl(path) {
        if (!path) return '';
        let p = String(path);
        if (/^https?:\/\//i.test(p) || p.startsWith('/')) return p;
        // normalize leading ./ or missing slash
        p = p.replace(/^\.+\/?/, '');
        return '/' + p;
    }

    showLoading(show) { this.elements.loadingIndicator?.classList.toggle('hidden', !show); }
    showNoResults(show) { this.elements.noResults?.classList.toggle('hidden', !show); }

    updateResultsCount() {
        const visible = Math.min(this.state.booksPerPage, Math.max(0, this.state.filteredBooks.length - (this.state.currentPage - 1) * this.state.booksPerPage));
        const total = this.state.filteredBooks.length;
        const vc = this.elements.resultsCount?.querySelector('#visible-count');
        const tc = this.elements.resultsCount?.querySelector('#total-count');
        if (vc) vc.textContent = visible;
        if (tc) tc.textContent = total;
    }

    updatePagination() {
        const el = this.elements.pagination; if (!el) return;
        const totalPages = Math.ceil(this.state.filteredBooks.length / this.state.booksPerPage);
        
        if (totalPages <= 1) { 
            el.innerHTML = ''; 
            return; 
        }
        
        // Create pagination container with modern Tailwind styling
        let html = '<div class="flex items-center justify-center space-x-1">';
        
        // Previous button
        const prevDisabled = this.state.currentPage <= 1;
        html += `<button class="inline-flex items-center justify-center w-10 h-10 text-sm font-medium transition-all duration-200 rounded-lg border focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-blue-500 ${
            prevDisabled 
                ? 'text-gray-400 bg-white border-gray-200 cursor-not-allowed' 
                : 'text-gray-700 bg-white border-gray-300 hover:bg-gray-50 hover:text-gray-900 hover:border-gray-400'
        } pagination-btn" data-page="${this.state.currentPage - 1}" ${prevDisabled ? 'disabled' : ''}>
            <i class="fas fa-chevron-left"></i>
        </button>`;
        
        // Page numbers with ellipsis logic
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= this.state.currentPage - 2 && i <= this.state.currentPage + 2)) {
                const isActive = i === this.state.currentPage;
                html += `<button class="inline-flex items-center justify-center min-w-[2.5rem] h-10 px-3 text-sm font-medium transition-all duration-200 rounded-lg border focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-blue-500 ${
                    isActive 
                        ? 'text-white bg-blue-600 border-blue-600 hover:bg-blue-700 hover:border-blue-700 shadow-sm' 
                        : 'text-gray-700 bg-white border-gray-300 hover:bg-gray-50 hover:text-gray-900 hover:border-gray-400'
                } pagination-btn ${isActive ? 'active' : ''}" data-page="${i}">
                    ${i}
                </button>`;
            } else if (i === this.state.currentPage - 3 || i === this.state.currentPage + 3) {
                html += '<span class="inline-flex items-center justify-center min-w-[2.5rem] h-10 px-3 text-sm font-medium text-gray-400">...</span>';
            }
        }
        
        // Next button
        const nextDisabled = this.state.currentPage >= totalPages;
        html += `<button class="inline-flex items-center justify-center w-10 h-10 text-sm font-medium transition-all duration-200 rounded-lg border focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-blue-500 ${
            nextDisabled 
                ? 'text-gray-400 bg-white border-gray-200 cursor-not-allowed' 
                : 'text-gray-700 bg-white border-gray-300 hover:bg-gray-50 hover:text-gray-900 hover:border-gray-400'
        } pagination-btn" data-page="${this.state.currentPage + 1}" ${nextDisabled ? 'disabled' : ''}>
            <i class="fas fa-chevron-right"></i>
        </button>`;
        
        html += '</div>';
        
        el.innerHTML = html;
    }

    resetSearch() { this.clearNewFilters(); }

    loadPage(page) { this.state.currentPage = page; this.updateBookDisplay(); }

    extractBooksFromDOM() {
        // Only extract from cards that don't have the 'all-books-card' class
        // This allows the All Books section to keep its original Blade template
        const cards = document.querySelectorAll('.book-card:not(.all-books-card)');
        this.state.allBooks = [];
        cards.forEach(card => {
            const id = card.dataset.bookId;
            
            // Extract data from the actual Blade template structure
            const titleEl = card.querySelector('h3') || card.querySelector('.text-xs.font-bold');
            const title = titleEl?.textContent?.trim();
            
            const authorEl = card.querySelector('.text-gray-600.font-medium') || card.querySelector('p');
            const author = authorEl?.textContent?.trim();
            
            const categoryEl = card.querySelector('.bg-blue-50') || card.querySelector('.inline-block');
            const category = categoryEl?.textContent?.trim() || 'General';
            
            const descriptionEl = card.querySelector('.text-gray-600.text-xs') || card.querySelector('.line-clamp-1');
            const description = descriptionEl?.textContent?.trim() || '';
            
            // Extract cover photo from data attribute or img element
            const imgEl = card.querySelector('.book-cover-img');
            const coverPhoto = imgEl?.src || card.dataset.coverPhoto || '';
            
            // Extract course and other data
            const courseEl = card.querySelector('.text-gray-500.uppercase');
            const course = courseEl?.textContent?.trim() || 'General';
            
            if (id && title) {
                this.state.allBooks.push({ 
                    id, 
                    title, 
                    author, 
                    category, 
                    description,
                    course,
                    cover_photo: coverPhoto,
                    availability_status: 'available'
                });
            }
        });
        this.state.filteredBooks = [...this.state.allBooks];
        
        console.log('Extracted books from DOM (excluding All Books section):', this.state.allBooks.length);
    }

    loadBooks() { this.performSearch(); }
    
    async loadBookContent(bookId) {
        try {
            // Get book details
            const bookResponse = await fetch(`/student/books/${bookId}/details`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const book = await bookResponse.json();
            
            // Update reader title
            document.getElementById('book-reader-title').textContent = `${book.title} by ${book.author}`;
            
            // For demo purposes, show a placeholder content
            const readerContent = document.getElementById('book-reader-content');
            readerContent.innerHTML = `
                <div class="max-w-4xl mx-auto p-8 bg-white shadow-lg rounded-lg m-6">
                    <div class="text-center mb-8">
                        <h1 class="text-4xl font-bold text-gray-900 mb-4">${this.escapeHtml(book.title)}</h1>
                        <p class="text-xl text-gray-600">by ${this.escapeHtml(book.author)}</p>
                        <div class="mt-4 text-sm text-gray-500">
                            <p>Published: ${book.published_year || 'Unknown'} | ISBN: ${book.isbn || 'N/A'}</p>
                        </div>
                    </div>
                    
                    <div class="prose prose-lg max-w-none">
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                            <p class="text-blue-800"><strong>Book Reader Demo</strong></p>
                            <p class="text-blue-700 mt-2">This is a demonstration of the book reader interface. In a production environment, this would display the actual book content, PDF viewer, or e-book reader.</p>
                        </div>
                        
                        ${book.description ? `
                            <h2>Description</h2>
                            <p>${this.escapeHtml(book.description)}</p>
                        ` : ''}
                        
                        <h2>Sample Content</h2>
                        <p>This book contains valuable information about ${this.escapeHtml(book.category || 'the subject matter')}. The content has been carefully curated to provide students with comprehensive knowledge and practical insights.</p>
                        
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
                        
                        <p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
                        
                        <h3>Key Learning Objectives</h3>
                        <ul>
                            <li>Understand the fundamental concepts</li>
                            <li>Apply theoretical knowledge to practical scenarios</li>
                            <li>Develop critical thinking skills</li>
                            <li>Master the essential techniques</li>
                        </ul>
                        
                        <p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.</p>
                    </div>
                </div>
            `;
            
        } catch (error) {
            
            const readerContent = document.getElementById('book-reader-content');
            readerContent.innerHTML = `
                <div class="text-center text-red-600">
                    <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                    <h3 class="text-xl font-semibold mb-2">Failed to Load Book</h3>
                    <p>Unable to load the book content. Please try again.</p>
                    <button id="retry-load" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Try Again
                    </button>
                </div>
            `;
            
            document.getElementById('retry-load')?.addEventListener('click', () => {
                this.loadBookContent(bookId);
            });
        }
    }
    
    setupReaderEventListeners(bookId) {
        // Close reader
        document.getElementById('close-reader')?.addEventListener('click', () => {
            const readerModal = document.getElementById('book-reader-modal');
            if (readerModal) {
                readerModal.remove();
            }
        });
        
        // Download from reader
        document.getElementById('reader-download')?.addEventListener('click', () => {
            this.downloadBook(bookId);
        });
        
        // Fullscreen toggle
        document.getElementById('reader-fullscreen')?.addEventListener('click', () => {
            const readerModal = document.getElementById('book-reader-modal');
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else {
                readerModal.requestFullscreen();
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (document.getElementById('book-reader-modal')) {
                if (e.key === 'Escape') {
                    document.getElementById('close-reader')?.click();
                } else if (e.key === 'F11') {
                    e.preventDefault();
                    document.getElementById('reader-fullscreen')?.click();
                }
            }
        });
    }
}

// Wait for DOM to be fully loaded
function initializeApp() {
    // Initialize the BooksManager
    const booksManager = new BooksManager();
    
    // Make it available globally if needed
    window.booksManager = booksManager;
    
    // Initialize toast function
    window.showToast = function(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white ${
            type === 'success' ? 'bg-green-500' : 
            type === 'error' ? 'bg-red-500' : 
            type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
        }`;
        toast.textContent = message;
        document.body.appendChild(toast);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            toast.remove();
        }, 5000);
    };
}

// Check if DOM is already loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApp);
} else {
    // DOM already loaded
    initializeApp();
}

// Borrow button functionality with toast
(function () {
    if (window.__booksBorrowBound) return; window.__booksBorrowBound = true;
    function showToast(message, type = 'info') {
        const n = document.createElement('div');
        n.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg border transition-all duration-300 ${type === 'success' ? 'bg-white text-blue-600 border-blue-200' : type === 'error' ? 'bg-red-500 text-white border-red-500' : 'bg-blue-600 text-white border-blue-600'}`;
        n.innerHTML = `<div class="flex items-center gap-2"><i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i><span>${message}</span></div>`;
        document.body.appendChild(n);
        setTimeout(() => { n.style.opacity = '0'; n.style.transform = 'translateX(100%)'; setTimeout(() => n.remove(), 300); }, 3000);
    }

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-borrow');
        if (!btn) return;
        
        e.preventDefault();
        const bookId = btn.dataset.bookId;
        if (!bookId) return;

        const isAvailable = btn.dataset.available === 'true';
        if (!isAvailable) {
            showToast('This book is not available for borrowing', 'error');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        try {
            const response = await fetch(`/student/books/${bookId}/borrow`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ duration: 1 })
            });

            const data = await response.json();

            if (response.ok) {
                showToast(data.message || 'Book borrowed successfully! Auto-returns in 1 day.', 'success');
                // Refresh the page after a short delay to show the success message
                setTimeout(() => { window.location.href = `/student/books/${bookId}`; }, 1200);
            } else {
                showToast(data.message || 'Failed to borrow book', 'error');
                btn.disabled = false;
            }
        } catch(err) {
            showToast('An error occurred while borrowing the book', 'error');
            btn.disabled = false;
        }
    });
})();
