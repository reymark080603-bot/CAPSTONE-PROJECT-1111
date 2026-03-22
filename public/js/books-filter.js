if (typeof BooksFilterManager === 'undefined') {
    window.BooksFilterManager = class BooksFilterManager {
    constructor() {
        this.state = {
            searchTerm: '',
            category: '',
            program: '',
            year: '',
            sort: 'title-asc',
            allBooks: [],
            filteredBooks: []
        };
        
        this.elements = {
            searchInput: document.getElementById('search-books'),
            categoryFilter: document.getElementById('filter-category'),
            programFilter: document.getElementById('filter-program'),
            yearFilter: document.getElementById('filter-year'),
            sortFilter: document.getElementById('filter-sort'),
            resetButton: document.getElementById('reset-filters'),
            applyButton: document.getElementById('apply-filters'),
            filterDropdownBtn: document.getElementById('filter-dropdown-btn'),
            filterDropdown: document.getElementById('filter-dropdown'),
            activeFiltersContainer: document.getElementById('active-filters'),
            booksContainer: document.getElementById('books-grid'),
            resultsCount: document.getElementById('visible-count')
        };
        
        // Debug: Check if filter dropdown elements are found
        console.log('Filter dropdown btn:', this.elements.filterDropdownBtn);
        console.log('Filter dropdown:', this.elements.filterDropdown);
        console.log('Search input:', this.elements.searchInput);
        console.log('Category filter:', this.elements.categoryFilter);
        console.log('Apply button:', this.elements.applyButton);
        console.log('Books container:', this.elements.booksContainer);
        
        this.init();
    }
    
    init() {
        this.extractBooksFromDOM();
        this.setupEventListeners();
        this.clearSearchOnRefresh(); // Clear search on page refresh
        
        // Run aggressive duplicate removal multiple times
        this.removeExactDuplicatesFromDOM();
        setTimeout(() => this.removeDuplicatesImmediately(), 100);
        setTimeout(() => this.removeDuplicatesImmediately(), 500);
        
        this.applyFilters();
    }
    
    clearSearchOnRefresh() {
        // Clear search inputs when page is refreshed
        if (this.elements.searchInput) {
            this.elements.searchInput.value = '';
        }
        
        // Clear header search if it exists
        const headerSearchInput = document.getElementById('header-search');
        if (headerSearchInput) {
            headerSearchInput.value = '';
        }
        
        // Reset search state
        this.state.searchTerm = '';
        
        // Remove duplicates from DOM on page load
        this.removeExactDuplicatesFromDOM();
        console.log('Duplicate removal enabled for books blade');
    }
    
    removeExactDuplicatesFromDOM() {
        const bookCards = document.querySelectorAll('.book-card');
        const seenBooks = new Set();
        let removedCount = 0;
        
        console.log('Checking DOM for duplicates in', bookCards.length, 'book cards...');
        
        bookCards.forEach((card, index) => {
            const titleElement = card.querySelector('h3');
            const authorElement = card.querySelector('.text-gray-600.font-medium');
            
            if (titleElement && authorElement) {
                const title = titleElement.textContent.trim().toLowerCase();
                const author = authorElement.textContent.trim().toLowerCase();
                
                // Extract base title by removing edition numbers, years, and special characters
                const baseTitle = title
                    .replace(/\d+(st|nd|rd|th)\s+edition/gi, '') // Remove "10th edition"
                    .replace(/\d+/g, '') // Remove all numbers
                    .replace(/\s+/g, ' ') // Normalize spaces
                    .replace(/[^\w\s]/g, '') // Remove special characters except spaces
                    .trim();
                
                const similarMatch = `${baseTitle}|${author}`;
                
                console.log(`DOM Card ${index}: "${title}" -> Base: "${baseTitle}" | Author: "${author}" -> Key: "${similarMatch}"`);
                
                if (seenBooks.has(similarMatch)) {
                    console.log(`REMOVING (duplicate from DOM): ${title} by ${author}`);
                    card.remove();
                    removedCount++;
                } else {
                    seenBooks.add(similarMatch);
                    console.log(`KEEPING (first in DOM): ${title} by ${author}`);
                }
            }
        });
        
        if (removedCount > 0) {
            console.log(`Removed ${removedCount} duplicates from DOM. Kept ${seenBooks.size} unique books (first occurrences).`);
        } else {
            console.log(`No duplicates found in DOM. All ${seenBooks.size} books are unique.`);
        }
    }
    
    removeDuplicatesImmediately() {
        console.log('🔥 RUNNING SUPER AGGRESSIVE DUPLICATE REMOVAL...');
        
        // Find ALL possible book cards using multiple selectors
        const bookCards = [
            ...document.querySelectorAll('.book-card'),
            ...document.querySelectorAll('[data-book-id]'),
            ...document.querySelectorAll('.group.relative.bg-white')
        ];
        
        // Remove duplicates from the array and get unique elements
        const uniqueCards = [...new Set(bookCards)];
        console.log(`📚 Found ${uniqueCards.length} total book cards to check`);
        
        const seenTitles = new Set(); // Track titles only, ignore author
        let removedCount = 0;
        
        uniqueCards.forEach((card, index) => {
            // Try multiple selectors to find title and author
            const titleElement = card.querySelector('h3') || 
                                card.querySelector('[class*="title"]') || 
                                card.querySelector('.text-xs.font-bold');
                                
            const authorElement = card.querySelector('.text-gray-600.font-medium') || 
                                 card.querySelector('[class*="author"]') ||
                                 card.querySelector('.text-xs.text-gray-600');
            
            if (titleElement && authorElement) {
                const title = titleElement.textContent.trim().toLowerCase();
                const author = authorElement.textContent.trim().toLowerCase();
                
                // Extract base title by removing edition numbers, years, and special characters
                const baseTitle = title
                    .replace(/\d+(st|nd|rd|th)\s+edition/gi, '') // Remove "10th edition"
                    .replace(/\d+/g, '') // Remove all numbers
                    .replace(/\s+/g, ' ') // Normalize spaces
                    .replace(/[^\w\s]/g, '') // Remove special characters except spaces
                    .trim();
                
                console.log(`📖 Book ${index}: "${title}" by "${author}" -> Base: "${baseTitle}"`);
                
                // Remove if we've seen this title before (regardless of author)
                if (seenTitles.has(baseTitle)) {
                    console.log(`❌ REMOVING DUPLICATE TITLE: ${title} by ${author}`);
                    card.remove();
                    removedCount++;
                } else {
                    seenTitles.add(baseTitle);
                    console.log(`✅ KEEPING FIRST: ${title} by ${author}`);
                }
            } else {
                console.log(`⚠️ Book ${index} missing elements - Title: ${!!titleElement}, Author: ${!!authorElement}`);
            }
        });
        
        console.log(`🎯 DEDUPLICATION COMPLETE: Removed ${removedCount} duplicates, kept ${seenTitles.size} unique titles`);
        
        // Run final verification
        setTimeout(() => {
            const finalCards = document.querySelectorAll('.book-card');
            console.log(`🔍 FINAL CHECK: ${finalCards.length} cards remaining`);
        }, 100);
    }
    
    extractBooksFromDOM() {
        console.log('Extracting books from DOM...');
        const bookCards = document.querySelectorAll('.book-card');
        console.log('Found', bookCards.length, 'book cards in DOM');
        
        const booksMap = new Map(); // Use Map to store unique books by ID
        const titleAuthorMap = new Map(); // Additional check for title+author combinations
        
        Array.from(bookCards).forEach((card, index) => {
            const titleElement = card.querySelector('h3');
            const authorElement = card.querySelector('.text-gray-600.font-medium');
            const categoryElement = card.querySelector('.text-xs.text-gray-500.uppercase');
            const bookId = card.dataset.bookId;
            
            console.log(`Processing card ${index}:`, {
                bookId: bookId,
                title: titleElement ? titleElement.textContent.trim() : 'NO TITLE',
                author: authorElement ? authorElement.textContent.trim() : 'NO AUTHOR',
                category: categoryElement ? categoryElement.textContent.trim() : 'NO CATEGORY'
            });
            
            const title = titleElement ? titleElement.textContent.trim() : '';
            const author = authorElement ? authorElement.textContent.trim() : '';
            const titleAuthorKey = `${title.toLowerCase()}-${author.toLowerCase()}`;
            
            // Only add book if we haven't seen this ID or title+author combination before
            if (!booksMap.has(bookId) && !titleAuthorMap.has(titleAuthorKey)) {
                // Debug: Log what we find
                console.log('BooksManager initialized');
                
                console.log('Title element:', titleElement, 'Text:', titleElement?.textContent?.trim());
                console.log('Author element:', authorElement, 'Text:', authorElement?.textContent?.trim());
                console.log('Category element:', categoryElement, 'Text:', categoryElement?.textContent?.trim());
                console.log('Book ID:', bookId);
                
                const bookData = {
                    element: card,
                    id: bookId,
                    title: title,
                    author: author,
                    category: categoryElement ? categoryElement.textContent.trim() : '',
                    program: this.extractProgram(card),
                    year: this.extractYear(card),
                    recentlyAdded: this.isRecentlyAdded(card)
                };
                
                booksMap.set(bookId, bookData);
                titleAuthorMap.set(titleAuthorKey, bookData);
                
                console.log('Added book:', bookData);
            } else {
                card.style.display = 'none';
                console.log('Skipped duplicate book:', title, 'by', author);
            }
        });
        
        this.state.allBooks = Array.from(booksMap.values());
        this.state.filteredBooks = [...this.state.allBooks];
        
        console.log('Extraction complete. All books:', this.state.allBooks.length);
        console.log('Sample books extracted:');
        this.state.allBooks.slice(0, 3).forEach((book, i) => {
            console.log(`  ${i+1}. "${book.title}" by ${book.author} (ID: ${book.id})`);
        });
    }
    
    extractProgram(card) {
        // Try to extract program from course or other data attributes
        const courseElement = card.querySelector('.text-xs.text-gray-500');
        if (courseElement) {
            const courseText = courseElement.textContent.toLowerCase();
            if (courseText.includes('computer') || courseText.includes('cs')) return 'computer-science';
            if (courseText.includes('it') || courseText.includes('information')) return 'information-technology';
            if (courseText.includes('engineering') || courseText.includes('eng')) return 'engineering';
            if (courseText.includes('business') || courseText.includes('ba')) return 'business-administration';
            if (courseText.includes('education') || courseText.includes('edu')) return 'education';
        }
        return 'general';
    }
    
    extractYear(card) {
        // Try to extract academic year from book data
        // This would typically come from data attributes or course information
        const courseElement = card.querySelector('.text-xs.text-gray-500');
        if (courseElement) {
            const courseText = courseElement.textContent.toLowerCase();
            if (courseText.includes('1st') || courseText.includes('first') || courseText.includes('year 1')) return '1st-year';
            if (courseText.includes('2nd') || courseText.includes('second') || courseText.includes('year 2')) return '2nd-year';
            if (courseText.includes('3rd') || courseText.includes('third') || courseText.includes('year 3')) return '3rd-year';
            if (courseText.includes('4th') || courseText.includes('fourth') || courseText.includes('year 4')) return '4th-year';
        }
        return '1st-year'; // Default to 1st year
    }
    
    isRecentlyAdded(card) {
        // Check if book has recently added badge or indicator
        return card.querySelector('.bg-blue-500') !== null;
    }
    
    setupEventListeners() {
        // Search input
        if (this.elements.searchInput) {
            let searchTimeout;
            this.elements.searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => this.handleSearch(), 300);
            });
        }
        
        // Category filter change
        if (this.elements.categoryFilter) {
            this.elements.categoryFilter.addEventListener('change', () => {
                this.applyFilters();
                // Run deduplication after filter change
                setTimeout(() => this.removeDuplicatesImmediately(), 100);
                setTimeout(() => this.removeDuplicatesImmediately(), 300);
            });
        }
        
        // Program filter change
        if (this.elements.programFilter) {
            this.elements.programFilter.addEventListener('change', () => {
                this.applyFilters();
                // Run deduplication after filter change
                setTimeout(() => this.removeDuplicatesImmediately(), 100);
                setTimeout(() => this.removeDuplicatesImmediately(), 300);
            });
        }
        
        // Year filter change
        if (this.elements.yearFilter) {
            this.elements.yearFilter.addEventListener('change', () => {
                this.applyFilters();
                // Run deduplication after filter change
                setTimeout(() => this.removeDuplicatesImmediately(), 100);
                setTimeout(() => this.removeDuplicatesImmediately(), 300);
            });
        }
        
        // Sort filter change
        if (this.elements.sortFilter) {
            this.elements.sortFilter.addEventListener('change', () => {
                this.applyFilters();
                // Run deduplication after filter change
                setTimeout(() => this.removeDuplicatesImmediately(), 100);
                setTimeout(() => this.removeDuplicatesImmediately(), 300);
            });
        }
        
        // Apply Filters button
        if (this.elements.applyButton) {
            this.elements.applyButton.addEventListener('click', () => {
                this.applyFilters();
                // Run deduplication multiple times after apply
                setTimeout(() => this.removeDuplicatesImmediately(), 100);
                setTimeout(() => this.removeDuplicatesImmediately(), 300);
                setTimeout(() => this.removeDuplicatesImmediately(), 500);
            });
        }
        
        // Reset Filters button
        if (this.elements.resetButton) {
            this.elements.resetButton.addEventListener('click', () => {
                this.resetFilters();
                this.closeDropdown();
            });
        }
        
        // Filter dropdown toggle
        if (this.elements.filterDropdownBtn && this.elements.filterDropdown) {
            this.elements.filterDropdownBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                console.log('Filter dropdown clicked'); // Debug log
                this.elements.filterDropdown.classList.toggle('hidden');
            });
        }
        
        // Borrow button functionality (delegated)
        if (this.elements.booksContainer) {
            this.elements.booksContainer.addEventListener('click', (e) => {
                if (e.target.closest('.btn-borrow-quick')) {
                    e.preventDefault();
                    const borrowButton = e.target.closest('.btn-borrow-quick');
                    const bookId = borrowButton.dataset.bookId;
                    const bookTitle = borrowButton.dataset.bookTitle;
                    
                    // Use the existing student dashboard borrow functionality
                    if (window.__studentDashboard && typeof window.__studentDashboard.borrowBookQuick === 'function') {
                        window.__studentDashboard.borrowBookQuick(bookId, bookTitle);
                    }
                }
            });
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (this.elements.filterDropdown && 
                !this.elements.filterDropdown.contains(e.target) && 
                !this.elements.filterDropdownBtn.contains(e.target)) {
                this.elements.filterDropdown.classList.add('hidden');
            }
        });
        
        // Prevent dropdown from closing when clicking inside it
        if (this.elements.filterDropdown) {
            this.elements.filterDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }
    }
    
    handleSearch() {
        const searchTerm = this.elements.searchInput.value.trim();
        this.state.searchTerm = searchTerm;
        
        // If search is cleared, refresh the page
        if (searchTerm === '') {
            console.log('Search cleared, refreshing page...');
            setTimeout(() => {
                window.location.reload();
            }, 300);
            return;
        }
        
        this.applyFilters();
        // Run deduplication immediately and multiple times to catch all duplicates
        this.removeDuplicatesImmediately();
        setTimeout(() => this.removeDuplicatesImmediately(), 50);
        setTimeout(() => this.removeDuplicatesImmediately(), 100);
        setTimeout(() => this.removeDuplicatesImmediately(), 200);
        setTimeout(() => this.removeDuplicatesImmediately(), 500);
        setTimeout(() => this.removeDuplicatesImmediately(), 1000);
    }
    
    showAllRecommendedBooks() {
        console.log('Showing all recommended books...');
        
        // Hide recommended section and show all books with only recommended books
        const recommendedSection = document.getElementById('recommended-section');
        const allBooksSection = document.getElementById('all-books-section');
        const allBooksGrid = document.getElementById('books-grid');
        const sectionTitle = allBooksSection.querySelector('h2');
        
        if (recommendedSection && allBooksSection && allBooksGrid) {
            // Hide recommended section
            recommendedSection.style.display = 'none';
            
            // Show all books section
            allBooksSection.style.display = 'block';
            
            // Update title
            if (sectionTitle) {
                sectionTitle.textContent = 'All Recommended Books';
            }
            
            // Get recommended books
            const recommendedBooksGrid = document.getElementById('recommended-books');
            const recommendedBookCards = recommendedBooksGrid ? 
                recommendedBooksGrid.querySelectorAll('.book-card') : [];
            
            console.log('Found', recommendedBookCards.length, 'recommended book cards');
            
            // Create a set of recommended book identifiers (title + author)
            const recommendedBooksSet = new Set();
            recommendedBookCards.forEach(card => {
                const titleElement = card.querySelector('h3');
                const authorElement = card.querySelector('.text-gray-600.font-medium');
                
                if (titleElement && authorElement) {
                    const title = titleElement.textContent.trim().toLowerCase();
                    const author = authorElement.textContent.trim().toLowerCase();
                    const bookKey = `${title}|${author}`;
                    recommendedBooksSet.add(bookKey);
                    console.log('Recommended book:', title, 'by', author);
                }
            });
            
            console.log('Recommended book set size:', recommendedBooksSet.size);
            
            // Show all books first
            const allBookCards = allBooksGrid.querySelectorAll('.book-card');
            allBookCards.forEach(card => card.style.display = '');
            
            // Hide non-recommended books
            let hiddenCount = 0;
            let shownCount = 0;
            
            allBookCards.forEach(card => {
                const titleElement = card.querySelector('h3');
                const authorElement = card.querySelector('.text-gray-600.font-medium');
                
                if (titleElement && authorElement) {
                    const title = titleElement.textContent.trim().toLowerCase();
                    const author = authorElement.textContent.trim().toLowerCase();
                    const bookKey = `${title}|${author}`;
                    
                    if (!recommendedBooksSet.has(bookKey)) {
                        card.style.display = 'none';
                        hiddenCount++;
                        console.log('Hiding non-recommended:', title, 'by', author);
                    } else {
                        shownCount++;
                        console.log('Showing recommended:', title, 'by', author);
                    }
                } else {
                    // Hide books without proper title/author
                    card.style.display = 'none';
                    hiddenCount++;
                }
            });
            
            console.log(`Show ${shownCount} recommended books, hid ${hiddenCount} non-recommended books`);
            
            // Update results count
            if (this.elements.resultsCount) {
                this.elements.resultsCount.textContent = shownCount;
            }
            
            // Clear search and filters
            this.clearSearchAndFilters();
            
            // Scroll to all books section
            allBooksSection.scrollIntoView({ behavior: 'smooth' });
        }
    }
    
    clearSearchAndFilters() {
        // Clear search input
        if (this.elements.searchInput) {
            this.elements.searchInput.value = '';
        }
        
        // Reset filter states
        this.state.searchTerm = '';
        this.state.category = '';
        this.state.program = '';
        this.state.year = '';
        this.state.sort = 'title-asc';
        
        // Reset filter dropdowns
        if (this.elements.categoryFilter) this.elements.categoryFilter.value = '';
        if (this.elements.programFilter) this.elements.programFilter.value = '';
        if (this.elements.yearFilter) this.elements.yearFilter.value = '';
        if (this.elements.sortFilter) this.elements.sortFilter.value = 'title-asc';
        
        // Clear active filters
        if (this.elements.activeFiltersContainer) {
            this.elements.activeFiltersContainer.innerHTML = '';
        }
    }
    
    toggleDropdown() {
        if (this.elements.filterDropdown.classList.contains('hidden')) {
            this.openDropdown();
        } else {
            this.closeDropdown();
        }
    }
    
    openDropdown() {
        this.elements.filterDropdown.classList.remove('hidden');
        // Add animation
        setTimeout(() => {
            this.elements.filterDropdown.classList.add('opacity-100');
            this.elements.filterDropdown.classList.remove('opacity-0');
        }, 10);
    }
    
    closeDropdown() {
        this.elements.filterDropdown.classList.add('hidden');
        this.elements.filterDropdown.classList.add('opacity-0');
        this.elements.filterDropdown.classList.remove('opacity-100');
    }
    
    updateFilterStates() {
        // Update state from dropdown values
        if (this.elements.categoryFilter) {
            this.state.category = this.elements.categoryFilter.value;
        }
        if (this.elements.programFilter) {
            this.state.program = this.elements.programFilter.value;
        }
        if (this.elements.yearFilter) {
            this.state.year = this.elements.yearFilter.value;
        }
        if (this.elements.sortFilter) {
            this.state.sort = this.elements.sortFilter.value;
        }
    }
    
    applyFilters() {
        console.log('Applying filters with search term:', this.state.searchTerm);
        console.log('Total books available:', this.state.allBooks.length);
        
        let filtered = [...this.state.allBooks];
        
        // Apply search filter
        if (this.state.searchTerm) {
            const searchLower = this.state.searchTerm.toLowerCase();
            console.log('Searching for:', searchLower);
            
            filtered = filtered.filter(book => {
                const titleMatch = book.title.toLowerCase().includes(searchLower);
                const authorMatch = book.author.toLowerCase().includes(searchLower);
                const categoryMatch = book.category.toLowerCase().includes(searchLower);
                
                console.log(`Book: "${book.title}" by ${book.author} - Title: ${titleMatch}, Author: ${authorMatch}, Category: ${categoryMatch}`);
                
                return titleMatch || authorMatch || categoryMatch;
            });
            console.log('Search results:', filtered.length, 'books found');
            
            if (filtered.length === 0) {
                console.log('No search results found. Sample books available:');
                this.state.allBooks.slice(0, 3).forEach((book, i) => {
                    console.log(`  ${i+1}. "${book.title}" by ${book.author}`);
                });
            }
        }
        
        // Apply category filter
        if (this.state.category) {
            filtered = filtered.filter(book => 
                book.category.toLowerCase().includes(this.state.category.toLowerCase())
            );
        }
        
        // Apply program filter
        if (this.state.program) {
            filtered = filtered.filter(book => book.program === this.state.program);
        }
        
        // Apply year filter
        if (this.state.year) {
            filtered = filtered.filter(book => book.year === this.state.year);
        }
        
        // Apply sorting
        filtered = this.sortBooks(filtered, this.state.sort);
        
        // Remove duplicates from filtered results
        const uniqueFiltered = this.removeExactDuplicates(filtered);
        console.log('Final filtered books:', uniqueFiltered.length);
        
        this.state.filteredBooks = uniqueFiltered;
        this.renderBooks();
        this.updateResultsCount();
        this.updateActiveFilters();
        this.toggleRecommendedSection();
    }
    
    removeExactDuplicates(books) {
        const seenBooks = new Set();
        const uniqueBooks = [];
        
        console.log('Checking for duplicates in', books.length, 'books...');
        
        books.forEach((book, index) => {
            // Remove edition numbers and special characters to catch similar books
            const title = book.title.trim().toLowerCase();
            const author = book.author.trim().toLowerCase();
            
            // Extract base title by removing edition numbers, years, and special characters
            const baseTitle = title
                .replace(/\d+(st|nd|rd|th)\s+edition/gi, '') // Remove "10th edition"
                .replace(/\d+/g, '') // Remove all numbers
                .replace(/\s+/g, ' ') // Normalize spaces
                .replace(/[^\w\s]/g, '') // Remove special characters except spaces
                .trim();
            
            const similarMatch = `${baseTitle}|${author}`;
            
            console.log(`Book ${index}: "${title}" -> Base: "${baseTitle}" | Author: "${author}" -> Key: "${similarMatch}"`);
            
            if (!seenBooks.has(similarMatch)) {
                seenBooks.add(similarMatch);
                uniqueBooks.push(book);
                console.log(`KEEPING (first occurrence): ${book.title} by ${book.author}`);
            } else {
                console.log(`REMOVING (duplicate): ${book.title} by ${book.author}`);
            }
        });
        
        if (books.length > uniqueBooks.length) {
            console.log(`Removed ${books.length - uniqueBooks.length} duplicates. Kept ${uniqueBooks.length} unique books (first occurrences).`);
        } else {
            console.log(`No duplicates found. All ${uniqueBooks.length} books are unique.`);
        }
        
        return uniqueBooks;
    }
    
    toggleRecommendedSection() {
        const recommendedSection = document.getElementById('recommended-section');
        const allBooksSection = document.getElementById('all-books-section');
        
        // Hide recommended section when search or any filter (except default sort) is active
        const hasActiveFilters = this.state.searchTerm || 
                               this.state.category || 
                               this.state.program || 
                               this.state.year ||
                               (this.state.sort !== 'title-asc');
        
        if (recommendedSection) {
            if (hasActiveFilters) {
                recommendedSection.style.display = 'none';
            } else {
                recommendedSection.style.display = 'block';
            }
        }
        
        // Always show all books section when filters are active
        if (allBooksSection) {
            if (hasActiveFilters) {
                allBooksSection.style.display = 'block';
                // Update the section title to indicate search results
                const sectionTitle = allBooksSection.querySelector('h2');
                if (sectionTitle) {
                    if (this.state.searchTerm) {
                        sectionTitle.textContent = `Search Results for "${this.state.searchTerm}"`;
                    } else if (this.state.category || this.state.program || this.state.year) {
                        sectionTitle.textContent = 'Filtered Results';
                    } else if (this.state.sort !== 'title-asc') {
                        sectionTitle.textContent = 'Sorted Results';
                    }
                }
            } else {
                // Reset title when no filters
                const sectionTitle = allBooksSection.querySelector('h2');
                if (sectionTitle) {
                    sectionTitle.textContent = 'All Books';
                }
            }
        }
    }
    
    sortBooks(books, sortType) {
        const sorted = [...books];
        
        switch (sortType) {
            case 'title-asc':
                return sorted.sort((a, b) => a.title.localeCompare(b.title));
            case 'title-desc':
                return sorted.sort((a, b) => b.title.localeCompare(a.title));
            case 'author-asc':
                return sorted.sort((a, b) => a.author.localeCompare(b.author));
            case 'author-desc':
                return sorted.sort((a, b) => b.author.localeCompare(a.author));
            case 'year-newest':
                return sorted.sort((a, b) => parseInt(b.year) - parseInt(a.year));
            case 'year-oldest':
                return sorted.sort((a, b) => parseInt(a.year) - parseInt(b.year));
            case 'recently-added':
                return sorted.sort((a, b) => {
                    if (a.recentlyAdded && !b.recentlyAdded) return -1;
                    if (!a.recentlyAdded && b.recentlyAdded) return 1;
                    return 0;
                });
            default:
                return sorted;
        }
    }
    
    renderBooks() {
        if (!this.elements.booksContainer) return;
        
        console.log('Rendering books:', this.state.filteredBooks.length, 'filtered out of', this.state.allBooks.length);
        
        // Hide all books first
        this.state.allBooks.forEach(book => {
            book.element.style.display = 'none';
        });
        
        // Show filtered books
        this.state.filteredBooks.forEach(book => {
            if (book.element && book.element.parentNode) {
                book.element.style.display = '';
            }
        });
        
        // Show no results message if needed
        const noResultsElement = this.elements.booksContainer.querySelector('.no-results-message');
        console.log('No results element found:', !!noResultsElement);
        if (this.state.filteredBooks.length === 0) {
            console.log('Showing no results message');
            if (!noResultsElement) {
                const noResultsDiv = document.createElement('div');
                noResultsDiv.className = 'col-span-full text-center py-12 no-results-message';
                noResultsDiv.innerHTML = `
                    <div class="text-gray-300 text-6xl mb-4">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No books found</h3>
                    <p class="text-gray-500">Try adjusting your search or filters to find what you're looking for.</p>
                `;
                this.elements.booksContainer.appendChild(noResultsDiv);
            }
        } else if (noResultsElement) {
            console.log('Removing no results message');
            noResultsElement.remove();
        }
    }
    
    updateResultsCount() {
        if (this.elements.resultsCount) {
            console.log('Updating results count to:', this.state.filteredBooks.length);
            this.elements.resultsCount.textContent = this.state.filteredBooks.length;
        } else {
            console.log('Results count element not found');
        }
    }
    
    updateActiveFilters() {
        if (!this.elements.activeFiltersContainer) return;
        
        this.elements.activeFiltersContainer.innerHTML = '';
        
        // Add active filter tags
        if (this.state.category) {
            this.addActiveFilterTag('Category', this.state.category, 'category');
        }
        if (this.state.program) {
            this.addActiveFilterTag('Program', this.state.program, 'program');
        }
        if (this.state.year) {
            this.addActiveFilterTag('Year', this.state.year, 'year');
        }
        if (this.state.sort !== 'title-asc') {
            const sortLabel = this.getSortLabel(this.state.sort);
            this.addActiveFilterTag('Sort', sortLabel, 'sort');
        }
    }
    
    addActiveFilterTag(label, value, type) {
        const tag = document.createElement('span');
        tag.className = 'inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium';
        tag.innerHTML = `
            ${label}: ${value}
            <button class="ml-1 text-blue-500 hover:text-blue-700" onclick="window.__booksFilterManager.removeFilter('${type}')">
                <i class="fas fa-times text-xs"></i>
            </button>
        `;
        this.elements.activeFiltersContainer.appendChild(tag);
    }
    
    getSortLabel(sortValue) {
        const labels = {
            'title-asc': 'Title (A-Z)',
            'title-desc': 'Title (Z-A)',
            'author-asc': 'Author (A-Z)',
            'author-desc': 'Author (Z-A)',
            'year-newest': 'Newest',
            'year-oldest': 'Oldest',
            'recently-added': 'Recent'
        };
        return labels[sortValue] || sortValue;
    }
    
    removeFilter(type) {
        switch (type) {
            case 'category':
                this.state.category = '';
                if (this.elements.categoryFilter) this.elements.categoryFilter.value = '';
                break;
            case 'program':
                this.state.program = '';
                if (this.elements.programFilter) this.elements.programFilter.value = '';
                break;
            case 'year':
                this.state.year = '';
                if (this.elements.yearFilter) this.elements.yearFilter.value = '';
                break;
            case 'sort':
                this.state.sort = 'title-asc';
                if (this.elements.sortFilter) this.elements.sortFilter.value = 'title-asc';
                break;
        }
        this.applyFilters();
        // Remove duplicates after removing filter
        setTimeout(() => this.removeDuplicatesImmediately(), 100);
    }
    
    resetFilters() {
        // Reset all filter states
        this.state = {
            ...this.state,
            searchTerm: '',
            category: '',
            program: '',
            year: '',
            sort: 'title-asc'
        };
        
        // Reset UI elements
        if (this.elements.searchInput) this.elements.searchInput.value = '';
        if (this.elements.categoryFilter) this.elements.categoryFilter.value = '';
        if (this.elements.programFilter) this.elements.programFilter.value = '';
        if (this.elements.yearFilter) this.elements.yearFilter.value = '';
        if (this.elements.sortFilter) this.elements.sortFilter.value = 'title-asc';
        
        // Apply filters
        this.applyFilters();
    }
}
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Initialize books filter
    if (document.getElementById('books-grid')) {
        window.__booksFilterManager = new BooksFilterManager();
    }
});
