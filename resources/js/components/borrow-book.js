// Borrow book page JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('auto-borrow-form');
    const processingStep = document.getElementById('processing-step');
    const redirectStep = document.getElementById('redirect-step');
    const borrowingLoader = document.getElementById('borrowing-loader');
    const successState = document.getElementById('success-state');

    // Calculate and display due date
    const today = new Date();
    const dueDate = new Date(today);
    dueDate.setDate(today.getDate() + 5);
    document.getElementById('due-date').textContent = dueDate.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric'
    });

    // Auto-borrow process simulation
    setTimeout(() => {
        // Update processing step to complete
        processingStep.innerHTML = `
            <i class="fas fa-check-circle text-green-500"></i>
            <span class="text-gray-600">Processing loan</span>
        `;

        // Start redirect step
        redirectStep.innerHTML = `
            <i class="fas fa-circle-notch fa-spin text-blue-500"></i>
            <span class="text-gray-600">Opening book</span>
        `;
    }, 1500);

    setTimeout(() => {
        // Complete redirect step
        redirectStep.innerHTML = `
            <i class="fas fa-check-circle text-green-500"></i>
            <span class="text-gray-600">Opening book</span>
        `;

        // Show success state
        borrowingLoader.classList.add('hidden');
        successState.classList.remove('hidden');
    }, 2500);

    // Submit form and redirect
    setTimeout(() => {
        // Submit the borrow form
        form.submit();
    }, 3000);
});
