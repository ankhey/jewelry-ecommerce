<?php
require_once __DIR__ . '/../includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = 'Shopping Cart';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

try {
    $db = getDB();
    
    // Get cart items with product details
    $cart_items = [];
    $total = 0;

    foreach ($_SESSION['cart'] as $key => $item) {
        // Get product details
        $stmt = $db->prepare("
            SELECT p.*, c.name as category_name, c.slug as category_slug
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = :id
        ");
        $stmt->execute([':id' => $item['product_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $variation = null;
            $variation_price_adjustment = 0;
            
            // Get variation details if exists
            if (isset($item['variation_id'])) {
                $stmt = $db->prepare("
                    SELECT * FROM product_variations 
                    WHERE id = :variation_id 
                    AND product_id = :product_id
                ");
                $stmt->execute([
                    ':variation_id' => $item['variation_id'],
                    ':product_id' => $item['product_id']
                ]);
                $variation = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($variation) {
                    $variation_price_adjustment = $variation['price_adjustment'];
                }
            }
            
            $item_total = ($product['price'] + $variation_price_adjustment) * $item['quantity'];
            $total += $item_total;
            
            $cart_items[] = [
                'key' => $key,
                'product' => $product,
                'variation' => $variation,
                'quantity' => $item['quantity'],
                'price' => $product['price'] + $variation_price_adjustment,
                'total' => $item_total
            ];
        }
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    $error = 'An error occurred while loading the cart';
}

// Add page-specific styles
$page_styles = <<<'HTML'
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .container {
            flex-grow: 1;
        }
        .cart-item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }
        .quantity-control {
            width: 40px;
        }
        .quantity-input {
            width: 60px;
            text-align: center;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .table td {
            vertical-align: middle;
        }
        .remove-item {
            padding: 0.25rem 0.5rem;
        }
        .remove-item i {
            font-size: 1rem;
        }
    </style>
HTML;

// Start output buffering
ob_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <?php echo $page_styles; ?>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container py-5">
        <h1 class="mb-4">Shopping Cart</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <div class="alert alert-info">
                Your cart is empty. <a href="/Glamour-shopv1.3/public/index.php">Continue shopping</a>
        </div>
    <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($item['product']['image_path'])): ?>
                                            <img src="/Glamour-shopv1.3/<?php echo htmlspecialchars($item['product']['image_path']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['product']['name']); ?>"
                                                 class="cart-item-image me-3">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 100px; height: 100px;">
                                                <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                            </div>
                                        <?php endif; ?>
                                        
                                            <div>
                                            <h5 class="mb-1">
                                                <a href="/Glamour-shopv1.3/public/product-detail.php?id=<?php echo $item['product']['id']; ?>" 
                                                   class="text-decoration-none text-dark">
                                                    <?php echo htmlspecialchars($item['product']['name']); ?>
                                                </a>
                                            </h5>
                                            <p class="text-muted mb-0">
                                                <?php echo htmlspecialchars($item['product']['category_name']); ?>
                                            </p>
                                            <?php if ($item['variation']): ?>
                                                <p class="text-muted mb-0">
                                                    Size: <?php echo htmlspecialchars($item['variation']['name']); ?>
                                        </p>
                                    <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>KES <?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <div class="input-group" style="max-width: 150px;">
                                        <button type="button" 
                                                class="btn btn-outline-secondary quantity-control decrease"
                                                data-key="<?php echo $item['key']; ?>">-</button>
                                        <input type="number" 
                                               class="form-control quantity-input" 
                                               value="<?php echo $item['quantity']; ?>"
                                               min="1"
                                               max="<?php echo $item['product']['stock']; ?>"
                                               data-key="<?php echo $item['key']; ?>">
                                        <button type="button" 
                                                class="btn btn-outline-secondary quantity-control increase"
                                                data-key="<?php echo $item['key']; ?>">+</button>
                                </div>
                                </td>
                                <td>KES <?php echo number_format($item['total'], 2); ?></td>
                                <td>
                                    <button type="button" 
                                            class="btn btn-danger btn-sm remove-item"
                                            data-key="<?php echo $item['key']; ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td><strong>KES <?php echo number_format($total, 2); ?></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-4">
                <a href="/Glamour-shopv1.3/public/index.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Continue Shopping
                </a>
                <button type="button" 
                        class="btn btn-success checkout-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#checkoutModal">
                    Proceed to Checkout <i class="bi bi-arrow-right"></i>
                                </button>
        </div>
    <?php endif; ?>
</div>

    <!-- Checkout Modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="checkoutModalLabel">Complete Your Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="checkoutForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="col-md-6">
                                <label for="pickup_location" class="form-label">Pickup Location *</label>
                                <select class="form-select" id="pickup_location" name="pickup_location" required>
                                    <option value="">Select a location</option>
                                    <option value="Kwa Shades">Kwa Shades</option>
                                    <option value="Gaturuturu">Gaturuturu</option>
                                    <option value="Mugumo-Ini">Mugumo-Ini</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="pickup_date" class="form-label">Pickup Date *</label>
                                <input type="date" class="form-control" id="pickup_date" name="pickup_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="pickup_time" class="form-label">Pickup Time *</label>
                                <input type="time" class="form-control" id="pickup_time" name="pickup_time" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="review" class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="review" name="review" rows="3" required></textarea>
                        </div>
                        <div class="alert alert-danger d-none" id="checkoutErrors"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="submitCheckout">Place Order</button>
                </div>
            </div>
        </div>
</div>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Quantity controls
        const quantityInputs = document.querySelectorAll('.quantity-input');
        const decreaseButtons = document.querySelectorAll('.decrease');
        const increaseButtons = document.querySelectorAll('.increase');
        const removeButtons = document.querySelectorAll('.remove-item');
        
        // Update quantity
        function updateQuantity(key, quantity) {
            fetch('/Glamour-shopv1.3/public/update-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    key: key,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to show updated cart
                    window.location.reload();
                } else {
                    alert(data.message || 'Error updating cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the cart');
            });
        }
        
        // Handle quantity input changes
        quantityInputs.forEach(input => {
            input.addEventListener('change', function() {
                const quantity = parseInt(this.value);
                const max = parseInt(this.getAttribute('max'));
                
                if (quantity < 1) {
                    this.value = 1;
                    updateQuantity(this.dataset.key, 1);
                } else if (quantity > max) {
                    this.value = max;
                    updateQuantity(this.dataset.key, max);
                } else {
                    updateQuantity(this.dataset.key, quantity);
                }
            });
        });
        
        // Handle decrease button clicks
        decreaseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = document.querySelector(`.quantity-input[data-key="${this.dataset.key}"]`);
                const currentValue = parseInt(input.value);
                
                if (currentValue > 1) {
                    input.value = currentValue - 1;
                    updateQuantity(this.dataset.key, currentValue - 1);
                }
            });
        });
        
        // Handle increase button clicks
        increaseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = document.querySelector(`.quantity-input[data-key="${this.dataset.key}"]`);
                const currentValue = parseInt(input.value);
                const max = parseInt(input.getAttribute('max'));
                
                if (currentValue < max) {
                    input.value = currentValue + 1;
                    updateQuantity(this.dataset.key, currentValue + 1);
                } else {
                    alert('Maximum quantity reached');
                }
            });
        });
        
        // Handle remove button clicks
        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                    fetch('/Glamour-shopv1.3/public/update-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                            key: this.dataset.key,
                            quantity: 0
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                            // Reload page to show updated cart
                            window.location.reload();
                    } else {
                        alert(data.message || 'Error removing item from cart');
                }
                })
                .catch(error => {
                    console.error('Error:', error);
                        alert('An error occurred while removing the item from cart');
                    });
                }
            });
        });

            // Set minimum date for pickup to tomorrow
            const pickupDate = document.getElementById('pickup_date');
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            pickupDate.min = tomorrow.toISOString().split('T')[0];

            // Handle checkout form submission
            document.getElementById('submitCheckout').addEventListener('click', function() {
                const form = document.getElementById('checkoutForm');
                const errorsDiv = document.getElementById('checkoutErrors');
                
                // Reset errors
                errorsDiv.classList.add('d-none');
                errorsDiv.innerHTML = '';
                
                // Validate form
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                
                // Get form data
                const formData = new FormData(form);
                
                // Submit order
                fetch('/Glamour-shopv1.3/public/checkout.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Redirect to WhatsApp
                        window.location.href = data.whatsapp_url;
                    } else {
                        // Show errors
                        errorsDiv.classList.remove('d-none');
                        errorsDiv.innerHTML = data.errors.join('<br>');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    errorsDiv.classList.remove('d-none');
                    errorsDiv.innerHTML = 'An error occurred while processing your order. Please try again.';
            });
        });
    });
</script>
</body>
</html>