// History Page JavaScript
class HistoryManager {
    static MAX_HISTORY = 500;
    constructor() {
        this.currentFilters = {};
        this.searchTerm = '';
        this.dateFrom = '';
        this.dateTo = '';
        this.statusFilter = '';
        this.resourceTypeFilter = '';
        this.currentPage = 1;
        this.isLoading = false;
        this.selectedRecordId = null;
        
        this.init();
    }

    getRoute(name, fallback = '') {
        return (window.historyPageRoutes && window.historyPageRoutes[name]) || fallback;
    }

    getBookUrl(bookId) {
        const base = this.getRoute('booksBase', '/student/books');
        return `${base.replace(/\/$/, '')}/${bookId}`;
    }

    getBookDetailsUrl(bookId) {
        return `${this.getBookUrl(bookId)}/details`;
    }

    getBookCoverUrl(book) {
        if (!book) {
            return '';
        }

        let coverPath = book.cover_url || book.cover_image || book.cover_photo || '';
        if (!coverPath) {
            return '';
        }

        coverPath = String(coverPath).trim();

        if (coverPath.startsWith('http://') || coverPath.startsWith('https://')) {
            return coverPath;
        }

        if (!coverPath.startsWith('/')) {
            coverPath = `/${coverPath.replace(/^\/+/, '')}`;
        }

        if (coverPath.startsWith('/covers/')) {
            return coverPath.replace(/^\/covers\//, '/storage/covers/');
        }

        return coverPath;
    }

    getRenewUrl(recordId) {
        const base = this.getRoute('renewBase', '/student/borrow-records');
        return `${base.replace(/\/$/, '')}/${recordId}/renew`;
    }

    getReturnUrl(recordId) {
        const base = this.getRoute('renewBase', '/student/borrow-records');
        return `${base.replace(/\/$/, '')}/${recordId}/return`;
    }
    
    init() {
        // Set initial state
        this.setInitialState();
        this.setupEventListeners();
        this.loadHistoryData();
    }
    
    setInitialState() {
        // Ensure proper initial state
        const loadingIndicator = document.getElementById('loading-indicator');
        const tableContainer = document.getElementById('history-table-container');
        const emptyState = document.getElementById('empty-state');
        const resultsCount = document.getElementById('results-count');
        
        // Hide loading and table, show empty state by default
        if (loadingIndicator) loadingIndicator.classList.add('hidden');
        if (tableContainer) tableContainer.classList.add('hidden');
        if (emptyState) emptyState.classList.remove('hidden');
        if (resultsCount) resultsCount.textContent = 'Ready';
        
    }
    
    setupEventListeners() {
        // Search functionality
        const searchInput = document.getElementById('search-history');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.searchTerm = e.target.value.trim();
                    this.currentPage = 1;
                    this.renderActiveFilters();
                    this.loadHistoryData();
                }, 300);
            });
        }

        const filterBtn = document.getElementById('history-filter-btn');
        const filterDropdown = document.getElementById('history-filter-dropdown');
        const applyFiltersBtn = document.getElementById('apply-history-filters');

        if (filterBtn && filterDropdown) {
            filterBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                filterDropdown.classList.toggle('hidden');
            });

            filterDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
            });

            document.addEventListener('click', () => {
                filterDropdown.classList.add('hidden');
            });
        }

        // Date filters
        const dateFrom = document.getElementById('date-from');
        const dateTo = document.getElementById('date-to');

        // Status filter
        const statusFilter = document.getElementById('status-filter');
        const resourceTypeFilter = document.getElementById('resource-type-filter');

        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', () => {
                this.dateFrom = dateFrom?.value || '';
                this.dateTo = dateTo?.value || '';
                this.statusFilter = statusFilter?.value || '';
                this.resourceTypeFilter = resourceTypeFilter?.value || '';
                this.currentPage = 1;
                this.loadHistoryData();
                this.renderActiveFilters();
                if (filterDropdown) {
                    filterDropdown.classList.add('hidden');
                }
            });
        }

        // Clear filters
        const clearFiltersBtn = document.getElementById('clear-filters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                this.clearFilters();
                this.renderActiveFilters();
                if (filterDropdown) {
                    filterDropdown.classList.add('hidden');
                }
            });
        }
        
        // Delete history (appears only at max) 
        const deleteBtn = document.getElementById('delete-history-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', async () => {
                const ok = confirm('Delete your old history records? Borrowed/active items are kept.');
                if (!ok) return;
                try {
                    const res = await fetch(this.getRoute('historyClear', '/student/history/clear'), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    const data = await res.json().catch(()=>({}));
                    if (!res.ok || data.success === false) throw new Error(data.message || 'Failed to delete history');
                    this.showNotification(data.message || 'History cleared successfully!', 'success');
                    this.currentPage = 1;
                    this.loadHistoryData();
                } catch (e) {
                    
                    this.showNotification('Failed to delete history.', 'error');
                }
            });
        }
        
        // Export button
        const exportBtn = document.getElementById('export-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => {
                this.exportHistory();
            });
        }
        
        // Modal event listeners
        this.setupModalEventListeners();
        
        // Action button event delegation
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-renew')) {
                const recordId = e.target.dataset.recordId;
                this.showRenewModal(recordId);
            } else if (e.target.matches('.btn-return')) {
                const recordId = e.target.dataset.recordId;
                this.showReturnModal(recordId);
            } else if (e.target.matches('.btn-details')) {
                const bookId = e.target.dataset.bookId;
                // Navigate to book details page instead of showing popup
                if (bookId) {
                    window.location.href = this.getBookUrl(bookId);
                }
            }
        });
        
        // Pagination event delegation
        document.addEventListener('click', (e) => {
            if (e.target.matches('.pagination-btn:not(.disabled)')) {
                e.preventDefault();
                const page = parseInt(e.target.dataset.page);
                if (page && page !== this.currentPage) {
                    this.loadPage(page);
                }
            }
        });
    }
    
    setupModalEventListeners() {
        // Book detail modal
        const bookModal = document.getElementById('book-detail-modal');
        if (bookModal) {
            bookModal.addEventListener('click', (e) => {
                if (e.target === bookModal) {
                    this.closeModal('book-detail-modal');
                }
            });
        }
        
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
        
        // Return modal
        const returnModal = document.getElementById('return-modal');
        if (returnModal) {
            returnModal.addEventListener('click', (e) => {
                if (e.target === returnModal) {
                    this.closeModal('return-modal');
                }
            });
        }
        
        const confirmReturnBtn = document.getElementById('confirm-return');
        const cancelReturnBtn = document.getElementById('cancel-return');
        
        if (confirmReturnBtn) {
            confirmReturnBtn.addEventListener('click', () => {
                this.confirmReturn();
            });
        }
        
        if (cancelReturnBtn) {
            cancelReturnBtn.addEventListener('click', () => {
                this.closeModal('return-modal');
            });
        }
        
        // Escape key to close modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal('book-detail-modal');
                this.closeModal('renew-modal');
                this.closeModal('return-modal');
            }
        });
    }
    
    clearFilters() {
        // Clear input fields
        document.getElementById('search-history').value = '';
        document.getElementById('date-from').value = '';
        document.getElementById('date-to').value = '';
        document.getElementById('status-filter').value = '';
        document.getElementById('resource-type-filter').value = '';
        
        // Reset filter values
        this.searchTerm = '';
        this.dateFrom = '';
        this.dateTo = '';
        this.statusFilter = '';
        this.resourceTypeFilter = '';
        this.currentPage = 1;
        
        // Reload data
        this.loadHistoryData();
        this.renderActiveFilters();
    }
    
    async loadHistoryData() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        
        // Don't show loading state, just fetch data quietly
        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                search: this.searchTerm,
                date_from: this.dateFrom,
                date_to: this.dateTo,
                status: this.statusFilter,
                resource_type: this.resourceTypeFilter
            });
            
            const historyApiUrl = new URL(this.getRoute('historyApi', '/student/history/api'), window.location.origin);
            historyApiUrl.search = params.toString();
            const response = await fetch(historyApiUrl.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                
                const errorText = await response.text();
                
                this.showEmptyState('Failed to load history');
                return;
            }
            
            const data = await response.json();
            
            
            this.renderHistoryData(data);
            
        } catch (error) {
            
            this.showEmptyState('Failed to load data');
        } finally {
            this.isLoading = false;
        }
    }
    
    showEmptyState(message = 'No records found') {
        const tableContainer = document.getElementById('history-table-container');
        const emptyState = document.getElementById('empty-state');
        const resultsCount = document.getElementById('results-count');
        const loadingIndicator = document.getElementById('loading-indicator');
        
        if (loadingIndicator) loadingIndicator.classList.add('hidden');
        if (tableContainer) tableContainer.classList.add('hidden');
        if (emptyState) emptyState.classList.remove('hidden');
        if (resultsCount) resultsCount.textContent = message;
    }

    renderActiveFilters() {
        const activeFilters = document.getElementById('history-active-filters');
        if (!activeFilters) return;

        const tags = [];

        if (this.searchTerm) {
            tags.push(`Search: ${this.escapeHtml(this.searchTerm)}`);
        }

        if (this.dateFrom) {
            tags.push(`From: ${this.escapeHtml(this.dateFrom)}`);
        }

        if (this.dateTo) {
            tags.push(`To: ${this.escapeHtml(this.dateTo)}`);
        }

        if (this.statusFilter) {
            const label = this.statusFilter === 'borrowed' ? 'Currently Borrowed' : 'Returned';
            tags.push(`Status: ${label}`);
        }

        if (this.resourceTypeFilter) {
            const labels = {
                book: 'Book',
                e_journal: 'E-Journal',
                thesis: 'E-Thesis'
            };
            tags.push(`Type: ${labels[this.resourceTypeFilter] || this.resourceTypeFilter}`);
        }

        activeFilters.innerHTML = tags.map(tag => `
            <span class="inline-flex items-center px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-medium">
                ${tag}
            </span>
        `).join('');
    }
    
    
    
    renderHistoryData(data) {
        const tbody = document.getElementById('history-tbody');
        const tableContainer = document.getElementById('history-table-container');
        const emptyState = document.getElementById('empty-state');
        const resultsCount = document.getElementById('results-count');
        
        // Always hide loading indicator first
        this.hideLoadingState();
        
        // Check if data is empty or invalid
        const hasData = data && data.records && data.records.data && data.records.data.length > 0;
        
        
        console.log('Data structure:', {
            hasData: !!data,
            hasRecords: !!(data && data.records),
            hasRecordsData: !!(data && data.records && data.records.data),
            recordsLength: data && data.records && data.records.data ? data.records.data.length : 0
        });
        
        if (!hasData) {
            if (tableContainer) tableContainer.classList.add('hidden');
            if (emptyState) emptyState.classList.remove('hidden');
            if (resultsCount) resultsCount.textContent = 'No records found';
            
            return;
        }
        
        if (tableContainer) tableContainer.classList.remove('hidden');
        if (emptyState) emptyState.classList.add('hidden');
        
        // Update results count
        if (resultsCount) {
            resultsCount.textContent = `Showing ${data.records.data.length} of ${data.records.total} records`;
        }
        
        // Toggle delete button if history reaches max
        const deleteBtn = document.getElementById('delete-history-btn');
        if (deleteBtn) {
            if ((data.records.total || 0) >= HistoryManager.MAX_HISTORY) {
                deleteBtn.classList.remove('hidden');
            } else {
                deleteBtn.classList.add('hidden');
            }
        }
        
        // Clear existing content and render records
        try {
            if (tbody) {
                tbody.innerHTML = '';
                
                
                
                // Render each record
                data.records.data.forEach((record, index) => {
                    
                    const row = this.createHistoryRow(record);
                    tbody.appendChild(row);
                });
                
                
            }
            
            // Render pagination
            this.renderPagination(data.records);
        } catch (error) {
            
            this.showEmptyState('Error displaying history');
        }
    }
    
    createHistoryRow(record) {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 transition-colors';
        
        // Determine record type and status
        const status = this.getRecordStatus(record);
        const statusClass = this.getStatusClass(status);
        const categoryClass = record.book?.category ? record.book.category.toLowerCase().replace(/\\s+/g, '') : '';
        const resourceType = (record.book?.resource_type || 'book').toLowerCase();
        const resourceTypeLabel = this.formatResourceType(resourceType);
        const resourceTypeIcon = this.getResourceTypeIcon(resourceType);
        const resourceTypeClass = this.getResourceTypeClass(resourceType);
        const coverUrl = this.getBookCoverUrl(record.book);
        const bookTitle = record.book?.title || 'Unknown';
        const coverMarkup = coverUrl
            ? `<img src="${this.escapeHtml(coverUrl)}" alt="${this.escapeHtml(bookTitle)} Cover" class="book-cover-image" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling?.classList.remove('hidden');">`
            : '';
        
        tr.innerHTML = `
            <td class="px-6 py-4">
                <div class="book-info">
                    <div class="book-cover-mini ${categoryClass}">
                        ${coverMarkup}
                        <i class="fas fa-book ${coverUrl ? 'hidden' : ''}"></i>
                    </div>
                    <div class="book-details">
                        <div class="book-title">${this.highlightSearch(bookTitle)}</div>
                        <div class="book-author">${record.book?.author || 'Unknown Author'}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="type-indicator ${resourceTypeClass}">
                    <i class="fas ${resourceTypeIcon}"></i>
                    ${resourceTypeLabel}
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="date-display">
                    ${this.formatDate(record.borrowed_date || record.reserved_date)}
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="date-display ${this.getDueDateClass(record)}">
                    ${record.due_date ? this.formatDate(record.due_date) : 'N/A'}
                    ${this.renderDaysCounter(record)}
                </div>
            </td>
            <td class="px-6 py-4">
                <span class="status-badge ${statusClass}">
                    ${status}
                </span>
                ${this.renderFineIndicator(record)}
            </td>
        `;
        
        return tr;
    }

    getResourceTypeClass(type) {
        switch (type) {
            case 'e_journal': return 'type-reserve';
            case 'thesis': return 'type-return';
            case 'book':
            default: return 'type-borrow';
        }
    }

    getResourceTypeIcon(type) {
        switch (type) {
            case 'e_journal': return 'fa-newspaper';
            case 'thesis': return 'fa-graduation-cap';
            case 'book':
            default: return 'fa-book';
        }
    }

    formatResourceType(type) {
        switch (type) {
            case 'e_journal': return 'E-Journal';
            case 'thesis': return 'E-Thesis';
            case 'book':
            default: return 'Book';
        }
    }
    
    getRecordStatus(record) {
        if (record.type === 'reservation') {
            if (record.status === 'active') return 'Reserved';
            if (record.status === 'expired') return 'Expired';
            if (record.status === 'fulfilled') return 'Fulfilled';
            return 'Cancelled';
        }
        
        // Auto-returned books should be marked as "Returned" since they were automatically processed
        if (record.returned_date) {
            return 'Returned';
        }
        
        return 'Borrowed';
    }
    
    getStatusClass(status) {
        switch (status.toLowerCase()) {
            case 'borrowed': return 'status-borrowed';
            case 'returned': return 'status-returned';
            case 'reserved': return 'status-reserved';
            case 'expired': return 'status-expired';
            case 'fulfilled': return 'status-returned';
            default: return 'status-expired';
        }
    }
    
    getTypeClass(type) {
        switch (type.toLowerCase()) {
            case 'borrow': return 'type-borrow';
            case 'return': return 'type-return';
            case 'reservation': return 'type-reserve';
            default: return '';
        }
    }
    
    getTypeIcon(type) {
        switch (type.toLowerCase()) {
            case 'borrow': return 'fa-download';
            case 'return': return 'fa-upload';
            case 'reservation': return 'fa-clock';
            default: return 'fa-book';
        }
    }
    
    formatRecordType(type) {
        switch (type.toLowerCase()) {
            case 'borrow': return 'Borrowed';
            case 'return': return 'Returned';
            case 'reservation': return 'Reserved';
            default: return 'Unknown';
        }
    }
    
    getDueDateClass(record) {
        if (!record.due_date || record.returned_date) return '';
        
        const dueDate = new Date(record.due_date);
        const today = new Date();
        const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
        
        if (daysDiff <= 3) return 'date-due-soon';
        return '';
    }
    
    renderDaysCounter(record) {
        if (!record.due_date || record.returned_date) return '';
        
        const dueDate = new Date(record.due_date);
        const today = new Date();
        const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
        
        let counterClass = 'days-normal';
        let text = '';
        
        if (daysDiff <= 3) {
            counterClass = 'days-due-soon';
            text = `${daysDiff} days left`;
        } else {
            text = `${daysDiff} days left`;
        }
        
        return `<div class="days-counter ${counterClass} mt-1">${text}</div>`;
    }
    
    renderFineIndicator(record) {
        if (record.fine_amount && record.fine_amount > 0) {
            return `<div class="fine-indicator mt-1">
                <i class="fas fa-exclamation-triangle"></i>
                Fine: $${record.fine_amount}
            </div>`;
        }
        return '';
    }
    
    renderActionButtons(record) {
        let buttons = [];
        
        const status = this.getRecordStatus(record);
        
        // Renew button for borrowed books
        if (status === 'Borrowed' && !this.isRenewalLimitReached(record)) {
            buttons.push(`
                <button class="action-btn btn-renew" data-record-id="${record.id}">
                    <i class="fas fa-refresh"></i> Renew
                </button>
            `);
        }
        
        // Return button for borrowed books
        if (status === 'Borrowed') {
            buttons.push(`
                <button class="action-btn btn-return" data-record-id="${record.id}">
                    <i class="fas fa-undo"></i> Return
                </button>
            `);
        }
        
        // Details button
        buttons.push(`
            <button class="action-btn btn-details" data-book-id="${record.book?.id}">
                <i class="fas fa-info-circle"></i> Details
            </button>
        `);
        
        return buttons.join('');
    }
    
    isRenewalLimitReached(record) {
        // Assuming max 2 renewals per book
        return (record.renewal_count || 0) >= 2;
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
    
    highlightSearch(text) {
        if (!this.searchTerm || !text) return this.escapeHtml(text);
        
        const regex = new RegExp(`(${this.escapeRegex(this.searchTerm)})`, 'gi');
        return this.escapeHtml(text).replace(regex, '<span class="search-highlight">$1</span>');
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }
    
    escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\\]\\\\]/g, '\\\\$&');
    }
    
    renderPagination(paginationData) {
        const paginationContainer = document.getElementById('pagination-container');
        if (!paginationContainer || !paginationData) return;
        
        const { current_page, last_page } = paginationData;
        
        if (last_page <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }
        
        let paginationHtml = '<div class="flex items-center justify-center space-x-1">';
        
        // Previous button
        const prevDisabled = current_page <= 1;
        paginationHtml += `
            <button class="pagination-btn ${prevDisabled ? 'disabled' : ''}" 
                    data-page="${current_page - 1}" 
                    ${prevDisabled ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>
        `;
        
        // Page numbers
        const startPage = Math.max(1, current_page - 2);
        const endPage = Math.min(last_page, current_page + 2);
        
        if (startPage > 1) {
            paginationHtml += `<button class="pagination-btn" data-page="1">1</button>`;
            if (startPage > 2) {
                paginationHtml += `<span class="px-2">...</span>`;
            }
        }
        
        for (let page = startPage; page <= endPage; page++) {
            const isActive = page === current_page;
            paginationHtml += `
                <button class="pagination-btn ${isActive ? 'active' : ''}" 
                        data-page="${page}">
                    ${page}
                </button>
            `;
        }
        
        if (endPage < last_page) {
            if (endPage < last_page - 1) {
                paginationHtml += `<span class="px-2">...</span>`;
            }
            paginationHtml += `<button class="pagination-btn" data-page="${last_page}">${last_page}</button>`;
        }
        
        // Next button
        const nextDisabled = current_page >= last_page;
        paginationHtml += `
            <button class="pagination-btn ${nextDisabled ? 'disabled' : ''}" 
                    data-page="${current_page + 1}" 
                    ${nextDisabled ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>
        `;
        
        paginationHtml += '</div>';
        paginationContainer.innerHTML = paginationHtml;
    }
    
    loadPage(page) {
        this.currentPage = page;
        this.loadHistoryData();
        
        // Scroll to top of table
        const tableContainer = document.getElementById('history-table-container');
        if (tableContainer) {
            tableContainer.scrollIntoView({ behavior: 'smooth' });
        }
    }
    
    showLoadingState() {
        const loadingIndicator = document.getElementById('loading-indicator');
        const tableContainer = document.getElementById('history-table-container');
        const emptyState = document.getElementById('empty-state');
        
        if (loadingIndicator) loadingIndicator.classList.remove('hidden');
        if (tableContainer) tableContainer.classList.add('hidden');
        if (emptyState) emptyState.classList.add('hidden');
    }
    
    hideLoadingState() {
        const loadingIndicator = document.getElementById('loading-indicator');
        if (loadingIndicator) loadingIndicator.classList.add('hidden');
    }
    
    showErrorState() {
        const tbody = document.getElementById('history-tbody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center">
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Error Loading History</h3>
                            <p class="text-gray-600 mb-4">Something went wrong while loading your history.</p>
                            <button onclick="window.historyManager.loadHistoryData()" 
                                    class="action-btn btn-details">
                                <i class="fas fa-refresh mr-1"></i> Try Again
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }
    }
    
    async showDetailsPopup(bookId) {
        if (!bookId) return;
        
        try {
            
            const response = await fetch(this.getBookDetailsUrl(bookId), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error('Failed to load book details');
            }
            
            const book = await response.json();
            this.renderDetailsToast(book);
            
        } catch (error) {
            
            this.showNotification('Failed to load book details.', 'error');
        }
    }
    
    renderDetailsToast(book) {
        const modalContent = document.querySelector('#book-detail-modal .modal-content');
        const categoryClass = book.category ? book.category.toLowerCase().replace(/\\s+/g, '') : '';
        const coverUrl = this.getBookCoverUrl(book);
        
        modalContent.innerHTML = `
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">${this.escapeHtml(book.title)}</h2>
                    <button onclick="window.historyManager.closeModal('book-detail-modal')" 
                            class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="flex flex-col md:flex-row gap-6">
                    <div class="book-cover-mini ${categoryClass} w-32 h-44 text-3xl">
                        ${coverUrl
                            ? `<img src="${this.escapeHtml(coverUrl)}" alt="${this.escapeHtml(book.title)} Cover" class="book-cover-image" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling?.classList.remove('hidden');">`
                            : ''}
                        <i class="fas fa-book ${coverUrl ? 'hidden' : ''}"></i>
                    </div>
                    
                    <div class="flex-1">
                        <h3 class="text-xl font-semibold mb-2">${this.escapeHtml(book.title)}</h3>
                        <p class="text-gray-600 text-lg mb-4">by ${this.escapeHtml(book.author)}</p>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <span class="text-sm text-gray-500">Category:</span>
                                <p class="font-medium">${book.category || 'N/A'}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">ISBN:</span>
                                <p class="font-medium">${book.isbn || 'N/A'}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">Published:</span>
                                <p class="font-medium">${book.published_year || 'N/A'}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">Pages:</span>
                                <p class="font-medium">${book.pages || 'N/A'}</p>
                            </div>
                        </div>
                        
                        ${book.description ? `
                            <div>
                                <span class="text-sm text-gray-500">Description:</span>
                                <p class="text-gray-700 mt-1">${this.escapeHtml(book.description)}</p>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }
    
    showRenewModal(recordId) {
        this.selectedRecordId = recordId;
        document.getElementById('renew-message').textContent = 
            'Are you sure you want to renew this book? This will extend your due date by 2 weeks.';
        document.getElementById('renew-modal').classList.remove('hidden');
    }
    
    showReturnModal(recordId) {
        this.selectedRecordId = recordId;
        document.getElementById('return-message').textContent = 
            'Are you sure you want to return this book? This action cannot be undone.';
        document.getElementById('return-modal').classList.remove('hidden');
    }
    
    async confirmRenew() {
        if (!this.selectedRecordId) return;
        
        try {
            const response = await fetch(this.getRenewUrl(this.selectedRecordId), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (response.ok) {
                this.showNotification('Book renewed successfully!', 'success');
                this.loadHistoryData();
            } else {
                this.showNotification(data.message || 'Failed to renew book', 'error');
            }
        } catch (error) {
            
            this.showNotification('Failed to renew book. Please try again.', 'error');
        } finally {
            this.closeModal('renew-modal');
        }
    }
    
    async confirmReturn() {
        if (!this.selectedRecordId) return;
        
        try {
            const response = await fetch(this.getReturnUrl(this.selectedRecordId), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (response.ok) {
                this.showNotification('Book returned successfully!', 'success');
                this.loadHistoryData();
            } else {
                this.showNotification(data.message || 'Failed to return book', 'error');
            }
        } catch (error) {
            
            this.showNotification('Failed to return book. Please try again.', 'error');
        } finally {
            this.closeModal('return-modal');
        }
    }
    
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
        }
        this.selectedRecordId = null;
    }
    
    async exportHistory() {
        try {
            const params = new URLSearchParams({
                search: this.searchTerm,
                date_from: this.dateFrom,
                date_to: this.dateTo,
                status: this.statusFilter,
                export: 'csv'
            });
            
            const exportUrl = new URL(this.getRoute('historyExport', '/student/history/export'), window.location.origin);
            exportUrl.search = params.toString();
            const response = await fetch(exportUrl.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'text/csv,application/csv',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error('Failed to export history');
            }
            
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `borrowing-history-${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            this.showNotification('History exported successfully!', 'success');
            
        } catch (error) {
            
            this.showNotification('Failed to export history. Please try again.', 'error');
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
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    
    window.historyManager = new HistoryManager();
    
    
    // Header Search Functionality
    initializeHeaderSearch();
    
});

// Header search functionality
function initializeHeaderSearch() {
    const headerSearchInput = document.getElementById('header-search');
    const headerSearchBtn = document.getElementById('header-search-btn');
    
    if (!headerSearchInput || !headerSearchBtn) return;
    
    // Function to perform search
    const performSearch = () => {
        const searchTerm = headerSearchInput.value.trim();
        if (searchTerm) {
            // Redirect to books page with search query
            const booksBase = (window.historyPageRoutes && window.historyPageRoutes.booksBase) || '/student/books';
            window.location.href = `${booksBase}?search=${encodeURIComponent(searchTerm)}`;
        }
    };
    
    // Search on button click
    headerSearchBtn.addEventListener('click', performSearch);
    
    // Search on Enter key
    headerSearchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            performSearch();
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
