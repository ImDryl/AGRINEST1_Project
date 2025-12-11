// Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get modal elements
    const loginModal = document.getElementById('loginModal');
    const registerModal = document.getElementById('registerModal');
    const body = document.body;

    // Open modal functions
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            body.classList.add('modal-open');
            // Focus first input
            setTimeout(() => {
                const firstInput = modal.querySelector('input[type="text"], input[type="email"]');
                if (firstInput) {
                    firstInput.focus();
                }
            }, 100);
        }
    }

    // Close modal functions
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            body.classList.remove('modal-open');
        }
    }

    // Close all modals
    function closeAllModals() {
        closeModal('loginModal');
        closeModal('registerModal');
    }

    // Open login modal
    document.querySelectorAll('[data-modal="login"]').forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            closeAllModals();
            openModal('loginModal');
        });
    });

    // Open register modal
    document.querySelectorAll('[data-modal="register"]').forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            closeAllModals();
            openModal('registerModal');
        });
    });

    // Switch between login and register
    document.querySelectorAll('[data-switch-modal]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetModal = this.getAttribute('data-switch-modal');
            closeAllModals();
            setTimeout(() => {
                openModal(targetModal);
            }, 300);
        });
    });

    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAllModals();
            }
        });
    });

    // Close modal on close button click
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal-overlay');
            if (modal) {
                closeModal(modal.id);
            }
        });
    });

    // Close modal on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });

    // Handle form submission - keep modal open on validation errors
    document.querySelectorAll('.modal-form form').forEach(form => {
        form.addEventListener('submit', function(e) {
            // Let the form submit normally
            // If there are errors, the page will reload with errors displayed
        });
    });

    // Check if we should auto-open a modal (e.g., on form error redirect)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('modal') === 'login') {
        openModal('loginModal');
    } else if (urlParams.get('modal') === 'register') {
        openModal('registerModal');
    }

    // Auto-open modal if there are form errors (detected via server-side script)
    // This is handled by inline script in the template
});

