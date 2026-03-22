// Loans Page JavaScript
class LoansManager {
    constructor() {
        this.selectedLoanId = null;
        this.isLoading = false;
        this.state = {
            allLoans: [],
            filteredLoans: [],
            searchTerm: '',
            currentFilters: {},
            sortBy: 'due-date-asc'
        };

        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.setupHeaderSearch();
        this.initSearchFromURL();
        this.loadCurrentLoans();
    }
    
    setupEventListeners() {
        // Renew all button
        const renewAllBtn = document.getElementById('renew-all-btn');
        if (renewAllBtn) {
            renewAllBtn.addEventListener('click', () => {
                this.renewAllEligible();
            });
        }

        // Filter and sort event listeners
        this.setupFilterSortEventListeners();

        // Modal event listeners
        this.setupModalEventListeners();

        // Action button event delegation
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-return-loan') || e.target.closest('.btn-return-loan')) {
                const button = e.target.matches('.btn-return-loan') ? e.target : e.target.closest('.btn-return-loan');
                const loanId = button.dataset.loanId;
                this.handleReturnClick(e, loanId); // Return book
            } else if (e.target.matches('.btn-view-loan') || e.target.closest('.btn-view-loan')) {
                const button = e.target.matches('.btn-view-loan') ? e.target : e.target.closest('.btn-view-loan');
                const bookId = button.dataset.bookId;
                this.viewBookDetails(bookId); // View book details
            }
        });
    }

    setupFilterSortEventListeners() {
        // Combined filter & sort dropdown toggle
        const filterSortBtn = document.getElementById('filter-sort-btn');
        const filterSortDropdown = document.getElementById('filter-sort-dropdown');
        const filterSortTabs = document.querySelectorAll('.filter-sort-tab');
        const filterSortContents = document.querySelectorAll('.filter-sort-content');

        // Toggle combined dropdown
        if (filterSortBtn && filterSortDropdown) {
            filterSortBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                filterSortDropdown.classList.toggle('hidden');
            });
        }

        // Tab switching functionality
        filterSortTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');

                // Remove active class from all tabs
                filterSortTabs.forEach(t => {
                    t.classList.remove('active', 'text-blue-600', 'border-blue-500', 'bg-blue-50');
                    t.classList.add('text-gray-500', 'border-transparent');
                });

                // Add active class to clicked tab
                this.classList.add('active', 'text-blue-600', 'border-blue-500', 'bg-blue-50');
                this.classList.remove('text-gray-500', 'border-transparent');

                // Hide all tab contents
                filterSortContents.forEach(content => {
                    content.classList.add('hidden');
                });

                // Show target tab content
                const targetContent = document.getElementById(targetTab + '-tab-content');
                if (targetContent) {
                    targetContent.classList.remove('hidden');
                }
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            if (filterSortDropdown) filterSortDropdown.classList.add('hidden');
        });

        // Prevent dropdown from closing when clicking inside
        if (filterSortDropdown) {
            filterSortDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }

        // Apply filters
        const applyFiltersBtn = document.getElementById('apply-filters');
        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', () => {
                this.applyFilters();
                if (filterSortDropdown) filterSortDropdown.classList.add('hidden');
            });
        }

        // Clear filters
        const clearFiltersBtn = document.getElementById('clear-filters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                this.clearFilters();
                if (filterSortDropdown) filterSortDropdown.classList.add('hidden');
            });
        }

        // Sort select
        const sortSelect = document.getElementById('sort-select');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                this.state.sortBy = e.target.value;
                this.performSearch();
            });
        }
    }

    applyFilters() {
        const form = document.getElementById('filter-form');
        if (!form) return;

        const formData = new FormData(form);
        this.state.currentFilters = {};
        for (const [key, value] of formData.entries()) {
            if (value && value.trim() !== '') {
                this.state.currentFilters[key] = value.trim();
            }
        }
        this.performSearch();
        this.updateFilterCount();
    }

    clearFilters() {
        this.state.currentFilters = {};
        const form = document.getElementById('filter-form');
        if (form) {
            form.reset();
            form.querySelectorAll('select').forEach(select => select.value = '');
        }
        this.performSearch();
        this.updateFilterCount();
    }

    updateFilterCount() {
        const count = Object.keys(this.state.currentFilters).length + (this.state.searchTerm ? 1 : 0);
        const badge = document.getElementById('active-filters-count');
        if (badge) {
            badge.textContent = count;
            badge.classList.toggle('hidden', count === 0);
        }
    }
    
    setupModalEventListeners() {
        // Renew modal
        const renewModal = document.getElementById('renew-modal');
        if (renewModal) {
            renewModal.addEventListener('click', (e) => {
                if (e.target === renewModal) {
                    this.closeModal('renew-modal');
                }
            });
        }
        
        const confirmRenewBtn = document.getElementById('confirm-renew');
        const cancelRenewBtn = document.getElementById('cancel-renew');
        
        if (confirmRenewBtn) {
            confirmRenewBtn.addEventListener('click', () => {
                this.confirmRenew();
            });
        }
        
        if (cancelRenewBtn) {
            cancelRenewBtn.addEventListener('click', () => {
                this.closeModal('renew-modal');
            });
        }
        
        // Escape key to close modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal('renew-modal');
                // return popup uses custom handler
                const container = document.getElementById('returnPopup');
                if (container && !container.classList.contains('hidden')) {
                    this._closeReturnPopup();
                }
            }
        });
    }
    
    async loadCurrentLoans() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoadingState();
        
        try {
            const response = await fetch('/student/loans/api', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load loans data');
            }
            
            const data = await response.json();
            this.state.allLoans = data.loans || [];
            this.performSearch();
            
        } catch (error) {
            
            this.showErrorState();
        } finally {
            this.isLoading = false;
            this.hideLoadingState();
        }
    }
    
    async loadStatistics() {
        try {
            const response = await fetch('/student/loans/statistics', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load statistics');
            }
            
            const stats = await response.json();
            this.updateStatistics(stats);
            
        } catch (error) {
            
            // Set default values if API fails
            this.updateStatistics({
                current_loans: 0,
                due_soon: 0,
                overdue_loans: 0
            });
        }
    }
    
    updateStatistics(stats) {
        const elCurrent = document.getElementById('current-loans');
        const elDueSoon = document.getElementById('due-soon');
        const elOverdue = document.getElementById('overdue-loans');
        if (elCurrent) elCurrent.textContent = stats.current_loans || 0;
        if (elDueSoon) elDueSoon.textContent = stats.due_soon || 0;
        if (elOverdue) elOverdue.textContent = stats.overdue_loans || 0;
    }
    
    renderLoans(loans) {
        const loansContainer = document.getElementById('loans-container');
        const loansGrid = document.getElementById('loans-grid');
        const emptyState = document.getElementById('empty-state');
        const loansCount = document.getElementById('loans-count');
        
        if (!loans || loans.length === 0) {
            loansContainer.classList.add('hidden');
            emptyState.classList.remove('hidden');
            loansCount.textContent = this.state.searchTerm ? 'No loans match your search' : 'No active loans';
            return;
        }
        
        loansContainer.classList.remove('hidden');
        emptyState.classList.add('hidden');
        loansCount.textContent = `${loans.length} active loan${loans.length !== 1 ? 's' : ''}`;
        
        // Clear existing content
        loansGrid.innerHTML = '';
        
        // Render each loan
        loans.forEach(loan => {
            const loanCard = this.createLoanCard(loan);
            loansGrid.appendChild(loanCard);
        });
    }
    
    performSearch() {
        const term = (this.state.searchTerm || '').toLowerCase();
        let filtered = [...this.state.allLoans];

        // Apply search filter
        if (term) {
            filtered = filtered.filter(loan => {
                const title = loan.book?.title?.toLowerCase() || '';
                const author = loan.book?.author?.toLowerCase() || '';
                const status = this.getLoanStatus(loan).toLowerCase();
                const category = loan.book?.category?.toLowerCase() || '';
                return title.includes(term) || author.includes(term) || status.includes(term) || category.includes(term);
            });
        }

        // Apply filters
        filtered = this.filterLoans(filtered);

        // Apply sorting
        filtered = this.sortLoans(filtered);

        this.state.filteredLoans = filtered;
        this.renderLoans(this.state.filteredLoans);
    }

    filterLoans(loans) {
        const f = this.state.currentFilters || {};
        let filtered = [...loans];

        if (f.status) {
            filtered = filtered.filter(loan => this.getLoanStatus(loan).toLowerCase() === f.status.toLowerCase());
        }

        if (f.category) {
            filtered = filtered.filter(loan => (loan.book?.category || '').toLowerCase() === f.category.toLowerCase());
        }

        return filtered;
    }

    sortLoans(loans) {
        return loans.sort((a, b) => {
            switch (this.state.sortBy) {
                case 'due-date-asc':
                    return new Date(a.due_date) - new Date(b.due_date);
                case 'due-date-desc':
                    return new Date(b.due_date) - new Date(a.due_date);
                case 'title-asc':
                    return (a.book?.title || '').localeCompare(b.book?.title || '');
                case 'title-desc':
                    return (b.book?.title || '').localeCompare(a.book?.title || '');
                case 'author-asc':
                    return (a.book?.author || '').localeCompare(b.book?.author || '');
                case 'author-desc':
                    return (b.book?.author || '').localeCompare(a.book?.author || '');
                case 'borrowed-date-asc':
                    return new Date(a.borrowed_date) - new Date(b.borrowed_date);
                case 'borrowed-date-desc':
                    return new Date(b.borrowed_date) - new Date(a.borrowed_date);
                default:
                    return 0;
            }
        });
    }
    
    setupHeaderSearch() {
        const headerSearchInput = document.getElementById('header-search');
        const headerSearchBtn = document.getElementById('header-search-btn');
        const headerSearchForm = headerSearchInput ? headerSearchInput.closest('form') : null;
        const resultsPanel = document.getElementById('header-search-results');

        if (!headerSearchInput || !headerSearchBtn) {
            return;
        }

        let timeout;
        let searchDebounce = null;
        let latestRequestId = 0;

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
                <a href="/student/books?search=${encodeURIComponent(query)}" class="block px-3 py-2 text-xs text-blue-700 hover:bg-blue-50 border-t border-gray-200 font-medium">
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
                const response = await fetch(`/dashboard/search?q=${encodeURIComponent(term)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!response.ok) {
                    throw new Error('Quick search request failed');
                }

                const data = await response.json();
                if (requestId !== latestRequestId) return;
                renderResults(term, data.results || []);
            } catch (error) {
                if (requestId !== latestRequestId) return;
                resultsPanel.innerHTML = `<div class="p-3 text-sm text-red-500">Unable to load search results.</div>`;
                resultsPanel.classList.remove('hidden');
            }
        };

        const performHeaderSearch = () => {
            const searchTerm = headerSearchInput.value.trim();
            this.state.searchTerm = searchTerm;
            this.performSearch();

            if (searchTerm) {
                closeResults();
                window.location.href = `/student/books?search=${encodeURIComponent(searchTerm)}`;
            }
        };

        headerSearchBtn.addEventListener('click', (e) => {
            e.preventDefault();
            performHeaderSearch();
        });

        headerSearchInput.addEventListener('input', (e) => {
            const term = e.target.value.trim();
            this.state.searchTerm = term;

            clearTimeout(timeout);
            timeout = setTimeout(() => {
                this.performSearch();
            }, 300);

            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                fetchQuickResults(term);
            }, 250);
        });

        headerSearchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                performHeaderSearch();
            } else if (e.key === 'Escape') {
                closeResults();
            }
        });

        headerSearchInput.addEventListener('focus', () => {
            const term = headerSearchInput.value.trim();
            if (term.length >= 2) {
                fetchQuickResults(term);
            }
        });

        if (headerSearchForm) {
            headerSearchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                performHeaderSearch();
            });
        }

        document.addEventListener('click', (e) => {
            if (!headerSearchForm) return;
            if (!headerSearchForm.contains(e.target)) {
                closeResults();
            }
        });

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
    
    initSearchFromURL() {
        try {
            const params = new URLSearchParams(window.location.search);
            const q = params.get('search') || params.get('q') || '';
            if (q) {
                this.state.searchTerm = q.trim();
                const headerSearch = document.getElementById('header-search');
                if (headerSearch) headerSearch.value = this.state.searchTerm;
            }
        } catch (e) {
            
        }
    }
    
    createLoanCard(loan) {
        const cardDiv = document.createElement('div');
        cardDiv.className = 'group relative bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 book-card overflow-hidden';

        const status = this.getLoanStatus(loan);
        const daysLeft = this.getDaysLeft(loan.due_date);
        const category = loan.book?.category || 'General';
        const catKey = category.toLowerCase().replace(/\s+/g, '') || 'blue';
        const coverUrl = this.getCoverUrl(loan.book?.cover_photo);
        const badgeText = (loan.status || 'borrowed');
        const badgeClass = status === 'Overdue' ? 'bg-red-500 text-white' : (status === 'Due Soon' ? 'bg-yellow-500 text-white' : 'bg-green-500 text-white');
        const icon = ({
            programming: 'fa-code', mathematics: 'fa-calculator', literature: 'fa-feather-alt', science: 'fa-flask',
            business: 'fa-chart-line', technology: 'fa-microchip', education: 'fa-graduation-cap', reference: 'fa-bookmark'
        })[catKey] || 'fa-book';

        // Match student books color mapping
        const categoryColor = ({
            'programming': 'bg-blue-600',
            'mathematics': 'bg-green-600',
            'literature': 'bg-purple-600',
            'science': 'bg-red-600',
            'business': 'bg-amber-600',
            'technology': 'bg-indigo-600',
            'education': 'bg-pink-600',
            'reference': 'bg-gray-600'
        })[catKey] || 'bg-gray-600';

        const categoryBgColor = ({
            'programming': 'bg-blue-100',
            'mathematics': 'bg-green-100',
            'literature': 'bg-purple-100',
            'science': 'bg-red-100',
            'business': 'bg-amber-100',
            'technology': 'bg-indigo-100',
            'education': 'bg-pink-100',
            'reference': 'bg-gray-100'
        })[catKey] || 'bg-gray-100';

        cardDiv.innerHTML = `
            <!-- Book Cover -->
            <div class="relative aspect-[3/4] bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden shadow border border-gray-200">
                ${coverUrl ? `
                    <img
                        src="${coverUrl}"
                        alt="${this.escapeHtml(loan.book?.title || 'Book Cover')}"
                        class="w-full h-full object-cover book-cover-img"
                        onload="this.style.opacity = '1'; this.nextElementSibling.style.display='none';"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                        style="opacity: 0; transition: opacity 0.3s ease-in-out;"
                        loading="lazy"
                    >
                ` : ''}

                <!-- Default book cover design -->
                <div class="absolute inset-0 bg-white default-book-cover" style="display: ${coverUrl ? 'none' : 'block'};">
                    <div class="h-8 ${categoryColor} relative">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                    </div>
                    
                    <div class="p-3 h-full flex flex-col justify-between">
                        <div class="text-center">
                            <h3 class="text-xs font-bold text-gray-900 leading-tight mb-1">${this.escapeHtml(loan.book?.title || 'Unknown Book')}</h3>
                            <p class="text-xs text-gray-600 font-medium line-clamp-1">${this.escapeHtml(loan.book?.author || 'Unknown Author')}</p>
                        </div>
                        
                        <div class="flex-1 flex items-center justify-center my-1">
                            <div class="w-10 h-10 ${categoryBgColor} rounded-full flex items-center justify-center">
                                <i class="fas ${icon} text-lg ${categoryColor.replace('bg-', 'text-')}"></i>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">${this.escapeHtml(category)}</div>
                        </div>
                    </div>
                    
                    <div class="absolute bottom-0 left-0 right-0 h-8 ${categoryColor}">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                    </div>
                </div>
                
                <!-- Status Badge -->
                <div class="absolute top-2 left-2 z-20">
                    <span class="px-2 py-1 text-xs font-bold rounded-full shadow ${badgeClass}">${badgeText}</span>
                </div>
            </div>

            <!-- Book Details -->
            <div class="p-2 bg-white">
                <p class="text-gray-600 text-xs mb-2 line-clamp-1">
                    ${this.escapeHtml(loan.book?.description || 'No description available.')}
                </p>
                
                <div class="space-y-1 text-xs mb-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Borrowed:</span>
                        <span class="text-gray-900">${this.formatDate(loan.borrowed_date)}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Due:</span>
                        <span class="text-gray-900 ${this.getDueDateClass(loan)}">${this.formatDate(loan.due_date)}</span>
                    </div>
                    ${daysLeft !== null ? `
                        <div class="flex justify-between">
                            <span class="text-gray-500">Days:</span>
                            <span class="font-medium ${this.getDaysLeftClass(daysLeft)}">${this.formatDaysLeft(daysLeft)}</span>
                        </div>
                    ` : ''}
                </div>
                
                <div class="flex gap-1">
                    ${this.renderLoanActions(loan)}
                </div>
            </div>
        `;
        
        return cardDiv;
    }
    
    renderLoanActions(loan) {
        let actions = [];
        
        // Return button
        actions.push(`
            <button class="btn-return-loan action-btn btn-return flex-1 text-xs px-2 py-1.5 min-h-[32px] bg-green-500 hover:bg-green-600 text-white" data-loan-id="${loan.id}">
                <i class="fas fa-undo text-xs"></i>
                <span class="hidden sm:inline ml-1">Return</span>
            </button>
        `);
        
        // View button
        actions.push(`
            <button class="btn-view-loan action-btn btn-view flex-1 text-xs px-2 py-1.5 min-h-[32px]" data-book-id="${loan.book?.id}">
                <i class="fas fa-eye text-xs"></i>
                <span class="hidden sm:inline ml-1">View</span>
            </button>
        `);
        
        return actions.join('');
    }
    
    getLoanStatus(loan) {
        if (!loan.due_date) return 'Active';
        
        const dueDate = new Date(loan.due_date);
        const today = new Date();
        
        if (dueDate < today) {
            return 'Overdue';
        } else if (this.getDaysLeft(loan.due_date) <= 3) {
            return 'Due Soon';
        }
        
        return 'Active';
    }
    
    getStatusClass(status) {
        switch (status) {
            case 'Active': return 'status-borrowed';
            case 'Due Soon': return 'status-reserved';
            case 'Overdue': return 'status-overdue';
            default: return 'status-borrowed';
        }
    }
    
    getDaysLeft(dueDate) {
        if (!dueDate) return null;
        
        const due = new Date(dueDate);
        const today = new Date();
        const diffTime = due - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        return diffDays;
    }
    
    getDueDateClass(loan) {
        const daysLeft = this.getDaysLeft(loan.due_date);
        
        if (daysLeft < 0) return 'text-red-600 font-semibold';
        if (daysLeft <= 3) return 'text-yellow-600 font-semibold';
        return 'text-gray-900';
    }
    
    getDaysLeftClass(daysLeft) {
        if (daysLeft < 0) return 'text-red-600';
        if (daysLeft <= 3) return 'text-yellow-600';
        return 'text-green-600';
    }
    
    formatDaysLeft(daysLeft) {
        if (daysLeft < 0) {
            return `${Math.abs(daysLeft)} days overdue`;
        } else if (daysLeft === 0) {
            return 'Due today';
        } else if (daysLeft === 1) {
            return '1 day left';
        } else {
            return `${daysLeft} days left`;
        }
    }
    
    isRenewalLimitReached(loan) {
        return (loan.renewal_count || 0) >= 2;
    }
    
    formatDate(dateString) {
        if (!dateString) return 'N/A';
        
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }
    getCoverUrl(path) {
        if (!path) return '';
        let p = String(path);
        if (/^https?:\/\//i.test(p) || p.startsWith('/')) return p;
        p = p.replace(/^\.+\/?/, '');
        return '/' + p;
    }
    
    async handleReturnClick(e, loanId) {
        this.selectedLoanId = loanId;
        const card = e.target.closest('.book-card');
        const title = card?.querySelector('h3')?.textContent?.trim() || 'this book';
        const ok = await this.confirmReturnModal(title);
        if (ok) {
            await this.confirmReturn();
        }
    }
    
    async confirmReturn() {
        if (!this.selectedLoanId) return;
        
        try {
            const response = await fetch(`/student/borrow-records/${this.selectedLoanId}/return`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (response.ok) {
                this.showNotification('Book returned successfully!', 'success');
                this.loadCurrentLoans();
                this.loadStatistics();
            } else {
                this.showNotification(data.message || 'Failed to return book', 'error');
            }
        } catch (error) {
            
            this.showNotification('Failed to return book. Please try again.', 'error');
        } finally {
            // cleanup handled by confirmReturnModal
        }
    }
    
    async viewBookDetails(bookId) {
        if (!bookId) return;
        
        // Redirect to book details page
        window.location.href = `/student/books/${bookId}`;
    }
    
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
        }
        this.selectedLoanId = null;
    }
    
    showLoadingState() {
        document.getElementById('loading-indicator').classList.remove('hidden');
        document.getElementById('loans-container').classList.add('hidden');
        document.getElementById('empty-state').classList.add('hidden');
    }
    
    hideLoadingState() {
        document.getElementById('loading-indicator').classList.add('hidden');
    }
    
    showErrorState() {
        const loansGrid = document.getElementById('loans-grid');
        if (loansGrid) {
            loansGrid.innerHTML = `
                <div class="col-span-full">
                    <div class="empty-state p-8 text-center">
                        <i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Error Loading Loans</h3>
                        <p class="text-gray-600 mb-4">Something went wrong while loading your loans.</p>
                        <button onclick="window.loansManager.loadCurrentLoans()" 
                                class="action-btn btn-details">
                            <i class="fas fa-refresh mr-1"></i> Try Again
                        </button>
                    </div>
                </div>
            `;
        }
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg border transition-all duration-300 ${
            type === 'success' ? 'bg-white text-blue-600 border-blue-200' :
            type === 'error' ? 'bg-red-500 text-white border-red-500' :
            'bg-blue-500 text-white border-blue-500'
        }`;
        notification.innerHTML = `
            <div class="flex items-center gap-2">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }

    // Return confirmation popup (same UX as Borrow)
    confirmReturnModal(bookTitle) {
        return new Promise((resolve) => {
            const container = document.getElementById('returnPopup');
            const card = document.getElementById('returnPopupCard');
            const titleEl = document.getElementById('returnPopupTitle');
            const confirmBtn = document.getElementById('returnPopupConfirm');
            const cancelBtn = document.getElementById('returnPopupCancel');

            if (!container || !card || !titleEl || !confirmBtn || !cancelBtn) {
                const ok = confirm(`Return "${bookTitle}" now?`);
                resolve(ok);
                return;
            }

            titleEl.textContent = `"${bookTitle}"`;
            container.classList.remove('hidden');
            requestAnimationFrame(() => {
                card.classList.remove('scale-95', 'opacity-0');
                card.classList.add('scale-100', 'opacity-100');
            });

            const cleanup = () => {
                card.classList.remove('scale-100', 'opacity-100');
                card.classList.add('scale-95', 'opacity-0');
                setTimeout(() => container.classList.add('hidden'), 180);
                // remove listeners by cloning
                confirmBtn.replaceWith(confirmBtn.cloneNode(true));
                cancelBtn.replaceWith(cancelBtn.cloneNode(true));
            };

            this._closeReturnPopup = cleanup; // store for Esc handling

            container.onclick = (e) => {
                if (e.target === container) {
                    cleanup();
                    resolve(false);
                }
            };

            document.getElementById('returnPopupCancel').addEventListener('click', () => {
                cleanup();
                resolve(false);
            }, { once: true });

            document.getElementById('returnPopupConfirm').addEventListener('click', () => {
                cleanup();
                resolve(true);
            }, { once: true });
        });
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    window.loansManager = new LoansManager();
});
