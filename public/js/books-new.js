/**
 * Clean Books Management System
 * Simple search functionality for books
 */
class BooksManager {
    constructor() {
        this.state = {
            searchTerm: '',
            allBooks: [],
            filteredBooks: [],
            filters: {
                category: '',
                program: '',
                year: '',
                sort: 'title-asc'
            },
            currentPage: 1,
            booksPerPage: 18,
            totalPages: 1
        };
        this.borrowedBookIds = new Set(((window.booksPageRoutes && window.booksPageRoutes.borrowedBookIds) || []).map(id => Number(id)));

        // Wait for DOM to be ready before initializing
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    init() {
        console.log('Initializing BooksManager...');
        
        // Get all DOM elements
        this.elements = {
            booksContainer: document.getElementById('books-grid'),
            searchInput: document.getElementById('search-books'),
            resultsCount: document.getElementById('results-count'),
            filterDropdownBtn: document.getElementById('filter-dropdown-btn'),
            filterDropdown: document.getElementById('filter-dropdown'),
            filterCategory: document.getElementById('filter-category'),
            filterProgram: document.getElementById('filter-program'),
            filterYear: document.getElementById('filter-year'),
            filterSort: document.getElementById('filter-sort'),
            applyFiltersBtn: document.getElementById('apply-filters'),
            resetFiltersBtn: document.getElementById('reset-filters'),
            activeFilters: document.getElementById('active-filters')
        };

        console.log('DOM Elements found:', {
            searchInput: !!this.elements.searchInput,
            booksContainer: !!this.elements.booksContainer,
            resultsCount: !!this.elements.resultsCount
        });

        // Preserve search query passed from dashboard (?search=...)
        this.initSearchFromUrl();
        this.setupEventListeners();
        this.loadBooks();
    }

    getRoute(name, fallback = '') {
        return (window.booksPageRoutes && window.booksPageRoutes[name]) || fallback;
    }

    getBookUrl(bookId) {
        const base = this.getRoute('booksBase', '/student/books');
        return `${base.replace(/\/$/, '')}/${bookId}`;
    }

    getBorrowUrl(bookId) {
        const base = this.getRoute('borrowBase', '/student/books');
        return `${base.replace(/\/$/, '')}/${bookId}/borrow`;
    }

    isBorrowed(bookId) {
        return this.borrowedBookIds.has(Number(bookId));
    }

    initSearchFromUrl() {
        try {
            const params = new URLSearchParams(window.location.search);
            const searchFromUrl = (params.get('search') || '').trim();
            if (!searchFromUrl) return;

            this.state.searchTerm = searchFromUrl;
            if (this.elements.searchInput) {
                this.elements.searchInput.value = searchFromUrl;
            }
        } catch (e) {
            // Ignore URL parsing issues and keep default empty search
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

        if (this.elements.filterDropdownBtn && this.elements.filterDropdown) {
            this.elements.filterDropdownBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.elements.filterDropdown.classList.toggle('hidden');
            });

            this.elements.filterDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
            });

            document.addEventListener('click', () => {
                this.elements.filterDropdown.classList.add('hidden');
            });
        }

        if (this.elements.applyFiltersBtn) {
            this.elements.applyFiltersBtn.addEventListener('click', () => {
                this.applyFilters();
            });
        }

        if (this.elements.resetFiltersBtn) {
            this.elements.resetFiltersBtn.addEventListener('click', () => {
                this.resetFilters();
            });
        }
        
        // Borrow button click event (using event delegation)
        if (this.elements.booksContainer) {
            this.elements.booksContainer.addEventListener('click', (e) => {
                if (e.target.closest('.btn-borrow')) {
                    e.preventDefault();
                    const borrowButton = e.target.closest('.btn-borrow');
                    const bookId = borrowButton.dataset.bookId;
                    console.log('Borrow button clicked');
                    console.log('Button element:', borrowButton);
                    console.log('Book ID from dataset:', bookId);
                    console.log('Button data attributes:', borrowButton.dataset);
                    
                    if (!bookId) {
                        console.error('No book ID found on button');
                        this.showBorrowError('Book ID not found');
                        return;
                    }
                    
                    this.showBorrowPopup(bookId);
                }
            });
        }

        document.addEventListener('click', (e) => {
            const quickBorrowButton = e.target.closest('.btn-borrow-quick');
            if (!quickBorrowButton) return;

            e.preventDefault();
            const bookId = quickBorrowButton.dataset.bookId;
            const bookTitle = quickBorrowButton.dataset.bookTitle || quickBorrowButton.closest('.book-card')?.dataset.bookTitle || 'Book Title';

            if (!bookId) {
                this.showBorrowError('Book ID not found');
                return;
            }

            this.showBorrowPopup(bookId, bookTitle);
        });
        
        // Borrow popup events
        const borrowPopup = document.getElementById('borrowPopup');
        const borrowPopupCancel = document.getElementById('borrowPopupCancel');
        const borrowPopupConfirm = document.getElementById('borrowPopupConfirm');
        
        if (borrowPopupCancel) {
            borrowPopupCancel.addEventListener('click', () => {
                this.hideBorrowPopup();
            });
        }
        
        if (borrowPopupConfirm) {
            borrowPopupConfirm.addEventListener('click', () => {
                this.confirmBorrow();
            });
        }
        
        // Close popup when clicking backdrop
        if (borrowPopup) {
            borrowPopup.addEventListener('click', (e) => {
                if (e.target === borrowPopup) {
                    this.hideBorrowPopup();
                }
            });
        }
        
        // Close popup when pressing Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !borrowPopup.classList.contains('hidden')) {
                this.hideBorrowPopup();
            }
        });
        
        // View All recommended books button
        const showAllRecommendedBtn = document.getElementById('show-all-recommended');
        if (showAllRecommendedBtn) {
            showAllRecommendedBtn.addEventListener('click', () => {
                window.location.href = `${this.getRoute('booksIndex', '/student/books')}?scope=recommended`;
            });
        } else {
            console.warn('View All recommended button not found');
        }
    }

    async loadBooks() {
        try {
            console.log('Loading books...');
            
            // Extract books from DOM that were rendered by Blade template
            this.extractBooksFromDOM();
            console.log('Books loaded from Blade template:', this.state.allBooks.length);
            
            // Initialize with all books visible
            this.state.filteredBooks = [...this.state.allBooks];
            
            // Show the main books grid by default with pagination
            const booksGrid = document.getElementById('books-grid');
            if (booksGrid) {
                booksGrid.style.display = 'grid';
            }
            
            // Hide course sections by default
            this.hideCourseSections();
            
            // Update the count
            this.updateResultsCount();
            
            // Apply initial search if present in URL, otherwise render default view
            if (this.state.searchTerm) {
                this.filterBooks();
            } else {
                // Render books with pagination (this will handle clearing duplicates)
                this.renderBooks();
            }
            
        } catch (error) {
            console.error('Error loading books:', error);
        }
    }

    extractBooksFromDOM() {
        // Only extract books once to avoid duplicates
        if (this.state.allBooks.length > 0) {
            console.log('Books already extracted, skipping...');
            return;
        }
        
        // Extract only from original Blade template, not from dynamically created elements
        const bookElements = document.querySelectorAll('.book-card');
        console.log('Found original book elements:', bookElements.length);
        
        // Use a Map to ensure unique books by ID
        const uniqueBooks = new Map();
        
        Array.from(bookElements).forEach(bookEl => {
            const bookId = bookEl.dataset.bookId;
            if (bookId && !uniqueBooks.has(bookId)) {
                const book = {
                    id: bookId,
                    title: bookEl.dataset.bookTitle?.trim() || bookEl.querySelector('h3')?.textContent?.trim() || 'Untitled',
                    author: bookEl.dataset.bookAuthor?.trim()
                        || bookEl.querySelector('[data-book-author-text]')?.textContent?.trim()
                        || bookEl.querySelector('p.text-xs.text-gray-600')?.textContent?.trim()
                        || 'Unknown Author',
                    category: bookEl.dataset.bookCategory?.trim() || '',
                    resourceType: bookEl.dataset.bookResourceType?.trim() || 'book',
                    course: bookEl.dataset.bookCourse?.trim() || '',
                    program: bookEl.dataset.bookProgram?.trim() || '',
                    yearLevel: bookEl.dataset.bookYearLevel?.trim() || '',
                    publishedYear: bookEl.dataset.bookPublishedYear?.trim() || '',
                    description: bookEl.dataset.bookDescription?.trim() || '',
                    available: true,
                    cover: bookEl.dataset.bookCover?.trim() || bookEl.querySelector('.book-cover-img')?.src || ''
                };
                uniqueBooks.set(bookId, book);
            }
        });
        
        // Convert Map to array
        this.state.allBooks = Array.from(uniqueBooks.values());
        console.log('Extracted unique books:', this.state.allBooks.length);
        this.state.filteredBooks = [...this.state.allBooks];
    }

    filterBooks() {
        console.log('Filtering books...');
        console.log('Search term:', this.state.searchTerm);
        console.log('Total books available:', this.state.allBooks.length);
        
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
            console.log('After search filter:', results.length);
        }

        if (this.state.filters.category) {
            const categoryNeedle = this.normalizeFilterValue(this.state.filters.category);
            results = results.filter(book => this.normalizeFilterValue(book.resourceType).includes(categoryNeedle));
        }

        if (this.state.filters.program) {
            const programNeedle = this.normalizeFilterValue(this.state.filters.program);
            results = results.filter(book => this.normalizeFilterValue(book.course).includes(programNeedle));
        }

        if (this.state.filters.year) {
            const yearNeedle = this.normalizeFilterValue(this.state.filters.year);
            results = results.filter(book => this.normalizeFilterValue(book.yearLevel).includes(yearNeedle));
        }

        results = this.sortBooks(results);
        
        // Update state
        this.state.filteredBooks = results;
        
        const hasActiveFilters = this.hasActiveFilters();

        // If there's a search term or active filters, hide course sections and show main grid
        if (this.state.searchTerm || hasActiveFilters) {
            console.log('Search active - hiding course sections, showing main grid');
            this.hideCourseSections();
            this.toggleRecommendedSection(false);
            
            // Show the main books grid
            const booksGrid = document.getElementById('books-grid');
            if (booksGrid) {
                booksGrid.style.display = 'grid';
            }
            
            // Update section title for search results
            const allBooksTitle = document.querySelector('#all-books-section h2');
            if (allBooksTitle) {
                allBooksTitle.textContent = `Search Results (${results.length})`;
            }

            // Put All Books search results at the very top of the arrangement
            this.rearrangeSectionsForSearch(true);
        } else {
            // No search term - hide all course specific sections and show the main grid
            console.log('No search term - hiding course sections, showing main grid');
            this.hideCourseSections();
            this.toggleRecommendedSection(true);
            
            // Show the main books grid
            const booksGrid = document.getElementById('books-grid');
            if (booksGrid) {
                booksGrid.style.display = 'grid';
            }
            
            // Reset section title
            const allBooksTitle = document.querySelector('#all-books-section h2');
            if (allBooksTitle) {
                allBooksTitle.textContent = 'All Books';
            }

            // Restore original section arrangement
            this.rearrangeSectionsForSearch(false);
        }
        
        // Render results
        this.renderBooks();
        this.updateResultsCount();
        this.renderActiveFilters();
    }

    applyFilters() {
        this.state.filters.category = this.elements.filterCategory?.value?.trim() || '';
        this.state.filters.program = this.elements.filterProgram?.value?.trim() || '';
        this.state.filters.year = this.elements.filterYear?.value?.trim() || '';
        this.state.filters.sort = this.elements.filterSort?.value?.trim() || 'title-asc';
        this.state.currentPage = 1;
        if (this.elements.filterDropdown) {
            this.elements.filterDropdown.classList.add('hidden');
        }
        this.filterBooks();
    }

    resetFilters() {
        this.state.filters = {
            category: '',
            program: '',
            year: '',
            sort: 'title-asc'
        };
        this.state.currentPage = 1;

        if (this.elements.filterCategory) this.elements.filterCategory.value = '';
        if (this.elements.filterProgram) this.elements.filterProgram.value = '';
        if (this.elements.filterYear) this.elements.filterYear.value = '';
        if (this.elements.filterSort) this.elements.filterSort.value = 'title-asc';

        this.filterBooks();
    }

    hasActiveFilters() {
        return Object.entries(this.state.filters).some(([key, value]) => key !== 'sort' && Boolean(value));
    }

    normalizeFilterValue(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '');
    }

    sortBooks(results) {
        const sortBy = this.state.filters.sort || 'title-asc';
        const items = [...results];

        items.sort((a, b) => {
            switch (sortBy) {
                case 'title-desc':
                    return (b.title || '').localeCompare(a.title || '');
                case 'author-asc':
                    return (a.author || '').localeCompare(b.author || '');
                case 'author-desc':
                    return (b.author || '').localeCompare(a.author || '');
                case 'year-newest':
                    return Number(b.publishedYear || 0) - Number(a.publishedYear || 0);
                case 'year-oldest':
                    return Number(a.publishedYear || 0) - Number(b.publishedYear || 0);
                case 'recently-added':
                    return Number(b.id || 0) - Number(a.id || 0);
                case 'title-asc':
                default:
                    return (a.title || '').localeCompare(b.title || '');
            }
        });

        return items;
    }

    renderActiveFilters() {
        if (!this.elements.activeFilters) return;

        const tags = [];

        if (this.state.searchTerm) {
            tags.push(`Search: ${this.escapeHtml(this.state.searchTerm)}`);
        }

        if (this.state.filters.category) {
            tags.push(`Type: ${this.elements.filterCategory?.selectedOptions?.[0]?.text || this.state.filters.category}`);
        }

        if (this.state.filters.program) {
            tags.push(`Program: ${this.elements.filterProgram?.selectedOptions?.[0]?.text || this.state.filters.program}`);
        }

        if (this.state.filters.year) {
            tags.push(`Year: ${this.elements.filterYear?.selectedOptions?.[0]?.text || this.state.filters.year}`);
        }

        this.elements.activeFilters.innerHTML = tags.map(tag => `
            <span class="inline-flex items-center px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-medium">
                ${tag}
            </span>
        `).join('');
    }

    toggleRecommendedSection(show) {
        const recommendedSection = document.getElementById('recommended-section');
        if (!recommendedSection) return;
        recommendedSection.style.display = show ? '' : 'none';
    }

    renderBooks() {
        if (!this.elements.booksContainer) return;
        
        const { filteredBooks, currentPage, booksPerPage } = this.state;
        
        // Calculate pagination
        this.state.totalPages = Math.ceil(filteredBooks.length / booksPerPage);
        const startIndex = (currentPage - 1) * booksPerPage;
        const endIndex = startIndex + booksPerPage;
        const booksToShow = filteredBooks.slice(startIndex, endIndex);
        
        console.log(`Rendering books: page ${currentPage} of ${this.state.totalPages}, showing ${booksToShow.length} books`);
        
        // If no search term and on page 1, show paginated view by default
        if (!this.state.searchTerm && currentPage === 1) {
            console.log('No search term - showing paginated view by default');
            
            // Clear the container first to avoid duplicates
            this.elements.booksContainer.innerHTML = '';
            
            // Always show pagination for All Books section
            this.elements.booksContainer.innerHTML = booksToShow.map(b => this.createBookCard(b)).join('');
            this.renderPaginationControls();
            return;
        }
        
        // Show "No books found" when searching yields no results
        if (filteredBooks.length === 0) {
            this.elements.booksContainer.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <div class="text-gray-300 text-6xl mb-4"><i class="fas fa-search"></i></div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No books found</h3>
                    <p class="text-gray-500 mb-6">Try adjusting your search terms.</p>
                    <button onclick="window.booksManager.clearSearch()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-redo mr-2"></i>Reset Search
                    </button>
                </div>
            `;
            return;
        }
        
        // Clear the container first to avoid duplicates
        this.elements.booksContainer.innerHTML = '';
        
        // Regenerate cards when searching or paginating
        console.log('Searching/Paginating - regenerating book cards');
        this.elements.booksContainer.innerHTML = booksToShow.map(b => this.createBookCard(b)).join('');
        
        // Add pagination controls
        this.renderPaginationControls();
    }
    
    renderPaginationControls() {
        const { currentPage, totalPages, filteredBooks } = this.state;
        
        if (totalPages <= 1) return; // Don't show pagination if only one page
        
        const paginationHtml = `
            <div class="col-span-full mt-6 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Showing ${((currentPage - 1) * this.state.booksPerPage) + 1} to ${Math.min(currentPage * this.state.booksPerPage, filteredBooks.length)} of ${filteredBooks.length} books
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="window.booksManager.goToPage(${currentPage - 1})" 
                            class="px-3 py-1 text-sm border rounded ${currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'}"
                            ${currentPage === 1 ? 'disabled' : ''}>
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                    
                    <div class="flex items-center space-x-1">
                        ${this.generatePageNumbers()}
                    </div>
                    
                    <button onclick="window.booksManager.goToPage(${currentPage + 1})" 
                            class="px-3 py-1 text-sm border rounded ${currentPage === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'}"
                            ${currentPage === totalPages ? 'disabled' : ''}>
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        `;
        
        // Append pagination to the container
        this.elements.booksContainer.insertAdjacentHTML('beforeend', paginationHtml);
    }
    
    generatePageNumbers() {
        const { currentPage, totalPages } = this.state;
        let pages = [];
        
        // Show first page
        if (currentPage > 3) {
            pages.push(`<button onclick="window.booksManager.goToPage(1)" class="px-3 py-1 text-sm border rounded bg-white text-gray-700 hover:bg-gray-50">1</button>`);
            if (currentPage > 4) {
                pages.push('<span class="px-2 text-gray-500">...</span>');
            }
        }
        
        // Show pages around current page
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === currentPage;
            pages.push(`
                <button onclick="window.booksManager.goToPage(${i})" 
                        class="px-3 py-1 text-sm border rounded ${isActive ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'}">
                    ${i}
                </button>
            `);
        }
        
        // Show last page
        if (currentPage < totalPages - 2) {
            if (currentPage < totalPages - 3) {
                pages.push('<span class="px-2 text-gray-500">...</span>');
            }
            pages.push(`<button onclick="window.booksManager.goToPage(${totalPages})" class="px-3 py-1 text-sm border rounded bg-white text-gray-700 hover:bg-gray-50">${totalPages}</button>`);
        }
        
        return pages.join('');
    }
    
    goToPage(page) {
        const { totalPages } = this.state;
        
        if (page < 1 || page > totalPages) return;
        
        this.state.currentPage = page;
        console.log(`Going to page ${page}`);
        
        // Scroll to top of books grid
        const booksGrid = document.getElementById('books-grid');
        if (booksGrid) {
            booksGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        // Re-render books with new page
        this.renderBooks();
        this.updateResultsCount();
    }
    
    clearSearch() {
        this.state.searchTerm = '';
        this.state.currentPage = 1;
        if (this.elements.searchInput) {
            this.elements.searchInput.value = '';
        }
        this.filterBooks();
    }

    updateResultsCount() {
        if (!this.elements.resultsCount) {
            console.log('Results count element not found, skipping update');
            return;
        }
        
        // Count actual book elements in the DOM
        const bookElements = document.querySelectorAll('.book-card');
        const visibleCount = bookElements.length;
        
        // Use total books count
        const totalCount = this.state.allBooks.length;
        
        console.log(`Updating results count: ${visibleCount} visible, ${totalCount} total`);
        
        // Update the display
        const visibleElement = this.elements.resultsCount.querySelector('#visible-count');
        if (visibleElement) {
            visibleElement.textContent = visibleCount;
        } else {
            console.log('Visible count element not found inside results count');
        }
    }

    createBookCard(book) {
        const coverUrl = book.cover || '';
        const resourceType = this.escapeHtml(String(book.resourceType || 'book').replace(/_/g, ' ')).toUpperCase();
        const courseLabel = this.escapeHtml(book.course || book.program || '');
        const cover = coverUrl
            ? `<img src="${coverUrl}" alt="${this.escapeHtml(book.title)}" class="w-full h-full object-cover rounded-lg book-cover-img" loading="lazy">`
            : `<div class="w-full h-full bg-gray-200 flex items-center justify-center">
                    <i class="fas fa-book text-gray-400 text-4xl"></i>
               </div>`;

        const actionButtons = this.isBorrowed(book.id)
            ? `<a href="${this.getBookUrl(book.id)}" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-2 rounded text-xs text-center transition-colors">View</a>`
            : `
                    <a href="${this.getBookUrl(book.id)}" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-2 rounded text-xs text-center transition-colors">View</a>
                    <button class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-2 rounded text-xs transition-colors btn-borrow" data-book-id="${book.id}">Borrow</button>
              `;

        return `
        <div class="book-card flex-shrink-0 w-full" data-book-id="${book.id}">
            <div class="relative group">
                <div class="book-cover relative bg-gray-100 rounded-lg shadow-md overflow-hidden h-56 sm:h-60 md:h-64 hover:shadow-xl transition-all duration-300 transform group-hover:scale-105">
                    <span class="absolute top-2 left-2 z-10 px-2 py-1 rounded-full bg-white/90 text-[10px] font-semibold text-gray-700 uppercase tracking-wide">${resourceType}</span>
                    ${cover}
                </div>
                <div class="mt-3 text-center">
                    <h3 class="text-sm font-semibold text-gray-900 line-clamp-2">
                        ${this.escapeHtml(book.title || 'Untitled')}
                    </h3>
                    <p class="text-xs text-gray-600 mb-2 line-clamp-1" title="${this.escapeHtml(book.author || 'Unknown Author')}">
                        ${this.escapeHtml(book.author || 'Unknown Author')}
                    </p>
                    <p class="text-[11px] text-gray-500 mb-2 uppercase tracking-wide line-clamp-1">
                        ${courseLabel}
                    </p>
                    <div class="flex gap-1">
                        ${actionButtons}
                    </div>
                </div>
            </div>
        </div>`;
    }

    limitText(text, n) { 
        return text && text.length > n ? text.slice(0, n) + '...' : (text || ''); 
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Borrow popup methods
    showBorrowPopup(bookId, fallbackTitle = 'Book Title') {
        console.log('showBorrowPopup called with bookId:', bookId);
        console.log('typeof bookId:', typeof bookId);
        
        const book = this.state.allBooks.find(b => b.id == bookId); // Use == for string comparison
        console.log('Found book:', book);
        
        const popup = document.getElementById('borrowPopup');
        const popupCard = document.getElementById('borrowPopupCard');
        const titleElement = document.getElementById('borrowPopupTitle');
        
        if (!popup || !popupCard || !titleElement) return;
        
        // Set book title
        titleElement.textContent = book?.title || fallbackTitle;
        
        // Store current book ID for confirmation
        this.currentBorrowBookId = bookId;
        console.log('Set currentBorrowBookId to:', this.currentBorrowBookId);
        
        // Show popup with animation
        popup.classList.remove('hidden');
        setTimeout(() => {
            popupCard.classList.remove('scale-95', 'opacity-0');
            popupCard.classList.add('scale-100', 'opacity-100');
        }, 10);
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }
    
    hideBorrowPopup() {
        const popup = document.getElementById('borrowPopup');
        const popupCard = document.getElementById('borrowPopupCard');
        
        if (!popup || !popupCard) return;
        
        // Hide popup with animation
        popupCard.classList.remove('scale-100', 'opacity-100');
        popupCard.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            popup.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }, 200);
        
        // Clear current book ID
        this.currentBorrowBookId = null;
    }
    
    async confirmBorrow() {
        if (!this.currentBorrowBookId) return;
        
        // Store the book ID before hiding popup (since hideBorrowPopup clears it)
        const bookId = this.currentBorrowBookId;
        
        const confirmButton = document.getElementById('borrowPopupConfirm');
        const confirmText = document.getElementById('borrowPopupConfirmText');
        
        // Show loading state
        if (confirmButton) {
            confirmButton.disabled = true;
            confirmText.textContent = 'Borrowing...';
        }
        
        try {
            console.log('Attempting to borrow book:', bookId);
            
            const url = this.getBorrowUrl(bookId);
            console.log('Request URL:', url);
            console.log('Request method: POST');
            
            // Use the exact same approach as student-dashboard.js
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    duration: 1 // Fixed 1-day duration
                })
            });

            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            console.log('Response URL:', response.url);

            const data = await response.json();
            console.log('Response data:', data);

            if (response.ok && data.success) {
                this.borrowedBookIds.add(Number(bookId));
                // Show success message
                this.showBorrowSuccess();
                // Hide popup AFTER success
                this.hideBorrowPopup();
                this.renderBooks();
                this.updateResultsCount();
                
                // Redirect to book details page after a delay (same as student dashboard)
                setTimeout(() => {
                    window.location.href = this.getBookUrl(bookId);
                }, 1500);
            } else {
                // Show error message
                this.showBorrowError(data.message || 'Failed to borrow book');
            }
            
        } catch (error) {
            console.error('Error borrowing book:', error);
            console.error('Error details:', error.stack);
            this.showBorrowError('An error occurred while borrowing the book');
        } finally {
            // Reset button state
            if (confirmButton) {
                confirmButton.disabled = false;
                confirmText.textContent = 'Confirm Borrow';
            }
        }
    }
    
    filterRecommendedBooks() {
        console.log('Filtering to show only recommended books...');
        
        // Get recommended books from the DOM (they're in the recommended-books section)
        const recommendedBooksContainer = document.getElementById('recommended-books');
        if (!recommendedBooksContainer) {
            console.error('Recommended books container not found');
            return;
        }
        
        // Extract recommended book IDs
        const recommendedBookElements = recommendedBooksContainer.querySelectorAll('.book-card');
        const recommendedBookIds = Array.from(recommendedBookElements).map(el => el.dataset.bookId);
        
        console.log('Recommended book IDs:', recommendedBookIds);
        
        // Hide all course-specific sections
        this.hideCourseSections();
        
        // Show the main books grid container
        const booksGrid = document.getElementById('books-grid');
        if (booksGrid) {
            booksGrid.style.display = 'grid';
        }
        
        // Filter all books to show only recommended ones
        this.state.filteredBooks = this.state.allBooks.filter(book => 
            recommendedBookIds.includes(book.id.toString())
        );
        
        console.log('Filtered recommended books:', this.state.filteredBooks.length);
        
        // Update the books grid
        this.renderBooks();
        
        // Update the results count
        this.updateResultsCount();
        
        // Update the section title to indicate it's showing recommended books
        const allBooksTitle = document.querySelector('#all-books-section h2');
        if (allBooksTitle) {
            allBooksTitle.textContent = 'Recommended Books';
        }
        
        // Show a clear filter indicator
        this.showFilterIndicator('Recommended Books');
    }
    
    hideCourseSections() {
        // Hide all course-specific sections
        const courseSections = [
            'bsit-books-section',
            'bse-books-section', 
            'bsed-books-section',
            'bshm-books-section',
            'bsn-books-section'
        ];
        
        courseSections.forEach(sectionId => {
            const section = document.getElementById(sectionId);
            if (section) {
                section.style.display = 'none';
                console.log(`Hidden section: ${sectionId}`);
            } else {
                console.log(`Section not found: ${sectionId}`);
            }
        });
        
        // Always hide BSN section specifically
        this.alwaysHideBSNSection();
    }
    
    alwaysHideBSNSection() {
        // Always hide BSN section no matter what
        const bsnSection = document.getElementById('bsn-books-section');
        if (bsnSection) {
            bsnSection.style.display = 'none';
            console.log('BSN section always hidden');
        }
    }
    
    showCourseSections() {
        // Show all course-specific book sections EXCEPT BSN
        const courseSections = [
            'bsit-books-section',
            'bse-books-section', 
            'bsed-books-section',
            'bshm-books-section'
            // BSN section intentionally excluded
        ];
        
        courseSections.forEach(sectionId => {
            const section = document.getElementById(sectionId);
            if (section) {
                section.style.display = 'block';
                console.log(`Shown section: ${sectionId}`);
            }
        });
        
        // Always hide BSN section
        this.alwaysHideBSNSection();
    }
    
    showFilterIndicator(filterName) {
        // Create or update filter indicator
        let indicator = document.getElementById('filter-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'filter-indicator';
            indicator.className = 'mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-center justify-between';
            
            // Insert before the books grid
            const booksContainer = document.getElementById('books-grid');
            if (booksContainer && booksContainer.parentNode) {
                booksContainer.parentNode.insertBefore(indicator, booksContainer);
            }
        }
        
        indicator.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-filter text-blue-600 mr-2"></i>
                <span class="text-blue-800 font-medium">Showing: ${filterName}</span>
            </div>
            <button onclick="window.booksManager.clearFilter()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Clear Filter
            </button>
        `;
    }
    
    clearFilter() {
        console.log('Clearing filter...');
        
        // Hide the main books grid container
        const booksGrid = document.getElementById('books-grid');
        if (booksGrid) {
            booksGrid.style.display = 'none';
        }
        
        // Show the user's specific course section
        this.showUserCourseSection();
        
        // Reset to show all books
        this.state.filteredBooks = [...this.state.allBooks];
        this.state.currentPage = 1;
        this.renderBooks();
        this.updateResultsCount();
        
        // Reset the section title
        const allBooksTitle = document.querySelector('#all-books-section h2');
        if (allBooksTitle) {
            allBooksTitle.textContent = 'All Books';
        }
        
        // Remove filter indicator
        const indicator = document.getElementById('filter-indicator');
        if (indicator) {
            indicator.remove();
        }

        // Restore original section arrangement
        this.rearrangeSectionsForSearch(false);
    }

    rearrangeSectionsForSearch(isSearchActive) {
        const allBooksSection = document.getElementById('all-books-section');
        const ejournalSection = document.getElementById('ejournal-section');
        const thesisSection = document.getElementById('thesis-section');
        const recommendedSection = document.getElementById('recommended-section');
        const mainContent = document.querySelector('.main-content');
        
        if (!allBooksSection || !mainContent) return;

        if (isSearchActive) {
            // Search active: Only show All Books/Search Results section and hide other static sections
            console.log('Search active: showing search results only, hiding static sections');
            
            if (ejournalSection) ejournalSection.style.display = 'none';
            if (thesisSection) thesisSection.style.display = 'none';
            
            // Put #all-books-section immediately below the filter controls (which is children[1])
            // children[0] is the page title wrapper, children[1] is the filter controls wrapper.
            // So we insert it before children[2].
            const targetBefore = mainContent.children[2];
            if (targetBefore && targetBefore !== allBooksSection) {
                mainContent.insertBefore(allBooksSection, targetBefore);
            } else if (!targetBefore) {
                mainContent.appendChild(allBooksSection);
            }
        } else {
            // No search: restore original arrangement: 1. Recommended, 2. E-Journals, 3. Theses, 4. All Books at the very bottom
            console.log('Restoring original section arrangement and displaying static sections');
            
            if (ejournalSection) ejournalSection.style.display = '';
            if (thesisSection) thesisSection.style.display = '';
            
            // Put E-journals before Theses
            if (ejournalSection && thesisSection) {
                mainContent.insertBefore(ejournalSection, thesisSection);
            }
            // Put All Books at the very bottom
            mainContent.appendChild(allBooksSection);
        }
    }
    
    showAllBooksWithPagination() {
        console.log('Showing all books with pagination...');
        
        // Hide all course sections
        this.hideCourseSections();
        
        // Show the main books grid
        const booksGrid = document.getElementById('books-grid');
        if (booksGrid) {
            booksGrid.style.display = 'grid';
        }
        
        // Reset to show all books
        this.state.filteredBooks = [...this.state.allBooks];
        this.state.currentPage = 1;
        this.state.searchTerm = ''; // Clear search
        
        // Update the section title
        const allBooksTitle = document.querySelector('#all-books-section h2');
        if (allBooksTitle) {
            allBooksTitle.textContent = 'All Books';
        }
        
        // Render books with pagination
        this.renderBooks();
        this.updateResultsCount();
        
        // Show filter indicator
        this.showFilterIndicator('All Books');
    }
    
    showUserCourseSection() {
        // Get user's course from the page (we need to add this to the page)
        const userCourse = this.getUserCourse();
        console.log('User course:', userCourse);
        
        // Hide all course sections first
        this.hideCourseSections();
        
        // Show the appropriate section based on user's course, but NEVER show BSN
        const courseSectionMap = {
            'BSIT': 'bsit-books-section',
            'BSE': 'bse-books-section', 
            'BSED': 'bsed-books-section',
            'BSHM': 'bshm-books-section'
            // BSN intentionally excluded - never show BSN section
        };
        
        const sectionId = courseSectionMap[userCourse];
        if (sectionId) {
            const section = document.getElementById(sectionId);
            if (section) {
                section.style.display = 'block';
                console.log(`Showing section: ${sectionId}`);
            }
        } else {
            console.log('No valid course section found for user course:', userCourse);
        }
        
        // Always hide BSN section as the final step
        this.alwaysHideBSNSection();
    }
    
    getUserCourse() {
        // Try to get user course from multiple possible sources
        // 1. From meta tag (if we add one)
        const userCourseMeta = document.querySelector('meta[name="user-course"]');
        if (userCourseMeta) {
            return userCourseMeta.getAttribute('content');
        }
        
        // 2. From the recommended section description
        const recommendedDesc = document.querySelector('#recommended-section p.text-sm');
        if (recommendedDesc) {
            const text = recommendedDesc.textContent;
            // Extract course from "Books for BSIT 3rd Year students"
            const match = text.match(/Books for (\w+) /);
            if (match) {
                return match[1];
            }
        }
        
        // 3. From page title or other elements
        const pageTitle = document.querySelector('h1, h2');
        if (pageTitle && pageTitle.textContent.includes('BSIT')) return 'BSIT';
        if (pageTitle && pageTitle.textContent.includes('BSE')) return 'BSE';
        if (pageTitle && pageTitle.textContent.includes('BSED')) return 'BSED';
        if (pageTitle && pageTitle.textContent.includes('BSHM')) return 'BSHM';
        if (pageTitle && pageTitle.textContent.includes('BSN')) return 'BSN';
        
        // Default fallback
        return 'BSIT'; // Default to BSIT if we can't determine
    }
    
    showBorrowSuccess() {
        // Create success notification
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-[10000] flex items-center space-x-3 transform translate-x-full transition-transform duration-300';
        notification.innerHTML = `
            <i class="fas fa-check-circle text-xl"></i>
            <div>
                <p class="font-semibold">Book Borrowed Successfully!</p>
                <p class="text-sm opacity-90">You can now read your borrowed book.</p>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 10);
        
        // Hide notification after 3 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
    
    showBorrowError(message) {
        // Create error notification
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg z-[10000] flex items-center space-x-3 transform translate-x-full transition-transform duration-300';
        notification.innerHTML = `
            <i class="fas fa-exclamation-circle text-xl"></i>
            <div>
                <p class="font-semibold">Borrow Failed</p>
                <p class="text-sm opacity-90">${message}</p>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 10);
        
        // Hide notification after 3 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
}

// Initialize the BooksManager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.booksManager = new BooksManager();
});
