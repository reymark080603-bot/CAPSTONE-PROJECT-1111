// Book Management JavaScript Functions
let currentBookId = null;
let isEditing = false;

// DOM Elements - Initialize after DOM is loaded
let bookModal, modalContent, deleteModal, deleteModalContent, bookForm, modalTitle;
let addBookBtn, closeModal, cancelBtn, saveBtn, saveBtnText, saveBtnLoader;
let coverPhotoInput, coverPreview, uploadArea, previewArea, removeImageBtn;

// Toast notification elements
const toast = document.getElementById('toast');
const toastIcon = document.getElementById('toastIcon');
const toastMessage = document.getElementById('toastMessage');
const closeToast = document.getElementById('closeToast');

// Delete modal elements
const deleteBookTitle = document.getElementById('deleteBookTitle');
const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
const deleteBtnText = document.getElementById('deleteBtnText');
const deleteBtnLoader = document.getElementById('deleteBtnLoader');


// Helper functions for modal management
function preventBackgroundScroll() {
    document.body.style.overflow = 'hidden';
    document.body.style.position = 'fixed';
    document.body.style.top = `-${window.scrollY}px`;
    document.body.style.width = '100%';
}

function allowBackgroundScroll() {
    const scrollY = document.body.style.top;
    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.overflow = '';
    document.body.style.width = '';
    window.scrollTo(0, parseInt(scrollY || '0') * -1);
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeDOMElements();
    initializeEventListeners();
    initializeCoverPhotoPreview();
});

// Initialize all DOM elements
function initializeDOMElements() {
    bookModal = document.getElementById('bookModal');
    modalContent = document.getElementById('modalContent');
    deleteModal = document.getElementById('deleteModal');
    deleteModalContent = document.getElementById('deleteModalContent');
    bookForm = document.getElementById('bookForm');
    modalTitle = document.getElementById('modalTitle');
    addBookBtn = document.getElementById('addBookBtn');
    closeModal = document.getElementById('closeModal');
    cancelBtn = document.getElementById('cancelBtn');
    saveBtn = document.getElementById('saveBtn');
    saveBtnText = document.getElementById('saveBtnText');
    saveBtnLoader = document.getElementById('saveBtnLoader');
    
    // Cover photo elements
    coverPhotoInput = document.getElementById('coverInput');
    coverPreview = document.getElementById('coverPreview');
    uploadArea = document.getElementById('uploadArea');
    previewArea = document.getElementById('previewArea');
    removeImageBtn = document.getElementById('removeImage');
}

// Initialize all event listeners
function initializeEventListeners() {
    // Modal open/close events
    if (addBookBtn) addBookBtn.addEventListener('click', openAddBookModal);
    if (closeModal) closeModal.addEventListener('click', closeBookModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeBookModal);
    
    // Form submission
    if (bookForm) bookForm.addEventListener('submit', handleFormSubmit);
    
    // Delete modal events
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const closeToast = document.getElementById('closeToast');
    
    if (cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', closeDeleteModal);
    if (confirmDeleteBtn) confirmDeleteBtn.addEventListener('click', handleDeleteConfirm);
    if (closeToast) closeToast.addEventListener('click', hideToast);
    
    // Close modals when clicking outside (but not on modal content)
    window.addEventListener('click', function(event) {
        if (event.target === bookModal && !bookModal.classList.contains('hidden')) {
            closeBookModal();
        }
        if (event.target === deleteModal && !deleteModal.classList.contains('hidden')) {
            closeDeleteModal();
        }
    });
    
    // Prevent modal content clicks from closing modal
    if (modalContent) {
        modalContent.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    }
    
    if (deleteModalContent) {
        deleteModalContent.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    }
    
    // Helper functions moved to global scope below
}

// Initialize cover photo preview
function initializeCoverPhotoPreview() {
    if (!coverPhotoInput || !uploadArea || !previewArea) return;
    
    // Click upload area to select file
    uploadArea.addEventListener('click', function() {
        coverPhotoInput.click();
    });
    
    // Handle file selection
    coverPhotoInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            // Validate file
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file.');
                return;
            }
            
            if (file.size > 2 * 1024 * 1024) {
                alert('File size must be less than 2MB.');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                coverPreview.src = e.target.result;
                uploadArea.classList.add('hidden');
                previewArea.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Handle drag and drop
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('border-blue-400', 'bg-blue-50');
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('border-blue-400', 'bg-blue-50');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('border-blue-400', 'bg-blue-50');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            coverPhotoInput.files = files;
            coverPhotoInput.dispatchEvent(new Event('change'));
        }
    });
    
    // Remove image button
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function() {
            resetCoverPreview();
        });
    }
}

// Reset cover photo preview
function resetCoverPreview() {
    if (coverPhotoInput) {
        coverPhotoInput.value = '';
    }
    if (coverPreview) {
        coverPreview.src = '';
    }
    if (uploadArea) {
        uploadArea.classList.remove('hidden');
    }
    if (previewArea) {
        previewArea.classList.add('hidden');
    }
}

// Open add book modal
function openAddBookModal() {
    
    
    
    isEditing = false;
    currentBookId = null;
    
    if (modalTitle) modalTitle.textContent = 'Add New Book';
    if (saveBtnText) saveBtnText.textContent = 'Save Book';
    
    resetForm();
    showModal();
}

// Open edit book modal
function editBook(bookId) {
    isEditing = true;
    currentBookId = bookId;
    modalTitle.textContent = 'Edit Book';
    saveBtnText.textContent = 'Update Book';
    
    // Fetch book data and populate form
    fetchBookData(bookId);
    showModal();
}

// Fetch book data for editing
async function fetchBookData(bookId) {
    try {
        showLoading(true);
        const response = await fetch(`/librarian/books/${bookId}/edit`);
        
        if (!response.ok) {
            throw new Error('Failed to fetch book data');
        }
        
        // Since this returns a view, we need to get the book data via API
        const dataResponse = await fetch(`/librarian/books/${bookId}`);
        if (!dataResponse.ok) {
            throw new Error('Failed to fetch book data');
        }
        
        const data = await dataResponse.json();
        populateForm(data.book);
        
    } catch (error) {
        
        showToast('Error fetching book data', 'error');
        closeBookModal();
    } finally {
        showLoading(false);
    }
}

// Populate form with book data
function populateForm(book) {
    const form = document.getElementById('bookForm');
    
    // Populate text inputs
    const fields = ['title', 'author', 'publisher', 'published_year', 'pages', 'language', 'description'];
    fields.forEach(field => {
        const input = form.querySelector(`[name="${field}"]`);
        if (input && book[field] !== null) {
            input.value = book[field];
        }
    });
    
    // Populate select fields
    const selectFields = ['category', 'course'];
    selectFields.forEach(field => {
        const select = form.querySelector(`[name="${field}"]`);
        if (select && book[field]) {
            select.value = book[field];
        }
    });
    
    // Handle cover photo preview
    if (book.cover_photo) {
        coverPreview.src = `/${book.cover_photo}`;
        coverPreview.classList.remove('hidden');
        coverPlaceholder.classList.add('hidden');
    } else {
        resetCoverPreview();
    }
}

// Handle form submission
async function handleFormSubmit(event) {
    event.preventDefault();
    
    clearErrors();
    showLoading(true);
    
    const formData = new FormData(bookForm);
    
    try {
        const url = isEditing ? 
            `/librarian/books/${currentBookId}` : 
            '/librarian/books';
        
        const method = isEditing ? 'PUT' : 'POST';
        
        // For PUT requests, we need to add the method field
        if (isEditing) {
            formData.append('_method', 'PUT');
        }
        
        const response = await fetch(url, {
            method: 'POST', // Always POST for FormData with Laravel
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            closeBookModal();
            
            // Refresh the DataTable
            if (window.table) {
                window.table.draw();
            } else if ($('#booksTable').DataTable) {
                $('#booksTable').DataTable().draw();
            }
        } else {
            if (data.errors) {
                displayErrors(data.errors);
            } else {
                showToast(data.message || 'An error occurred', 'error');
            }
        }
        
    } catch (error) {
        
        showToast('An error occurred while saving the book', 'error');
    } finally {
        showLoading(false);
    }
}

// Display validation errors
function displayErrors(errors) {
    Object.keys(errors).forEach(field => {
        const errorElement = document.querySelector(`.error-message[data-field="${field}"]`);
        if (errorElement) {
            errorElement.textContent = errors[field][0];
            errorElement.classList.remove('hidden');
        }
    });
}

// Clear validation errors
function clearErrors() {
    document.querySelectorAll('.error-message').forEach(element => {
        element.textContent = '';
        element.classList.add('hidden');
    });
}

// Show/hide loading state
function showLoading(isLoading) {
    if (isLoading) {
        saveBtn.disabled = true;
        saveBtnText.classList.add('hidden');
        saveBtnLoader.classList.remove('hidden');
    } else {
        saveBtn.disabled = false;
        saveBtnText.classList.remove('hidden');
        saveBtnLoader.classList.add('hidden');
    }
}

// Show modal with animation
function showModal() {
    
    
    
    if (!bookModal || !modalContent) {
        
        return;
    }
    
    // Prevent background interaction
    preventBackgroundScroll();
    
    // Show modal backdrop
    bookModal.classList.remove('hidden');
    
    // Trigger animation after a brief delay to ensure DOM is ready
    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
    
    // Focus trap - keep focus within modal
    const focusableElements = modalContent.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    const firstFocusableElement = focusableElements[0];
    const lastFocusableElement = focusableElements[focusableElements.length - 1];
    
    // Focus first element
    if (firstFocusableElement) {
        setTimeout(() => firstFocusableElement.focus(), 100);
    }
    
    // Handle Tab key for focus trap
    function handleTabKey(e) {
        if (e.key === 'Tab') {
            if (e.shiftKey) {
                if (document.activeElement === firstFocusableElement) {
                    lastFocusableElement.focus();
                    e.preventDefault();
                }
            } else {
                if (document.activeElement === lastFocusableElement) {
                    firstFocusableElement.focus();
                    e.preventDefault();
                }
            }
        }
        
        // Handle Escape key
        if (e.key === 'Escape') {
            closeBookModal();
        }
    }
    
    document.addEventListener('keydown', handleTabKey);
    
    // Store the event listener so we can remove it later
    bookModal._keydownHandler = handleTabKey;
}

// Close book modal with animation
function closeBookModal() {
    // Start close animation
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    // Hide modal after animation completes
    setTimeout(() => {
        bookModal.classList.add('hidden');
        allowBackgroundScroll();
        resetForm();
        
        // Remove event listener
        if (bookModal._keydownHandler) {
            document.removeEventListener('keydown', bookModal._keydownHandler);
            bookModal._keydownHandler = null;
        }
    }, 300);
}

// Reset form
function resetForm() {
    bookForm.reset();
    clearErrors();
    resetCoverPreview();
    
    // Set default values
    const languageInput = bookForm.querySelector('[name="language"]');
    if (languageInput) {
        languageInput.value = 'English';
    }
    
    const copiesInput = bookForm.querySelector('[name="copies_total"]');
    if (copiesInput) {
        copiesInput.value = '1';
    }
}

// View book details
function viewBook(bookId) {
    window.open(`/librarian/books/${bookId}`, '_blank');
}

// Delete book
function deleteBook(bookId, bookTitle) {
    currentBookId = bookId;
    deleteBookTitle.textContent = `"${bookTitle}"`;
    
    // Prevent background interaction
    preventBackgroundScroll();
    
    // Show delete modal with animation
    deleteModal.classList.remove('hidden');
    
    setTimeout(() => {
        deleteModalContent.classList.remove('scale-95', 'opacity-0');
        deleteModalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
    
    // Focus on cancel button by default for safety
    setTimeout(() => {
        if (cancelDeleteBtn) {
            cancelDeleteBtn.focus();
        }
    }, 100);
    
    // Handle Escape key for delete modal
    function handleDeleteModalKeys(e) {
        if (e.key === 'Escape') {
            closeDeleteModal();
        }
    }
    
    document.addEventListener('keydown', handleDeleteModalKeys);
    deleteModal._keydownHandler = handleDeleteModalKeys;
}

// Handle delete confirmation
async function handleDeleteConfirm() {
    if (!currentBookId) return;
    
    try {
        showDeleteLoading(true);
        
        const response = await fetch(`/librarian/books/${currentBookId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        let data = null;
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            data = await response.json();
        } else {
            const text = await response.text();
            throw new Error(text || 'Non-JSON response');
        }
        
        if (data.success) {
            showToast(data.message, 'success');
            closeDeleteModal();
            
            // Refresh the DataTable
            if (window.table) {
                window.table.draw();
            } else if ($('#booksTable').DataTable) {
                $('#booksTable').DataTable().draw();
            }
        } else {
            showToast(data.message || 'Failed to delete book', 'error');
        }
        
    } catch (error) {
        showToast('Dili ma-delete ang book. I-check ang server error/CSRF.', 'error');
    } finally {
        showDeleteLoading(false);
    }
}

// Show/hide delete loading state
function showDeleteLoading(isLoading) {
    if (isLoading) {
        confirmDeleteBtn.disabled = true;
        deleteBtnText.classList.add('hidden');
        deleteBtnLoader.classList.remove('hidden');
    } else {
        confirmDeleteBtn.disabled = false;
        deleteBtnText.classList.remove('hidden');
        deleteBtnLoader.classList.add('hidden');
    }
}

// Close delete modal with animation
function closeDeleteModal() {
    // Start close animation
    deleteModalContent.classList.remove('scale-100', 'opacity-100');
    deleteModalContent.classList.add('scale-95', 'opacity-0');
    
    // Hide modal after animation completes
    setTimeout(() => {
        deleteModal.classList.add('hidden');
        allowBackgroundScroll();
        currentBookId = null;
        
        // Remove event listener
        if (deleteModal._keydownHandler) {
            document.removeEventListener('keydown', deleteModal._keydownHandler);
            deleteModal._keydownHandler = null;
        }
    }, 300);
}

// Show toast notification
function showToast(message, type = 'success') {
    toastMessage.textContent = message;
    
    // Set toast style based on type
    const iconClasses = {
        'success': 'bg-blue-100 text-blue-600',
        'error': 'bg-red-100 text-red-600',
        'warning': 'bg-yellow-100 text-yellow-600',
        'info': 'bg-blue-100 text-blue-600'
    };
    
    const icons = {
        'success': 'fas fa-check',
        'error': 'fas fa-times',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle'
    };
    
    // Reset classes
    toastIcon.className = `flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center mr-3 ${iconClasses[type] || iconClasses.success}`;
    toastIcon.innerHTML = `<i class="${icons[type] || icons.success}"></i>`;
    
    // Show toast
    if (toast._hideTimeout) {
        clearTimeout(toast._hideTimeout);
        toast._hideTimeout = null;
    }
    toast.classList.remove('opacity-0', 'pointer-events-none', '-translate-y-2');
    toast.classList.add('opacity-100');
    
    // Auto hide after 5 seconds
    toast._hideTimeout = setTimeout(() => {
        hideToast();
    }, 5000);
}

// Hide toast
function hideToast() {
    toast.classList.remove('opacity-100');
    toast.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');
}

// Export functions for global access
window.editBook = editBook;
window.viewBook = viewBook;
window.deleteBook = deleteBook;
