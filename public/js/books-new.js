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
                console.log('View All recommended button clicked');
                
                // Filter to show only recommended books
                this.filterRecommendedBooks();
                
                // Scroll to the books grid
                const booksContainer = document.getElementById('books-grid');
                if (booksContainer) {
                    console.log('Found books container, scrolling...');
                    booksContainer.scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                } else {
                    console.warn('Books container not found');
                    // Fallback: scroll to All Books section
                    const allBooksSection = document.getElementById('all-books-section');
                    if (allBooksSection) {
                        allBooksSection.scrollIntoView({ 
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
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
        const bookElements = document.querySelectorAll('#books-grid > .book-card');
        console.log('Found original book elements:', bookElements.length);
        
        // Use a Map to ensure unique books by ID
        const uniqueBooks = new Map();
        
        Array.from(bookElements).forEach(bookEl => {
            const bookId = bookEl.dataset.bookId;
            if (bookId && !uniqueBooks.has(bookId)) {
                const book = {
                    id: bookId,
                    title: bookEl.dataset.bookTitle?.trim() || bookEl.querySelector('h3')?.textContent?.trim() || 'Untitled',
                    author: bookEl.dataset.bookAuthor?.trim() || bookEl.querySelector('.text-gray-600.font-medium')?.textContent?.trim() || 'Unknown Author',
                    category: bookEl.dataset.bookCategory?.trim() || 'General',
                    course: bookEl.dataset.bookCourse?.trim() || 'General',
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
            results = results.filter(book => this.normalizeFilterValue(book.category).includes(categoryNeedle));
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
        } else {
            // No search term - show user's course section and hide main grid
            console.log('No search term - showing user course section');
            this.showUserCourseSection();
            this.toggleRecommendedSection(true);
            
            // Hide the main books grid
            const booksGrid = document.getElementById('books-grid');
            if (booksGrid) {
                booksGrid.style.display = 'none';
            }
            
            // Reset section title
            const allBooksTitle = document.querySelector('#all-books-section h2');
            if (allBooksTitle) {
                allBooksTitle.textContent = 'All Books';
            }
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
            tags.push(`Category: ${this.elements.filterCategory?.selectedOptions?.[0]?.text || this.state.filters.category}`);
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
        
        const categoryKey = (book.category || '').toLowerCase().replace(/\s+/g, '') || 'programming';
        const colors = categoryColors[categoryKey] || categoryColors['programming'];
        const icon = categoryIcons[categoryKey] || 'fa-book';
        
        const coverUrl = book.cover || '';
        const cover = coverUrl ? `<img src="${coverUrl}" alt="${this.escapeHtml(book.title)}" class="w-full h-full object-cover book-cover-img" onload="this.style.opacity='1'; this.nextElementSibling.style.display='none';" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';" style="opacity: 0; transition: opacity 0.3s ease-in-out;" loading="lazy">` : '';

        return `
        <div class="group relative bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 book-card overflow-hidden" data-book-id="${book.id}">
            <div class="relative aspect-[3/4] bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden shadow border border-gray-200">
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
            <div class="p-2 bg-white">
                <p class="text-gray-600 text-xs mb-2 line-clamp-1">
                    ${this.escapeHtml(book.description || 'No description.')}
                </p>
                <div class="flex gap-1">
                    <a href="/student/books/${book.id}" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-1.5 px-2 rounded text-xs text-center transition-all duration-200 shadow hover:shadow-md">View</a>
                    <button class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-1.5 px-2 rounded text-xs transition-all duration-200 shadow hover:shadow-md btn-borrow" data-book-id="${book.id}">Borrow</button>
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
    showBorrowPopup(bookId) {
        console.log('showBorrowPopup called with bookId:', bookId);
        console.log('typeof bookId:', typeof bookId);
        
        const book = this.state.allBooks.find(b => b.id == bookId); // Use == for string comparison
        console.log('Found book:', book);
        
        if (!book) {
            console.error('Book not found in allBooks array');
            console.error('Available books:', this.state.allBooks.map(b => ({ id: b.id, title: b.title })));
            this.showBorrowError('Book not found');
            return;
        }
        
        const popup = document.getElementById('borrowPopup');
        const popupCard = document.getElementById('borrowPopupCard');
        const titleElement = document.getElementById('borrowPopupTitle');
        
        if (!popup || !popupCard || !titleElement) return;
        
        // Set book title
        titleElement.textContent = book.title;
        
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
            
            const url = `/student/books/${bookId}/borrow`;
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
                    duration: 5 // Fixed 5-day duration
                })
            });

            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            console.log('Response URL:', response.url);

            const data = await response.json();
            console.log('Response data:', data);

            if (response.ok && data.success) {
                // Show success message
                this.showBorrowSuccess();
                // Hide popup AFTER success
                this.hideBorrowPopup();
                
                // Redirect to book details page after a delay (same as student dashboard)
                setTimeout(() => {
                    window.location.href = `/student/books/${bookId}`;
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
