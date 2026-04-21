// Book details page JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const getBookDetailsRoute = (name, fallback = '') => (window.bookDetailsRoutes && window.bookDetailsRoutes[name]) || fallback;
    const getBorrowUrl = (bookId) => `${getBookDetailsRoute('borrowBase', '/student/books').replace(/\/$/, '')}/${bookId}/borrow`;

    // Borrow button click - direct borrowing without confirmation
    document.addEventListener('click', async function(e) {
        if (e.target.matches('.btn-borrow') || e.target.closest('.btn-borrow')) {
            e.preventDefault();
            const button = e.target.matches('.btn-borrow') ? e.target : e.target.closest('.btn-borrow');
            const bookId = button?.dataset?.bookId;
            
            if (bookId && csrfToken) {
                // Borrow directly without confirmation
                borrowBookAutomatically(bookId, button);
            } else {
                console.error('Missing book ID or CSRF token', { bookId, csrfToken: !!csrfToken });
                showNotification('Unable to borrow book. Please refresh the page.', 'error');
            }
        }

        // Reserve button click
        if (e.target.matches('.btn-reserve') || e.target.closest('.btn-reserve')) {
            e.preventDefault();
            const bookId = (e.target.dataset.bookId || e.target.closest('.btn-reserve').dataset.bookId);
            if (bookId) {
                reserveBook(bookId);
            }
        }
    });

    // Automatic borrowing function
    async function borrowBookAutomatically(bookId, button) {
        const borrowButton = button || document.querySelector(`[data-book-id="${bookId}"]`);
        const originalText = borrowButton ? borrowButton.textContent : 'Borrow Book';

        // Show loading state
        if (borrowButton) {
            borrowButton.disabled = true;
            borrowButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Borrowing...';
        }

        try {
            const response = await fetch(getBorrowUrl(bookId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({})
            });

            const data = await response.json();

            if (response.ok && data.success) {
                showNotification(data.message || 'Book borrowed successfully for 1 day!', 'success');

                // Reload page to show updated status
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification(data.message || 'Failed to borrow book', 'error');
                // Reset button state on error
                if (borrowButton) {
                    borrowButton.disabled = false;
                    borrowButton.textContent = originalText;
                }
            }
        } catch (error) {
            console.error('Error borrowing book:', error);
            showNotification('Failed to borrow book. Please try again.', 'error');
            // Reset button state on error
            if (borrowButton) {
                borrowButton.disabled = false;
                borrowButton.textContent = originalText;
            }
        }
    }

    // Reserve functionality disabled for open access system
    async function reserveBook(bookId) {
        showNotification('Reservations are not needed! You can borrow books directly in our open access system.', 'info');
    }

    // Show notification function
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 bg-white text-gray-800 border border-gray-200`;
        notification.innerHTML = `
            <div class="flex items-center gap-2">
                <i class="fas ${type === 'success' ? 'fa-check-circle text-green-500' : type === 'error' ? 'fa-exclamation-circle text-red-500' : 'fa-info-circle text-blue-500'}"></i>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(notification);

        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
});
