// Student Dashboard JavaScript
(function() {
    'use strict';

    if (typeof StudentDashboard === 'undefined') {
        class StudentDashboard {
            constructor() {
                this.books = [];
                this.borrowedBooks = new Set();
                this.currentIndex = 0;
                this.booksToShow = 4;
                this.totalBooks = 0;
                this.maxIndex = 0;
                this.recommendedCurrentIndex = 0;
                this.recommendedMaxIndex = 0;
                this.prevBtn = null;
                this.nextBtn = null;
                this.recommendedCarousel = null;
                this.prevBtnRecommended = null;
                this.nextBtnRecommended = null;
                
                this.init();
            }

            getRoute(name, fallback = '') {
                return (window.studentDashboardRoutes && window.studentDashboardRoutes[name]) || fallback;
            }

            getBookUrl(bookId) {
                const base = this.getRoute('booksBase', '/student/books');
                return `${base.replace(/\/$/, '')}/${bookId}`;
            }

            getBorrowUrl(bookId) {
                return `${this.getBookUrl(bookId)}/borrow`;
            }

            init() {
                this.bindEvents();
                this.handleResize();
                this.initProfileMenus();
                this.addAnimations();
                this.initBookCarousel();
                this.initRecommendedBooksCarousel();
                this.loadUserBorrowedBooks().then(() => {
                    this.loadRecommendedBooks();
                    this.loadRecentBooks();
                    this.loadContinueReading();
                });
                this.loadDashboardStats();
                this.initQuickBorrowListeners();
                this.initBorrowPopupEvents();
                this.initHeaderSearch();
            }

            bindEvents() {
                // Bind window resize event
                window.addEventListener('resize', () => this.handleResize());
            }

            initProfileMenus() {
                const profileWrappers = document.querySelectorAll('[data-profile-wrapper]');
                if (!profileWrappers.length) return;

                const closeAllMenus = () => {
                    profileWrappers.forEach((wrapper) => {
                        const menu = wrapper.querySelector('[data-profile-menu]');
                        if (menu) {
                            menu.classList.add('hidden');
                        }
                        wrapper.classList.remove('profile-menu-open');
                    });
                };

                profileWrappers.forEach((wrapper) => {
                    const toggle = wrapper.querySelector('[data-profile-toggle]');
                    const menu = wrapper.querySelector('[data-profile-menu]');
                    if (!toggle || !menu) return;

                    toggle.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const willOpen = menu.classList.contains('hidden');
                        closeAllMenus();
                        if (willOpen) {
                            menu.classList.remove('hidden');
                            wrapper.classList.add('profile-menu-open');
                        }
                    });
                });

                document.addEventListener('click', (e) => {
                    if (!e.target.closest('[data-profile-wrapper]')) {
                        closeAllMenus();
                    }
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        closeAllMenus();
                    }
                });
            }

            handleResize() {
                const isMobile = window.innerWidth < 768;
                this.booksToShow = isMobile ? 2 : 4;
                if (this.totalBooks > 0) {
                    this.maxIndex = Math.max(0, this.totalBooks - this.booksToShow);
                    this.currentIndex = Math.min(this.currentIndex, this.maxIndex);
                    this.updateCarouselButtons();
                }
                this.updateRecommendedCarouselButtons();
            }

            addAnimations() {
                // Add fade-in animations to elements
                const elements = document.querySelectorAll('.fade-in');
                elements.forEach((el, index) => {
                    setTimeout(() => {
                        el.style.opacity = '1';
                        el.style.transform = 'translateY(0)';
                    }, index * 100);
                });
            }

            // Book carousel functionality
            initBookCarousel() {
                this.prevBtn = document.getElementById('prevBtn');
                this.nextBtn = document.getElementById('nextBtn');
                
                if (this.prevBtn && this.nextBtn) {
                    this.prevBtn.addEventListener('click', () => this.previousBooks());
                    this.nextBtn.addEventListener('click', () => this.nextBooks());
                    this.updateCarouselButtons();
                }
            }

            previousBooks() {
                if (this.currentIndex > 0) {
                    this.currentIndex--;
                    this.updateCarouselPosition();
                    this.updateCarouselButtons();
                }
            }

            nextBooks() {
                if (this.currentIndex < this.maxIndex) {
                    this.currentIndex++;
                    this.updateCarouselPosition();
                    this.updateCarouselButtons();
                }
            }

            updateCarouselPosition() {
                const carousel = document.getElementById('bookCarousel');
                if (carousel) {
                    const cardWidth = 192 + 24; // 192px card width + 24px gap
                    const translateX = -this.currentIndex * cardWidth;
                    carousel.style.transform = `translateX(${translateX}px)`;
                }
            }

            updateCarouselButtons() {
                if (this.prevBtn && this.nextBtn && this.maxIndex !== undefined) {
                    this.prevBtn.disabled = this.currentIndex <= 0;
                    this.nextBtn.disabled = this.currentIndex >= this.maxIndex;
                    
                    // Update button styles
                    if (this.currentIndex <= 0) {
                        this.prevBtn.style.opacity = '0.5';
                        this.prevBtn.style.cursor = 'not-allowed';
                    } else {
                        this.prevBtn.style.opacity = '1';
                        this.prevBtn.style.cursor = 'pointer';
                    }
                    
                    if (this.currentIndex >= this.maxIndex) {
                        this.nextBtn.style.opacity = '0.5';
                        this.nextBtn.style.cursor = 'not-allowed';
                    } else {
                        this.nextBtn.style.opacity = '1';
                        this.nextBtn.style.cursor = 'pointer';
                    }
                }
            }

            // Recommended books carousel functionality
            initRecommendedBooksCarousel() {
                this.recommendedCarousel = document.getElementById('recommendedCarousel');
                this.prevBtnRecommended = document.getElementById('prevBtnRecommended');
                this.nextBtnRecommended = document.getElementById('nextBtnRecommended');
                
                if (this.prevBtnRecommended && this.nextBtnRecommended) {
                    this.prevBtnRecommended.addEventListener('click', () => this.previousRecommendedBooks());
                    this.nextBtnRecommended.addEventListener('click', () => this.nextRecommendedBooks());
                    this.updateRecommendedCarouselButtons();
                }

                if (this.recommendedCarousel) {
                    this.recommendedCarousel.addEventListener('scroll', () => this.updateRecommendedCarouselButtons(), { passive: true });
                }
            }

            previousRecommendedBooks() {
                if (this.recommendedCarousel) {
                    this.recommendedCarousel.scrollBy({ left: -220, behavior: 'smooth' });
                }
            }

            nextRecommendedBooks() {
                if (this.recommendedCarousel) {
                    this.recommendedCarousel.scrollBy({ left: 220, behavior: 'smooth' });
                }
            }

            updateRecommendedCarouselPosition() {
                // Deprecated for recommended carousel; keeping method for compatibility.
            }

            updateRecommendedCarouselButtons() {
                if (this.prevBtnRecommended && this.nextBtnRecommended && this.recommendedCarousel) {
                    const maxScrollLeft = this.recommendedCarousel.scrollWidth - this.recommendedCarousel.clientWidth;
                    const canScrollLeft = this.recommendedCarousel.scrollLeft > 0;
                    const canScrollRight = this.recommendedCarousel.scrollLeft < (maxScrollLeft - 1);

                    this.prevBtnRecommended.disabled = !canScrollLeft;
                    this.nextBtnRecommended.disabled = !canScrollRight;
                    
                    // Update button styles
                    if (!canScrollLeft) {
                        this.prevBtnRecommended.style.opacity = '0.5';
                        this.prevBtnRecommended.style.cursor = 'not-allowed';
                    } else {
                        this.prevBtnRecommended.style.opacity = '1';
                        this.prevBtnRecommended.style.cursor = 'pointer';
                    }
                    
                    if (!canScrollRight) {
                        this.nextBtnRecommended.style.opacity = '0.5';
                        this.nextBtnRecommended.style.cursor = 'not-allowed';
                    } else {
                        this.nextBtnRecommended.style.opacity = '1';
                        this.nextBtnRecommended.style.cursor = 'pointer';
                    }
                }
            }

            // Load user's borrowed books
            async loadUserBorrowedBooks() {
                try {
                    const response = await fetch(this.getRoute('loansApi', '/student/loans/api'), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        const loans = Array.isArray(data.loans) ? data.loans : [];
                        this.borrowedBooks.clear();
                        loans.forEach(loan => {
                            if (loan.book && loan.book.id) {
                                this.borrowedBooks.add(loan.book.id);
                            }
                        });
                    }
                } catch (error) {
                    // Silently handle error
                }
            }

            isBookBorrowed(bookId) {
                return this.borrowedBooks.has(bookId);
            }

            showNotification(message, type = 'info') {
                // Create notification element
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full ${
                    type === 'success' ? 'bg-green-500 text-white' :
                    type === 'error' ? 'bg-red-500 text-white' :
                    'bg-blue-500 text-white'
                }`;
                notification.innerHTML = `
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            ${type === 'success' ? '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' :
                          type === 'error' ? '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>' :
                          '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'}
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium">${message}</p>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(notification);
                
                // Animate in
                setTimeout(() => {
                    notification.classList.remove('translate-x-full');
                }, 100);
                
                // Remove after 3 seconds
                setTimeout(() => {
                    notification.classList.add('translate-x-full');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }, 3000);
            }

            // Load recommended books
            renderRecommendedBooks(courseRelatedBooks) {
                const carousel = document.getElementById('recommendedCarousel');
                const noBooks = document.getElementById('no-recommendations');
                const loading = document.getElementById('loading-recommended');
                
                if (loading) loading.classList.add('hidden');
                
                if (!carousel) return;
                
                if (courseRelatedBooks.length === 0) {
                    if (noBooks) noBooks.classList.remove('hidden');
                    if (carousel) carousel.classList.add('hidden');
                    return;
                }
                
                if (noBooks) noBooks.classList.add('hidden');
                if (carousel) carousel.classList.remove('hidden');
                
                const booksHtml = courseRelatedBooks.map(book => {
                    // Check if book is already borrowed
                    const isBorrowed = this.isBookBorrowed(book.id);
                    const bookTitle = this.escapeHtml(book.title || '');
                    const bookAuthor = this.escapeHtml(book.author || '');
                    const resourceType = this.escapeHtml((book.resource_type || 'book').replace(/_/g, ' '));
                    const courseLabel = this.escapeHtml(book.course || book.program || '');
                    
                    // Fix cover photo URL to be absolute
                    const coverUrl = book.cover_photo && !book.cover_photo.startsWith('http') 
                        ? book.cover_photo.startsWith('/') 
                            ? book.cover_photo 
                            : '/' + book.cover_photo
                        : book.cover_photo;
                    
                    return `
                        <div class="book-card flex-shrink-0 w-40 sm:w-44 md:w-48">
                            <div class="relative group">
                                <div class="book-cover relative bg-gray-100 rounded-lg shadow-md overflow-hidden h-56 sm:h-60 md:h-64 hover:shadow-xl transition-all duration-300 transform group-hover:scale-105">
                                    <span class="absolute top-2 left-2 z-10 px-2 py-1 rounded-full bg-white/90 text-[10px] font-semibold text-gray-700 uppercase tracking-wide">${resourceType}</span>
                                    ${coverUrl ? 
                                        `<img src="${coverUrl}" alt="${bookTitle} Cover" class="w-full h-full object-cover rounded-lg">` : 
                                        `<div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-book text-gray-400 text-4xl"></i>
                                        </div>`
                                    }
                                </div>
                                <div class="mt-3 text-center">
                                    <h5 class="font-semibold text-sm text-gray-900 truncate">${bookTitle}</h5>
                                    <p class="text-xs text-gray-600 mb-2">${bookAuthor}</p>
                                    <p class="text-[11px] text-gray-500 mb-2 uppercase tracking-wide">${courseLabel}</p>
                                    <div class="flex gap-1">
                                        <button class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-2 py-2 rounded text-xs font-medium transition-colors" onclick="window.location.href='${this.getBookUrl(book.id)}'">View</button>
                                        ${!isBorrowed ? `<button class="flex-1 bg-green-500 hover:bg-green-600 text-white px-2 py-2 rounded text-xs font-medium transition-colors btn-borrow-quick" data-book-id="${book.id}" data-book-title="${bookTitle}"><i class="fas fa-book-reader mr-1"></i>Borrow</button>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
                
                carousel.innerHTML = booksHtml;
                this.initMobileScrolling(carousel);

                carousel.scrollLeft = 0;
                this.updateRecommendedCarouselButtons();
            }

            async loadRecommendedBooks() {
                // Wait for DOM to be fully loaded
                if (document.readyState !== 'complete') {
                    await new Promise(resolve => {
                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', resolve);
                        } else {
                            resolve();
                        }
                    });
                }
                
                try {
                    const response = await fetch(this.getRoute('recommended', '/dashboard/recommended'), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    
                    if (!response.ok) throw new Error('Failed to load recommended books');
                    
                    const data = await response.json();
                    const recommendedBooks = data.recommended || data.courseRelated || [];
                    
                    this.renderRecommendedBooks(recommendedBooks);
                    
                } catch (error) {
                    console.error('Error loading recommended books:', error);
                    // Show error state
                    const loading = document.querySelector('#loading-recommended');
                    const carousel = document.getElementById('recommendedCarousel');
                    const empty = document.getElementById('no-recommendations');
                    
                    if (loading) loading.classList.add('hidden');
                    if (carousel) carousel.classList.add('hidden');
                    if (empty) empty.classList.remove('hidden');
                }
            }

            
            renderRecentBooks(uniqueBooks, carouselId = 'bookCarousel') {
                const carousel = document.getElementById(carouselId);
                if (!carousel) return;
                
                const booksHtml = uniqueBooks.map(book => {
                    // Check if book is already borrowed
                    const isBorrowed = this.isBookBorrowed(book.id);
                    const bookTitle = this.escapeHtml(book.title || '');
                    const bookAuthor = this.escapeHtml(book.author || '');
                    const resourceType = this.escapeHtml((book.resource_type || 'book').replace(/_/g, ' '));
                    const courseLabel = this.escapeHtml(book.course || book.program || '');
                    
                    // Fix cover photo URL to be absolute
                    const coverUrl = book.cover_photo && !book.cover_photo.startsWith('http') 
                        ? book.cover_photo.startsWith('/') 
                            ? book.cover_photo 
                            : '/' + book.cover_photo
                        : book.cover_photo;
                    
                    return `
                        <div class="book-card flex-shrink-0 w-40 sm:w-44 md:w-48">
                            <div class="relative group">
                                <div class="book-cover relative bg-gray-100 rounded-lg shadow-md overflow-hidden h-56 sm:h-60 md:h-64 hover:shadow-xl transition-all duration-300 transform group-hover:scale-105">
                                    <span class="absolute top-2 left-2 z-10 px-2 py-1 rounded-full bg-white/90 text-[10px] font-semibold text-gray-700 uppercase tracking-wide">${resourceType}</span>
                                    ${coverUrl ? 
                                        `<img src="${coverUrl}" alt="${bookTitle} Cover" class="w-full h-full object-cover rounded-lg">` : 
                                        `<div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-book text-gray-400 text-4xl"></i>
                                        </div>`
                                    }
                                </div>
                                <div class="mt-3 text-center">
                                    <h5 class="font-semibold text-sm text-gray-900 truncate">${bookTitle}</h5>
                                    <p class="text-xs text-gray-600 mb-2">${bookAuthor}</p>
                                    <p class="text-[11px] text-gray-500 mb-2 uppercase tracking-wide">${courseLabel}</p>
                                    <div class="flex gap-1">
                                        <button class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-2 py-2 rounded text-xs font-medium transition-colors" 
                                                onclick="window.location.href='${this.getBookUrl(book.id)}'">
                                            View
                                        </button>
                                        ${!isBorrowed ? `<button class="flex-1 bg-green-500 hover:bg-green-600 text-white px-2 py-2 rounded text-xs font-medium transition-colors btn-borrow-quick" 
                                                data-book-id="${book.id}" data-book-title="${bookTitle}">
                                            <i class="fas fa-book-reader mr-1"></i>Borrow
                                        </button>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
                
                carousel.innerHTML = booksHtml;
                
                // Add padding to ensure last book is fully visible
                if (uniqueBooks.length > 0) {
                    carousel.style.paddingRight = '24px';
                }
                
                // Update carousel settings after books are loaded
                this.totalBooks = uniqueBooks.length;
                this.maxIndex = Math.max(0, this.totalBooks - this.booksToShow);
                this.currentIndex = 0;
                this.updateCarouselButtons();
                
                // Re-initialize carousel buttons to ensure they work
                if (this.prevBtn && this.nextBtn) {
                    // Remove existing listeners to prevent duplicates
                    this.prevBtn.replaceWith(this.prevBtn.cloneNode(true));
                    this.nextBtn.replaceWith(this.nextBtn.cloneNode(true));
                    
                    // Get fresh references
                    this.prevBtn = document.getElementById('prevBtn');
                    this.nextBtn = document.getElementById('nextBtn');
                    
                    // Add new event listeners
                    this.prevBtn.addEventListener('click', () => this.previousBooks());
                    this.nextBtn.addEventListener('click', () => this.nextBooks());
                    
                    // Update button states
                    this.updateCarouselButtons();
                }
            }

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text || '';
                return div.innerHTML;
            }

            // Remove duplicate books based on title and author
            removeDuplicateBooks(books) {
                console.log('removeDuplicateBooks called with:', books.length, 'books');
                const seenBooks = new Set();
                const uniqueBooks = [];
                
                books.forEach((book, index) => {
                    const title = (book.title || '').trim().toLowerCase().replace(/\s+/g, ' ');
                    const author = (book.author || '').trim().toLowerCase().replace(/\s+/g, ' ');
                    const bookKey = `${title}-${author}`;
                    
                    console.log(`Book ${index}:`, {
                        title: book.title,
                        author: book.author,
                        bookKey: bookKey,
                        isDuplicate: seenBooks.has(bookKey)
                    });
                    
                    if (!seenBooks.has(bookKey)) {
                        seenBooks.add(bookKey);
                        uniqueBooks.push(book);
                    }
                });
                
                console.log('removeDuplicateBooks returning:', uniqueBooks.length, 'unique books');
                return uniqueBooks;
            }

            // Continue Reading: show currently borrowed books, otherwise show empty state.
            async loadContinueReading() {
                const loading = document.getElementById('loading-continue');
                const track = document.getElementById('continueCarousel');
                const emptyElement = document.getElementById('no-continue');
                const headerNote = document.querySelector('[data-continue-note]');
                if (!loading || !track || !emptyElement) return;

                track.classList.add('hidden');
                emptyElement.classList.add('hidden');

                // Try current loans first
                try {
                    const resLoans = await fetch(this.getRoute('loansApi', '/student/loans/api'), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (resLoans.ok) {
                        const loansJson = await resLoans.json();
                        const loans = Array.isArray(loansJson.loans) ? loansJson.loans : [];
                        if (loans.length > 0) {
                            const books = loans
                                .filter(l => l.book)
                                .map(l => {
                                    const b = l.book;
                                    // Ensure fields expected by renderer exist
                                    let cp = b.cover_photo || b.coverPhoto || b.cover;
                                    // Fix cover photo URL to be absolute
                                    if (cp && !cp.startsWith('http') && !cp.startsWith('/')) {
                                        cp = '/' + cp;
                                    }
                                    return {
                                        id: b.id,
                                        title: b.title,
                                        author: b.author,
                                        category: b.category || '',
                                        cover_photo: cp,
                                        program: b.program || '',
                                        publication_year: b.published_year || b.publication_year || b.year,
                                        availability_status: 'borrowed'
                                    };
                                });
                            
                            // Remove duplicates from loans books
                            const uniqueBooks = this.removeDuplicateBooks(books);
                                        
                            loading.classList.add('hidden');
                            track.classList.remove('hidden');
                            emptyElement.classList.add('hidden');
                            if (headerNote) headerNote.textContent = 'Pick up where you left off';
                            this.renderRecentBooks(uniqueBooks, 'continueCarousel');
                            // Initialize mobile scrolling for continue reading carousel
                            this.initMobileScrolling(track);
                            return; // Done
                        }
                    }
                } catch (err) {
                    
                }

                loading.classList.add('hidden');
                track.classList.add('hidden');
                track.innerHTML = '';
                emptyElement.classList.remove('hidden');
                if (headerNote) headerNote.textContent = 'Continue Reading';
            }

            // Recent Books functionality
            async loadRecentBooks() {
                const loadingElement = document.getElementById('loading-recent');
                const carouselElement = document.getElementById('recentBooksContainer');
                const noRecentBooksElement = document.getElementById('no-recent-books');
                
                try {
                    const response = await fetch(this.getRoute('recent', '/dashboard/recent'), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    if (!response.ok) {
                        throw new Error('Failed to load recent books');
                    }
                    
                    const data = await response.json();
                    const books = data.recent || [];
                    
                    // Hide loading indicator
                    if (loadingElement) {
                        loadingElement.classList.add('hidden');
                    }
                    
                    if (books.length === 0) {
                        // Show no recent books message
                        if (noRecentBooksElement) {
                            noRecentBooksElement.classList.remove('hidden');
                        }
                    } else {
                        // Render books and show carousel
                        const uniqueBooks = this.removeDuplicateBooks(books);
                        this.renderRecentBooks(uniqueBooks, 'recentBooksContainer');
                        if (carouselElement) {
                            carouselElement.classList.remove('hidden');
                        }
                        this.initMobileScrolling(carouselElement);
                        
                        // Update carousel settings for recent books (use unique count)
                        this.totalBooks = uniqueBooks.length;
                        this.maxIndex = Math.max(0, this.totalBooks - this.booksToShow);
                        this.updateCarouselButtons();
                    }
                    
                } catch (error) {
                    
                    // Hide loading indicator and show error or no books message
                    if (loadingElement) {
                        loadingElement.classList.add('hidden');
                    }
                    if (noRecentBooksElement) {
                        noRecentBooksElement.classList.remove('hidden');
                    }
                }
            }

            // Dashboard Statistics functionality
            async loadDashboardStats() {
                try {
                    const response = await fetch(this.getRoute('stats', '/dashboard/stats'), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    if (!response.ok) {
                        throw new Error('Failed to load dashboard stats');
                    }
                    
                    const stats = await response.json();
                    
                    // Update the stats cards with animation
                    this.animateStatsUpdate('books-read-count', stats.books_read || 0);
                    this.animateStatsUpdate('total-books-count', stats.total_books || 0);
                    this.animateStatsUpdate('current-loans-count', stats.current_loans || 0);
                    
                } catch (error) {
                    // Keep default values (0) if loading fails
                }
            }

            // Quick borrow functionality for dashboard buttons
            initQuickBorrowListeners() {
                // Add event listener for quick borrow buttons on the dashboard
                document.addEventListener('click', (e) => {
                    // Find the closest button with btn-borrow-quick class to handle clicks on child elements
                    const borrowButton = e.target.closest('.btn-borrow-quick');
                    if (borrowButton) {
                        e.preventDefault();
                        const bookId = borrowButton.getAttribute('data-book-id');
                        const bookTitle = borrowButton.getAttribute('data-book-title');
                        this.showBorrowPopup(bookId, bookTitle);
                    }
                });
            }

            async borrowBookQuick(bookId, bookTitle) {
                try {
                    const formData = new FormData();
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                    formData.append('duration', 1); // 1-day duration
                    
                    const response = await fetch(this.getBorrowUrl(bookId), {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        this.showNotification(data.message || 'Book borrowed successfully! It will be returned automatically in 1 day.', 'success');
                        
                        // Refresh borrowed books list to update UI
                        await this.loadUserBorrowedBooks();
                        
                        // Refresh all carousels to update button states
                        this.loadRecommendedBooks();
                        this.loadRecentBooks();
                        this.loadContinueReading();
                        
                        setTimeout(() => {
                            window.location.href = this.getBookUrl(bookId);
                        }, 1500);
                    } else {
                        this.showNotification(data.message || 'Failed to borrow book', 'error');
                    }
                } catch (error) {
                    this.showNotification('An error occurred while borrowing book', 'error');
                }
            }

            // Header search functionality
            initHeaderSearch() {
                const headerSearchInput = document.getElementById('header-search');
                const headerSearchBtn = document.getElementById('header-search-btn');
                const headerSearchForm = headerSearchInput ? headerSearchInput.closest('form') : null;
                const resultsPanel = document.getElementById('header-search-results');
                const isHomepageMobileSearch = document.body.classList.contains('homepage-dashboard');
                
                if (!headerSearchInput) {
                    return; // Exit if search input doesn't exist
                }

                let searchDebounce = null;
                let latestRequestId = 0;
                let autoHideTimer = null;

                const isMobileViewport = () => window.innerWidth <= 768;

                const clearAutoHideTimer = () => {
                    if (autoHideTimer) {
                        clearTimeout(autoHideTimer);
                        autoHideTimer = null;
                    }
                };

                const hideMobileSearch = () => {
                    if (!isHomepageMobileSearch || !isMobileViewport() || !headerSearchForm) return;
                    if (document.activeElement === headerSearchInput) return;
                    if (headerSearchInput.value.trim()) return;
                    headerSearchForm.classList.remove('mobile-search-open');
                    closeResults();
                };

                const startAutoHideTimer = () => {
                    if (!isHomepageMobileSearch || !isMobileViewport()) return;
                    clearAutoHideTimer();
                    autoHideTimer = setTimeout(() => {
                        hideMobileSearch();
                    }, 3000);
                };

                const showMobileSearch = () => {
                    if (!isHomepageMobileSearch || !isMobileViewport() || !headerSearchForm) return;
                    headerSearchForm.classList.add('mobile-search-open');
                    clearAutoHideTimer();
                    setTimeout(() => headerSearchInput.focus(), 30);
                    startAutoHideTimer();
                };

                const closeResults = () => {
                    if (!resultsPanel) return;
                    resultsPanel.classList.add('hidden');
                    resultsPanel.innerHTML = '';
                };

                const renderResults = (query, results) => {
                    if (!resultsPanel) return;

                    if (!Array.isArray(results) || results.length === 0) {
                        resultsPanel.innerHTML = `
                            <div class="p-4 text-sm text-gray-500">
                                No results found for "<span class="font-medium text-gray-700">${this.escapeHtml(query)}</span>".
                            </div>
                        `;
                        resultsPanel.classList.remove('hidden');
                        return;
                    }

                    const rows = results.map(item => {
                        const cover = this.escapeHtml(item.cover_url || '/covers/default-book.png');
                        const title = this.escapeHtml(item.title || 'Untitled');
                        const author = this.escapeHtml(item.author || 'Unknown Author');
                        const type = this.escapeHtml(item.type || 'BOOK');
                        const viewUrl = this.escapeHtml(item.view_url || '#');
                        const readUrl = item.read_url ? this.escapeHtml(item.read_url) : null;

                        return `
                            <div class="quick-search-item p-3 border-b border-gray-100 last:border-b-0 hover:bg-gray-50 transition-colors">
                                <div class="quick-search-row flex gap-3">
                                    <img src="${cover}" alt="${title}" class="quick-search-cover w-10 h-14 object-cover rounded border border-gray-200" loading="lazy">
                                    <div class="quick-search-meta flex-1 min-w-0">
                                        <div class="quick-search-head flex items-center justify-between gap-2">
                                            <p class="text-sm font-semibold text-gray-900 truncate">${title}</p>
                                            <span class="text-[10px] px-2 py-1 rounded-full bg-blue-50 text-blue-700 font-semibold">${type}</span>
                                        </div>
                                        <p class="text-xs text-gray-600 truncate mt-0.5">${author}</p>
                                        <div class="quick-search-actions mt-2 flex gap-2">
                                            <a href="${viewUrl}" class="text-xs px-2 py-1 rounded bg-blue-600 text-white hover:bg-blue-700">View</a>
                                            ${readUrl ? `<a href="${readUrl}" class="text-xs px-2 py-1 rounded bg-green-600 text-white hover:bg-green-700">Read</a>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');

                    resultsPanel.innerHTML = `
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 bg-gray-50 border-b border-gray-200">
                            Search Results
                        </div>
                        ${rows}
                        <a href="${this.getRoute('booksBase', '/student/books')}?search=${encodeURIComponent(query)}" class="block px-3 py-2 text-xs text-blue-700 hover:bg-blue-50 border-t border-gray-200 font-medium">
                            See all results for "${this.escapeHtml(query)}"
                        </a>
                    `;

                    resultsPanel.classList.remove('hidden');
                };

                const fetchQuickResults = async (term) => {
                    if (!resultsPanel) return;
                    if (term.length < 2) {
                        closeResults();
                        return;
                    }

                    const requestId = ++latestRequestId;
                    resultsPanel.innerHTML = `<div class="p-3 text-sm text-gray-500">Searching...</div>`;
                    resultsPanel.classList.remove('hidden');

                    try {
                        const searchUrl = new URL(this.getRoute('search', '/dashboard/search'), window.location.origin);
                        searchUrl.searchParams.set('q', term);
                        const response = await fetch(searchUrl.toString(), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (!response.ok) throw new Error('Quick search request failed');
                        const data = await response.json();
                        if (requestId !== latestRequestId) return;
                        renderResults(term, data.results || []);
                    } catch (error) {
                        if (requestId !== latestRequestId) return;
                        resultsPanel.innerHTML = `<div class="p-3 text-sm text-red-500">Unable to load search results.</div>`;
                        resultsPanel.classList.remove('hidden');
                    }
                };
                
                // Function to perform search
                const performSearch = () => {
                    const searchTerm = headerSearchInput.value.trim();
                    if (searchTerm) {
                        clearAutoHideTimer();
                        closeResults();
                        // Redirect to books page with search query
                        window.location.href = `${this.getRoute('booksBase', '/student/books')}?search=${encodeURIComponent(searchTerm)}`;
                    }
                };
                
                // Search on button click
                if (headerSearchBtn) {
                    headerSearchBtn.addEventListener('click', (e) => {
                        if (isHomepageMobileSearch && isMobileViewport() && !headerSearchForm.classList.contains('mobile-search-open')) {
                            e.preventDefault();
                            showMobileSearch();
                            return;
                        }

                        if (isHomepageMobileSearch && isMobileViewport() && !headerSearchInput.value.trim()) {
                            e.preventDefault();
                            startAutoHideTimer();
                            return;
                        }

                        performSearch();
                    });
                }
                
                // Search on Enter key
                headerSearchInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        performSearch();
                    } else if (e.key === 'Escape') {
                        closeResults();
                    }
                });

                headerSearchInput.addEventListener('input', () => {
                    const term = headerSearchInput.value.trim();
                    clearAutoHideTimer();
                    clearTimeout(searchDebounce);
                    searchDebounce = setTimeout(() => {
                        fetchQuickResults(term);
                    }, 250);
                });

                headerSearchInput.addEventListener('focus', () => {
                    if (isHomepageMobileSearch && isMobileViewport() && headerSearchForm) {
                        headerSearchForm.classList.add('mobile-search-open');
                    }
                    clearAutoHideTimer();
                    const term = headerSearchInput.value.trim();
                    if (term.length >= 2) {
                        fetchQuickResults(term);
                    }
                });

                headerSearchInput.addEventListener('blur', () => {
                    startAutoHideTimer();
                });

                // Support native form submit as a fallback
                if (headerSearchForm) {
                    headerSearchForm.addEventListener('submit', (e) => {
                        e.preventDefault();
                        performSearch();
                    });
                }

                document.addEventListener('click', (e) => {
                    if (!headerSearchForm) return;
                    if (!headerSearchForm.contains(e.target)) {
                        closeResults();
                        startAutoHideTimer();
                    }
                });

                window.addEventListener('resize', () => {
                    if (!isMobileViewport() && headerSearchForm) {
                        clearAutoHideTimer();
                        headerSearchForm.classList.remove('mobile-search-open');
                    }
                });
                
                // Add placeholder animation
                const placeholders = [
                    'Quick search books...',
                    'Search by title...',
                    'Search by author...',
                    'Find your next book...'
                ];
                let placeholderIndex = 0;
                
                setInterval(() => {
                    placeholderIndex = (placeholderIndex + 1) % placeholders.length;
                    headerSearchInput.setAttribute('placeholder', placeholders[placeholderIndex]);
                }, 3000);
            }

            animateStatsUpdate(elementId, newValue) {
                const element = document.getElementById(elementId);
                if (!element) return;
                
                const currentValue = parseInt(element.textContent) || 0;
                const increment = Math.ceil((newValue - currentValue) / 20);
                
                if (increment === 0) {
                    element.textContent = newValue;
                    return;
                }
                
                let current = currentValue;
                const timer = setInterval(() => {
                    current += increment;
                    
                    if ((increment > 0 && current >= newValue) || (increment < 0 && current <= newValue)) {
                        element.textContent = newValue;
                        clearInterval(timer);
                    } else {
                        element.textContent = current;
                    }
                }, 50);
            }

            // Mobile scrolling functionality for carousels
            initMobileScrolling(carousel) {
                if (!carousel) return;
                
                // Enable touch and horizontal swipe behavior on phones and tablets.
                const isTouchViewport = window.innerWidth <= 1024;
                if (!isTouchViewport) return;
                
                // Touch events for better mobile experience
                let startX = 0;
                let scrollLeft = 0;
                
                carousel.addEventListener('touchstart', (e) => {
                    startX = e.touches[0].pageX - carousel.offsetLeft;
                    scrollLeft = carousel.scrollLeft;
                });
                
                carousel.addEventListener('touchmove', (e) => {
                    if (!startX) return;
                    
                    const x = e.touches[0].pageX - carousel.offsetLeft;
                    const walk = (x - startX) * 2; // Adjust scroll speed
                    carousel.scrollLeft = scrollLeft - walk;
                });
                
                carousel.addEventListener('touchend', () => {
                    startX = 0;
                });
            }

            showBorrowPopup(bookId, bookTitle, bookAuthor) {
                // Update popup content with book details
                const titleElement = document.getElementById('borrowPopupTitle');
                if (titleElement) {
                    titleElement.textContent = bookTitle || 'Book Title';
                }

                // Store book ID for borrowing
                this.currentBookId = bookId;
                this.currentBookTitle = bookTitle || '';

                // Show popup
                const popup = document.getElementById('borrowPopup');
                const card = document.getElementById('borrowPopupCard');
                
                if (popup && card) {
                    popup.classList.remove('hidden');
                    setTimeout(() => {
                        card.classList.remove('scale-95', 'opacity-0');
                        card.classList.add('scale-100', 'opacity-100');
                    }, 10);
                    document.body.style.overflow = 'hidden';
                }
            }

            initBorrowPopupEvents() {
                const popup = document.getElementById('borrowPopup');
                const cancelButton = document.getElementById('borrowPopupCancel');

                if (cancelButton) {
                    cancelButton.addEventListener('click', () => this.hideBorrowPopup());
                }

                if (popup) {
                    popup.addEventListener('click', (e) => {
                        if (e.target === popup) {
                            this.hideBorrowPopup();
                        }
                    });
                }

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && popup && !popup.classList.contains('hidden')) {
                        this.hideBorrowPopup();
                    }
                });
            }

            async confirmBorrow() {
                if (!this.currentBookId) return;
                const bookId = this.currentBookId;

                // Show loading state
                const confirmBtn = document.getElementById('borrowPopupConfirm');
                const confirmText = document.getElementById('borrowPopupConfirmText');
                
                if (confirmBtn && confirmText) {
                    confirmBtn.disabled = true;
                    confirmText.textContent = 'Borrowing...';
                }

                try {
                    const response = await fetch(this.getBorrowUrl(bookId), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ duration: 1 })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        this.hideBorrowPopup();
                        this.showNotification(data.message || 'Book borrowed successfully!', 'success');
                        await this.loadUserBorrowedBooks();
                        this.loadRecommendedBooks();
                        this.loadRecentBooks();
                        this.loadContinueReading();

                        setTimeout(() => {
                            window.location.href = this.getBookUrl(bookId);
                        }, 1500);
                    } else {
                        this.showNotification(data.message || 'Failed to borrow book', 'error');
                    }
                } catch (error) {
                    this.showNotification('Error borrowing book', 'error');
                } finally {
                    // Reset button state
                    if (confirmBtn && confirmText) {
                        confirmBtn.disabled = false;
                        confirmText.textContent = 'Confirm Borrow';
                    }
                }
            }

            hideBorrowPopup() {
                const popup = document.getElementById('borrowPopup');
                const card = document.getElementById('borrowPopupCard');
                
                if (popup && card) {
                    card.classList.add('scale-95', 'opacity-0');
                    card.classList.remove('scale-100', 'opacity-100');
                    setTimeout(() => {
                        popup.classList.add('hidden');
                        document.body.style.overflow = '';
                    }, 200);
                }
                this.currentBookId = null;
                this.currentBookTitle = '';
            }

        }

        // Expose the class globally
        window.StudentDashboard = StudentDashboard;
    }

    document.addEventListener('DOMContentLoaded', function() {
        // expose instance globally so other pages can reuse the dashboard functionality
        window.__studentDashboard = new StudentDashboard();
    });

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = StudentDashboard;
    }
})();

