// Book details page JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const getBookDetailsRoute = (name, fallback = '') => (window.bookDetailsRoutes && window.bookDetailsRoutes[name]) || fallback;
    const getBorrowUrl = (bookId) => `${getBookDetailsRoute('borrowBase', '/student/books').replace(/\/$/, '')}/${bookId}/borrow`;

    let activeBookId = null;

    // Listen for Borrow button clicks
    document.addEventListener('click', function(e) {
        const borrowBtn = e.target.matches('.btn-borrow') ? e.target : e.target.closest('.btn-borrow');
        if (borrowBtn) {
            e.preventDefault();
            const bookId = borrowBtn.dataset.bookId;
            const bookTitle = borrowBtn.dataset.bookTitle || '';
            const maxDays = parseInt(borrowBtn.dataset.borrowDays || 5, 10);

            if (bookId) {
                openBorrowPopup(bookId, bookTitle, maxDays);
            } else {
                showNotification('Unable to borrow book. Please refresh the page.', 'error');
            }
        }
    });

    // Open Borrow Popup Modal
    function openBorrowPopup(bookId, bookTitle, maxDays = 5) {
        activeBookId = bookId;

        const popup = document.getElementById('borrowPopup');
        const card = document.getElementById('borrowPopupCard');
        const titleElement = document.getElementById('borrowPopupTitle');
        const durationSelect = document.getElementById('borrowDurationSelect');
        const limitNote = document.getElementById('borrowLimitNote');

        if (titleElement) {
            titleElement.textContent = (bookTitle || 'Book Title').toUpperCase();
        }

        // Populate duration choices (1 up to librarian max limit)
        const maxLimit = Math.max(1, maxDays);
        if (durationSelect) {
            let optionsHtml = '';
            for (let i = 1; i <= maxLimit; i++) {
                const label = i === 1 ? '1 Day' : `${i} Days`;
                optionsHtml += `<option value="${i}">${label}</option>`;
            }
            durationSelect.innerHTML = optionsHtml;
            durationSelect.value = 1;
        }

        if (limitNote) {
            limitNote.textContent = `Select duration up to the librarian's set limit for this resource (${maxLimit} day(s)).`;
        }

        if (popup && card) {
            popup.classList.remove('hidden');
            setTimeout(() => {
                card.classList.remove('scale-95', 'opacity-0');
                card.classList.add('scale-100', 'opacity-100');
            }, 10);
            document.body.style.overflow = 'hidden';
        }
    }

    // Hide Borrow Popup Modal
    function hideBorrowPopup() {
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
        activeBookId = null;
    }

    // Cancel Button click listener
    const cancelBtn = document.getElementById('borrowPopupCancel');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            hideBorrowPopup();
        });
    }

    // Modal background backdrop click listener
    const popupModal = document.getElementById('borrowPopup');
    if (popupModal) {
        popupModal.addEventListener('click', function(e) {
            if (e.target === popupModal) {
                hideBorrowPopup();
            }
        });
    }

    // Escape key listener
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && popupModal && !popupModal.classList.contains('hidden')) {
            hideBorrowPopup();
        }
    });

    // Confirm Borrow click listener
    const confirmBtn = document.getElementById('borrowPopupConfirm');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            if (!activeBookId) return;

            const durationSelect = document.getElementById('borrowDurationSelect');
            const selectedDays = durationSelect ? parseInt(durationSelect.value || 1, 10) : 1;
            const confirmText = document.getElementById('borrowPopupConfirmText');

            confirmBtn.disabled = true;
            if (confirmText) confirmText.textContent = 'Borrowing...';

            try {
                const response = await fetch(getBorrowUrl(activeBookId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ borrow_days: selectedDays })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    hideBorrowPopup();
                    showNotification(data.message || `Book borrowed successfully for ${selectedDays} day(s)!`, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    showNotification(data.message || 'Failed to borrow book', 'error');
                }
            } catch (err) {
                showNotification('Failed to borrow book. Please try again.', 'error');
            } finally {
                confirmBtn.disabled = false;
                if (confirmText) confirmText.textContent = 'Confirm Borrow';
            }
        });
    }

    // Show Notification Toast
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-[10000] p-4 rounded-lg shadow-lg transition-all duration-300 ${
            type === 'success' ? 'bg-white text-blue-600 border border-blue-200' :
            'bg-white text-gray-800 border border-gray-200'
        }`;
        notification.innerHTML = `
            <div class="flex items-center gap-2 font-medium">
                <i class="fas ${type === 'success' ? 'fa-check-circle text-blue-600' : type === 'error' ? 'fa-exclamation-circle text-red-500' : 'fa-info-circle text-blue-500'}"></i>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
});
