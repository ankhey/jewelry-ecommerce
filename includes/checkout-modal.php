<?php
// This file contains the checkout modal that will be included in the layout template
// It will be available on all pages and can be triggered by "Buy Now" or checkout buttons
?>

<!-- Checkout Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="checkoutModalLabel">Checkout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="checkout-form" method="POST" action="/checkout.php">
                    <!-- Contact Information -->
                    <div class="form-section mb-4">
                        <h6 class="mb-3">Contact Information</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                    </div>

                    <!-- Pickup Information -->
                    <div class="form-section mb-4">
                        <h6 class="mb-3">Pickup Details</h6>
                        <div class="mb-3">
                            <label for="pickup_location" class="form-label">Pickup Location</label>
                            <select class="form-select" id="pickup_location" name="pickup_location" required>
                                <option value="">Select pickup location</option>
                                <option value="Gaturuturu">Gaturuturu</option>
                                <option value="Mugumo-ini">Mugumo-ini</option>
                                <option value="Kwa shades">Kwa shades</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="pickup_date" class="form-label">Pickup Date</label>
                                <input type="date" class="form-control" id="pickup_date" name="pickup_date" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="pickup_time" class="form-label">Pickup Time</label>
                                <input type="time" class="form-control" id="pickup_time" name="pickup_time" 
                                       min="09:00" max="17:00" required>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Review -->
                    <div class="form-section mb-4">
                        <h6 class="mb-3">Your Review</h6>
                        <div class="mb-3">
                            <label for="review" class="form-label">Please share your experience with us</label>
                            <textarea class="form-control" id="review" name="review" rows="3" required></textarea>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="form-section">
                        <h6 class="mb-3">Order Summary</h6>
                        <div id="order-summary">
                            <!-- Order summary will be loaded dynamically via AJAX -->
                            <div class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="place-order-btn">Place Order</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum time based on current time
    const timeInput = document.getElementById('pickup_time');
    const currentHour = new Date().getHours();
    if (currentHour >= 17) {
        // If current time is past 5 PM, set min time for next day
        timeInput.min = "09:00";
    } else if (currentHour < 9) {
        // If current time is before 9 AM, set min time to 9 AM
        timeInput.min = "09:00";
    } else {
        // Set min time to current hour
        timeInput.min = currentHour.toString().padStart(2, '0') + ":00";
    }

    // Load order summary when modal is shown
    const checkoutModal = document.getElementById('checkoutModal');
    checkoutModal.addEventListener('show.bs.modal', function() {
        // Load order summary via AJAX
        fetch('/get-cart-summary.php')
            .then(response => response.text())
            .then(html => {
                document.getElementById('order-summary').innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading cart summary:', error);
                document.getElementById('order-summary').innerHTML = 
                    '<div class="alert alert-danger">Error loading order summary. Please try again.</div>';
            });
    });

    // Handle form submission
    const form = document.getElementById('checkout-form');
    const placeOrderBtn = document.getElementById('place-order-btn');
    
    placeOrderBtn.addEventListener('click', function() {
        if (form.checkValidity()) {
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to WhatsApp
                    window.location.href = data.whatsapp_url;
                } else {
                    alert(data.errors.join('\n'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your order');
            });
        } else {
            form.reportValidity();
        }
    });
});
</script> 