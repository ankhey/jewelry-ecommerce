// Cart functionality
class Cart {
    constructor() {
        this.items = JSON.parse(localStorage.getItem('cart')) || [];
        this.updateCartCount();
    }

    addItem(productId, variationId, quantity = 1) {
        const existingItem = this.items.find(item => 
            item.productId === productId && item.variationId === variationId
        );

        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            this.items.push({ productId, variationId, quantity });
        }

        this.saveCart();
        this.updateCartCount();
        this.showNotification('Item added to cart');
    }

    removeItem(productId, variationId) {
        this.items = this.items.filter(item => 
            !(item.productId === productId && item.variationId === variationId)
        );
        this.saveCart();
        this.updateCartCount();
        this.showNotification('Item removed from cart');
    }

    updateQuantity(productId, variationId, quantity) {
        const item = this.items.find(item => 
            item.productId === productId && item.variationId === variationId
        );
        if (item) {
            item.quantity = Math.max(1, quantity);
            this.saveCart();
            this.updateCartCount();
        }
    }

    saveCart() {
        localStorage.setItem('cart', JSON.stringify(this.items));
    }

    updateCartCount() {
        const count = this.items.reduce((total, item) => total + item.quantity, 0);
        document.querySelector('.cart-count').textContent = count;
    }

    showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'toast-notification';
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 2000);
        }, 100);
    }
}

// Initialize cart
const cart = new Cart();

// Product variation selection
document.addEventListener('DOMContentLoaded', () => {
    const variationOptions = document.querySelectorAll('.variation-option');
    variationOptions.forEach(option => {
        option.addEventListener('click', () => {
            variationOptions.forEach(opt => opt.classList.remove('selected'));
            option.classList.add('selected');
        });
    });

    // Add to cart form submission
    const addToCartForm = document.querySelector('#add-to-cart-form');
    if (addToCartForm) {
        addToCartForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const productId = addToCartForm.querySelector('input[name="product_id"]').value;
            const variationId = addToCartForm.querySelector('input[name="variation_id"]').value;
            const quantity = parseInt(addToCartForm.querySelector('input[name="quantity"]').value);
            
            cart.addItem(productId, variationId, quantity);
        });
    }

    // Cart quantity controls
    const quantityControls = document.querySelectorAll('.quantity-control');
    quantityControls.forEach(control => {
        control.addEventListener('click', (e) => {
            const button = e.target.closest('.quantity-control');
            const input = button.parentElement.querySelector('input');
            const currentValue = parseInt(input.value);
            
            if (button.classList.contains('decrease')) {
                input.value = Math.max(1, currentValue - 1);
            } else {
                input.value = currentValue + 1;
            }
            
            // Trigger change event to update cart
            const event = new Event('change');
            input.dispatchEvent(event);
        });
    });

    // Buy Now buttons
    const buyNowButtons = document.querySelectorAll('.buy-now-btn');
    buyNowButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Get product details from the button's data attributes
            const productId = button.getAttribute('data-product-id');
            const variationId = button.getAttribute('data-variation-id');
            const quantity = parseInt(button.closest('form').querySelector('input[name="quantity"]').value) || 1;
            
            // Add to cart first
            cart.addItem(productId, variationId, quantity);
            
            // Show checkout modal
            const checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
            checkoutModal.show();
        });
    });
    
    // Checkout button in cart page
    const checkoutButton = document.querySelector('.checkout-btn');
    if (checkoutButton) {
        checkoutButton.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Show checkout modal
            const checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
            checkoutModal.show();
        });
    }

    // Search functionality
    const searchForm = document.querySelector('#search-form');
    if (searchForm) {
        const searchInput = searchForm.querySelector('input[type="search"]');
        let searchTimeout;

        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const query = searchInput.value.trim();
                if (query.length >= 2) {
                    window.location.href = `/search.php?q=${encodeURIComponent(query)}`;
                }
            }, 500);
        });
    }

    // Admin dashboard charts
    if (document.querySelector('#salesChart')) {
        const ctx = document.querySelector('#salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Sales',
                    data: [12, 19, 3, 5, 2, 3],
                    borderColor: '#007bff',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});

// Form validation
function validateForm(form) {
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// Image preview
function previewImage(input, previewElement) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            previewElement.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Toast notification styles
const style = document.createElement('style');
style.textContent = `
    .toast-notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #333;
        color: white;
        padding: 1rem 2rem;
        border-radius: 4px;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s ease;
    }
    .toast-notification.show {
        opacity: 1;
        transform: translateY(0);
    }
`;
document.head.appendChild(style); 