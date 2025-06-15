<?php
require_once __DIR__ . '/../includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header('Location: /Glamour-shopv1.3/public/index.php');
    exit();
}

try {
    $db = getDB();
    
    // Get product details with category information
    $stmt = $db->prepare("
        SELECT p.*, c.name as category_name, c.slug as category_slug
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = :id AND p.is_visible = 1
    ");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header('Location: /Glamour-shopv1.3/public/index.php');
        exit();
    }
    
    // Check if this is a ring product
    $is_ring = ($product['category_slug'] === 'rings');
    
    // Get product variations
    if ($is_ring) {
        // For rings, get size variations
        $stmt = $db->prepare("
            SELECT * FROM product_variations 
            WHERE product_id = :product_id 
            AND name LIKE 'Size %' 
            ORDER BY CAST(SUBSTRING(name, 6) AS UNSIGNED)
        ");
    } else {
        // For other products, get all variations
        $stmt = $db->prepare("
            SELECT * FROM product_variations 
            WHERE product_id = :product_id 
            ORDER BY name
        ");
    }
    
    $stmt->execute([':product_id' => $product_id]);
    $variations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get related products
    $stmt = $db->prepare("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.category_id = :category_id 
        AND p.id != :product_id 
        AND p.is_visible = 1
        ORDER BY RAND()
        LIMIT 4
    ");
    $stmt->execute([
        ':category_id' => $product['category_id'],
        ':product_id' => $product_id
    ]);
    $related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

$page_title = $product['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .product-detail {
            max-height: 500px;
            object-fit: contain;
        }
        .ring-size-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        .ring-size-option {
            display: inline-block;
            width: 60px;
            height: 60px;
            line-height: 1.2;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s;
            padding: 5px;
            font-size: 1.1rem;
            font-weight: 500;
            background-color: #f8f9fa;
        }
        .ring-size-option:hover:not(.out-of-stock) {
            border-color: #007bff;
            background-color: #e7f1ff;
        }
        .ring-size-option.selected {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .ring-size-option.out-of-stock {
            background-color: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
            text-decoration: line-through;
            opacity: 0.7;
        }
        .ring-size-option:not(.out-of-stock) {
            background-color: #e7f1ff;
            border-color: #007bff;
        }
        .ring-size-option small {
            font-size: 0.7rem;
            display: block;
            margin-top: 2px;
        }
        .product-card {
            height: 100%;
            transition: transform 0.2s;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container py-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/Glamour-shopv1.3/public/index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="/Glamour-shopv1.3/public/index.php?category=<?php echo $product['category_id']; ?>"><?php echo $product['category_name']; ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo $product['name']; ?></li>
            </ol>
        </nav>

        <div class="row">
            <!-- Product Image -->
            <div class="col-md-6">
                <?php if (!empty($product['image_path'])): ?>
                    <img src="/Glamour-shopv1.3/<?php echo htmlspecialchars($product['image_path']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         class="img-fluid product-detail">
                <?php else: ?>
                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 400px;">
                        <i class="bi bi-image text-muted" style="font-size: 5rem;"></i>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Info -->
            <div class="col-md-6">
                <h1 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="text-muted mb-3"><?php echo htmlspecialchars($product['category_name']); ?></p>
                
                <div class="mb-4">
                    <span class="h3 text-primary">KES <?php echo number_format($product['price'], 2); ?></span>
                    <span class="badge <?php echo $product['stock'] > 0 ? 'bg-success' : 'bg-danger'; ?> ms-2">
                        <?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                    </span>
                </div>

                <div class="mb-4">
                    <h5>Description</h5>
                    <p class="text-muted" style="font-family: Roboto, -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;">
                        <?php 
                            // Remove any HTML tags but preserve line breaks
                            $description = strip_tags($product['description'], '<br><p>');
                            // Convert line breaks to <br> tags
                            $description = nl2br($description);
                            echo $description;
                        ?>
                    </p>
                </div>

                <?php if ($product['stock'] > 0): ?>
                    <form id="add-to-cart-form" class="mb-4">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        
                        <?php if ($is_ring && !empty($variations)): ?>
                            <div class="mb-4">
                                <label class="form-label">Select Ring Size</label>
                                <div class="ring-size-selector">
                                    <?php foreach ($variations as $size): ?>
                                        <div class="ring-size-option <?php echo $size['stock'] <= 0 ? 'out-of-stock' : ''; ?>"
                                             data-variation-id="<?php echo $size['id']; ?>"
                                             data-stock="<?php echo $size['stock']; ?>">
                                            <?php echo htmlspecialchars($size['name']); ?>
                                            <small><?php echo $size['stock']; ?> in stock</small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="variation_id" id="selected-variation" required>
                                <div class="invalid-feedback">Please select a ring size</div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <div class="input-group" style="max-width: 150px;">
                                <button type="button" class="btn btn-outline-secondary quantity-control decrease">-</button>
                                <input type="number" class="form-control text-center" name="quantity" 
                                       id="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" required>
                                <button type="button" class="btn btn-outline-secondary quantity-control increase">+</button>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <button type="submit" class="btn btn-primary btn-lg w-100">Add to Cart</button>
                            </div>
                            <div class="col-6">
                                <button type="button" 
                                        class="btn btn-success btn-lg w-100" 
                                        id="buy-now"
                                        data-bs-toggle="modal"
                                        data-bs-target="#checkoutModal"
                                        data-product-id="<?php echo $product['id']; ?>"
                                        data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                        data-product-price="<?php echo $product['price']; ?>"
                                        data-product-image="<?php echo htmlspecialchars($product['image_path']); ?>">
                                    Buy Now
                                </button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>

                <!-- Additional Info -->
                <div class="mt-4">
                    <h5>Product Details</h5>
                    <ul class="list-unstyled">
                        <li><strong>SKU:</strong> <?php echo $product['id']; ?></li>
                        <li><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?></li>
                        <li><strong>Stock:</strong> <?php echo $product['stock']; ?> units</li>
                        <li><strong>Added:</strong> <?php echo date('F j, Y', strtotime($product['created_at'])); ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <div class="row mt-5">
            <div class="col-12">
                <h2 class="mb-4">Related Products</h2>
                <div class="row g-4">
                    <?php foreach ($related_products as $related): ?>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card product-card">
                                <div class="position-relative">
                                    <a href="/Glamour-shopv1.3/public/product-detail.php?id=<?php echo $related['id']; ?>" 
                                       class="text-decoration-none">
                                        <?php if (!empty($related['image_path'])): ?>
                                            <img src="/Glamour-shopv1.3/<?php echo htmlspecialchars($related['image_path']); ?>" 
                                                 alt="<?php echo htmlspecialchars($related['name']); ?>"
                                                 class="product-image">
                                        <?php else: ?>
                                            <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                    
                                    <?php if ($related['category_name']): ?>
                                        <span class="position-absolute top-0 end-0 m-2 badge bg-light text-dark">
                                            <?php echo htmlspecialchars($related['category_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($related['stock'] <= 0): ?>
                                        <div class="position-absolute top-50 start-50 translate-middle bg-danger text-white px-3 py-2 rounded">
                                            Out of Stock
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="/Glamour-shopv1.3/public/product-detail.php?id=<?php echo $related['id']; ?>" 
                                           class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($related['name']); ?>
                                        </a>
                                    </h5>
                                    <p class="card-text text-muted">
                                        <?php echo substr(strip_tags($related['description']), 0, 100) . '...'; ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="h5 mb-0">KES <?php echo number_format($related['price'], 2); ?></span>
                                    </div>
                                    <?php if ($related['stock'] > 0): ?>
                                        <div class="d-grid gap-2">
                                            <button type="button" 
                                                    class="btn btn-outline-primary btn-add-to-cart"
                                                    onclick="addToCart(<?php echo $related['id']; ?>)">
                                                <i class="bi bi-cart-plus"></i> Add to Cart
                                            </button>
                                            <a href="/Glamour-shopv1.3/public/product-detail.php?id=<?php echo $related['id']; ?>" 
                                               class="btn btn-primary">
                                                <i class="bi bi-eye"></i> View Details
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary w-100" disabled>
                                            Out of Stock
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const addToCartForm = document.getElementById('add-to-cart-form');
        const ringSizeOptions = document.querySelectorAll('.ring-size-option');
        const selectedVariationInput = document.getElementById('selected-variation');
        const quantityInput = document.getElementById('quantity');

        // Handle ring size selection
        if (ringSizeOptions.length > 0) {
            ringSizeOptions.forEach(option => {
                option.addEventListener('click', function() {
                    if (this.classList.contains('out-of-stock')) return;
                    
                    // Remove selected class from all options
                    ringSizeOptions.forEach(opt => opt.classList.remove('selected'));
                    
                    // Add selected class to clicked option
                    this.classList.add('selected');
                    
                    // Update hidden input
                    selectedVariationInput.value = this.dataset.variationId;
                    
                    // Update quantity max based on selected variation's stock
                    const stock = parseInt(this.dataset.stock);
                    quantityInput.max = stock;
                    if (parseInt(quantityInput.value) > stock) {
                        quantityInput.value = stock;
                    }
                });
            });
        }

        // Quantity controls
        const decreaseBtn = document.querySelector('.decrease');
        const increaseBtn = document.querySelector('.increase');
        
        if (decreaseBtn && increaseBtn && quantityInput) {
            decreaseBtn.addEventListener('click', function() {
                let value = parseInt(quantityInput.value);
                if (value > 1) {
                    quantityInput.value = value - 1;
                }
            });
            
            increaseBtn.addEventListener('click', function() {
                let value = parseInt(quantityInput.value);
                let max = parseInt(quantityInput.getAttribute('max'));
                if (value < max) {
                    quantityInput.value = value + 1;
                } else {
                    alert('Maximum quantity reached');
                }
            });
        }

        // Add to cart form submission
        addToCartForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate ring size selection if it's a ring product
            if (ringSizeOptions.length > 0 && !selectedVariationInput.value) {
                alert('Please select a ring size');
                return;
            }
            
            const formData = new FormData(this);
            const data = {};
            for (const [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            fetch('/Glamour-shopv1.3/public/add-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to show updated cart
                    window.location.reload();
                } else {
                    alert(data.message || 'Error adding product to cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the product to cart');
            });
        });

        // Buy Now button click handler
        const buyNowBtn = document.getElementById('buy-now');
        if (buyNowBtn) {
            buyNowBtn.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const quantity = document.getElementById('quantity').value;
                const variationId = document.querySelector('input[name="variation_id"]')?.value;

                // Add to cart with hold status
                fetch('/Glamour-shopv1.3/public/add-to-cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: quantity,
                        variation_id: variationId,
                        is_hold: true
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // The checkout modal will be shown automatically via data-bs-toggle
                    } else {
                        alert(data.message || 'Error adding item to cart');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing your request');
                });
            });
        }

        // Handle checkout form submission
        const submitCheckoutBtn = document.getElementById('submitCheckout');
        if (submitCheckoutBtn) {
            submitCheckoutBtn.addEventListener('click', function() {
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
        }

        // Set minimum date for pickup to tomorrow
        const pickupDate = document.getElementById('pickup_date');
        if (pickupDate) {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            pickupDate.min = tomorrow.toISOString().split('T')[0];
        }
    });
    
    // Function to add product to cart from related products
    function addToCart(productId) {
        fetch('/Glamour-shopv1.3/public/add-to-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update cart count in header
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    cartCount.textContent = data.cart_count;
                }
                
                // Show success message
                alert('Product added to cart successfully!');
            } else {
                alert(data.message || 'Error adding product to cart');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding the product to cart');
        });
    }
    </script>

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
                            <textarea class="form-control" id="review" name="review" rows="3"></textarea>
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
</body>
</html> 