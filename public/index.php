<?php
require_once __DIR__ . '/../includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $db = getDB();

// Get category filter
    $category = isset($_GET['category']) ? $_GET['category'] : null;

// Get sort parameters
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_desc';
$sort_parts = explode('_', $sort);
$sort_field = $sort_parts[0];
$sort_direction = $sort_parts[1];

// Build query
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.is_visible = 1";

if ($category) {
        $query .= " AND c.id = :category";
}

// Add sorting
switch ($sort_field) {
    case 'price':
        $query .= " ORDER BY p.price " . ($sort_direction === 'asc' ? 'ASC' : 'DESC');
        break;
    case 'name':
        $query .= " ORDER BY p.name " . ($sort_direction === 'asc' ? 'ASC' : 'DESC');
        break;
    default:
        $query .= " ORDER BY p.created_at DESC";
}

$stmt = $db->prepare($query);
if ($category) {
    $stmt->bindValue(':category', $category);
}
$stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
    $stmt = $db->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

$page_title = 'Welcome to ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
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
            height: 300px;
            object-fit: cover;
        }
        .product-category {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.9);
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        .product-price {
            font-size: 1.25rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .out-of-stock {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(220, 53, 69, 0.9);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        .btn-add-to-cart {
            border-radius: 20px;
            padding: 6px 16px;
            font-size: 0.9rem;
        }
        .btn-buy-now {
            border-radius: 20px;
            padding: 6px 16px;
            font-size: 0.9rem;
        }
        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
        }
        .card-body {
            padding: 1rem;
        }
        .card-text {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        .card-title {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main>
        <div class="product-layout">
            <!-- Mobile Filter Controls -->
            <div class="mobile-filter-controls">
                <div class="row g-2">
                    <div class="col-6">
                        <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="bi bi-funnel"></i> Filters
                        </button>
                    </div>
                    <div class="col-6">
                        <form action="" method="GET" id="mobile-sort-form">
                        <?php if ($category): ?>
                                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                        <?php endif; ?>
                            <select name="sort" class="form-select mobile-sort-dropdown" onchange="this.form.submit()">
                            <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                            <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                            <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                            <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                                <option value="created_desc" <?php echo $sort === 'created_desc' ? 'selected' : ''; ?>>Newest First</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>

            <!-- Filters Sidebar (Desktop) -->
            <div class="filter-sidebar">
                <div class="filter-section">
                    <h5>Categories</h5>
                    <div class="filter-options">
                        <a href="index.php" class="filter-option <?php echo !$category ? 'active' : ''; ?>">
                            All Products
                        </a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="index.php?category=<?php echo $cat['id']; ?>" 
                               class="filter-option <?php echo $category == $cat['id'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Product Grid Container -->
            <div class="product-grid-container">
                <!-- Sort Controls (Desktop) -->
            <div class="sort-controls">
                <form action="" method="GET" id="sort-form" class="d-flex align-items-center">
                    <?php if ($category): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                    <?php endif; ?>
                    <label for="sort" class="me-2">Sort by:</label>
                    <select name="sort" class="form-select" style="width: auto;" onchange="this.form.submit()">
                        <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                        <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                        <option value="created_desc" <?php echo $sort === 'created_desc' ? 'selected' : ''; ?>>Newest First</option>
                    </select>
                </form>
            </div>
        <!-- Product Grid -->
                <div class="product-grid">
                    <?php if (empty($products)): ?>
                        <div class="no-products-message">
                            <?php
                            if ($category) {
                                // Get the category name
                                $category_name = '';
                                foreach ($categories as $cat) {
                                    if ($cat['id'] == $category) {
                                        $category_name = $cat['name'];
                                        break;
                                    }
                                }
                                echo "<h3>No {$category_name} Available</h3>";
                                echo "<p>We currently don't have any {$category_name} in our collection. Please check back later or browse our other categories.</p>";
                            } else {
                                echo "<h3>No Products Available</h3>";
                                echo "<p>We currently don't have any products in our collection. Please check back later.</p>";
                            }
                            ?>
                            <a href="index.php" class="btn btn-primary mt-3">View All Products</a>
                        </div>
                    <?php else: ?>
                <?php foreach ($products as $product): ?>
                            <div class="card product-card">
                                <div class="position-relative">
                                    <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                                       class="text-decoration-none">
                                        <?php if (!empty($product['image_path'])): ?>
                                            <img src="../<?php echo htmlspecialchars($product['image_path']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                 class="product-image">
                                        <?php else: ?>
                                            <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                    
                                    <?php if ($product['category_name']): ?>
                                        <span class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($product['stock'] <= 0): ?>
                                        <div class="out-of-stock">Out of Stock</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                                           class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h5>
                                    <p class="card-text text-muted">
                                        <?php 
                                            $words = explode(' ', strip_tags($product['description']));
                                            $short_desc = implode(' ', array_slice($words, 0, 5));
                                            echo $short_desc . (count($words) > 5 ? '...' : '');
                                        ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="product-price">KES <?php echo number_format($product['price'], 2); ?></span>
                                    </div>
                                    <?php if ($product['stock'] > 0): ?>
                                        <div class="d-grid gap-2">
                                            <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                                               class="btn btn-outline-primary">
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
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Filter Modal for Mobile -->
        <div class="modal fade filter-modal" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="filterModalLabel">Filter Products</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    <div class="modal-body">
                        <div class="filter-section">
                            <h5>Categories</h5>
                            <div class="filter-options">
                                <a href="index.php" class="filter-option <?php echo !$category ? 'active' : ''; ?>">
                                    All Products
                                </a>
                                <?php foreach ($categories as $cat): ?>
                                    <a href="index.php?category=<?php echo $cat['id']; ?>" 
                                       class="filter-option <?php echo $category == $cat['id'] ? 'active' : ''; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </a>
                    <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
            </div>
        </div>
    </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    
    <!-- Ring Size Modal -->
    <div class="modal fade" id="ringSizeModal" tabindex="-1" aria-labelledby="ringSizeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ringSizeModalLabel">Select Ring Size</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="ringSizeOptions" class="ring-size-selector"></div>
                    <div class="alert alert-danger d-none mt-3" id="sizeError">Please select a size.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="addSizeToCartBtn">Add to Cart</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Buy Now Modal -->
    <div class="modal fade" id="buyNowModal" tabindex="-1" aria-labelledby="buyNowModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="buyNowModalLabel">Complete Your Order (Buy Now)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="buyNowProductDetails">
                        <!-- Product details will be loaded here -->
                    </div>
                    <hr>
                    <form id="buyNowForm">
                        <input type="hidden" name="product_id" id="buyNowProductId">
                        <input type="hidden" name="variation_id" id="buyNowVariationId">
                        <input type="hidden" name="quantity" value="1"> <!-- Default quantity to 1 for Buy Now -->

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="buyNowName" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="buyNowName" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="buyNowEmail" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="buyNowEmail" name="email" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="buyNowPhone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="buyNowPhone" name="phone" required>
                            </div>
                            <div class="col-md-6">
                                <label for="buyNowPickupLocation" class="form-label">Pickup Location *</label>
                                <select class="form-select" id="buyNowPickupLocation" name="pickup_location" required>
                                    <option value="">Select a location</option>
                                    <option value="Kwa Shades">Kwa Shades</option>
                                    <option value="Gaturuturu">Gaturuturu</option>
                                    <option value="Mugumo-Ini">Mugumo-Ini</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="buyNowPickupDate" class="form-label">Pickup Date *</label>
                                <input type="date" class="form-control" id="buyNowPickupDate" name="pickup_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="buyNowPickupTime" class="form-label">Pickup Time *</label>
                                <input type="time" class="form-control" id="buyNowPickupTime" name="pickup_time" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="buyNowReview" class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="buyNowReview" name="review" rows="3"></textarea>
                        </div>
                        <div class="alert alert-danger d-none" id="buyNowErrors"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="submitBuyNow">Place Order</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to add product to cart
        function addToCart(productId, variationId = null, quantity = 1) {
            const data = { product_id: productId, quantity: quantity };
            if (variationId) {
                data.variation_id = variationId;
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
                    // Update cart count
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
                alert('Error adding product to cart');
            });
        }

        // Handle opening the ring size modal
        const ringSizeModal = document.getElementById('ringSizeModal');
        ringSizeModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Button that triggered the modal
            const productId = button.dataset.productId; // Extract info from data-* attributes
            const modalBody = ringSizeModal.querySelector('.modal-body #ringSizeOptions');
            const sizeErrorDiv = ringSizeModal.querySelector('.modal-body #sizeError');
            const addSizeToCartBtn = ringSizeModal.querySelector('.modal-footer #addSizeToCartBtn');

            // Clear previous options and errors
            modalBody.innerHTML = '';
            sizeErrorDiv.classList.add('d-none');
            addSizeToCartBtn.dataset.productId = productId;
            addSizeToCartBtn.dataset.variationId = ''; // Reset selected variation
            addSizeToCartBtn.disabled = true; // Disable button initially

            // Fetch ring sizes
            fetch(`/Glamour-shopv1.3/public/get-ring-sizes.php?product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.variations.length > 0) {
                        data.variations.forEach(variation => {
                            const sizeOption = document.createElement('div');
                            sizeOption.classList.add('ring-size-option');
                            if (variation.stock <= 0) {
                                sizeOption.classList.add('out-of-stock');
                            } else {
                                sizeOption.classList.add('available'); // Add a class for available sizes
                                sizeOption.dataset.variationId = variation.id;
                                sizeOption.dataset.stock = variation.stock;
                            }
                            sizeOption.innerHTML = `
                                ${htmlspecialchars(variation.name)}
                                <small>${variation.stock} in stock</small>
                            `;
                            modalBody.appendChild(sizeOption);
                        });

                        // Add event listeners to new size options
                        modalBody.querySelectorAll('.ring-size-option.available').forEach(option => {
                            option.addEventListener('click', function() {
                                // Remove selected class from all available options
                                modalBody.querySelectorAll('.ring-size-option.available').forEach(opt => opt.classList.remove('selected'));

                                // Add selected class to clicked option
                                this.classList.add('selected');

                                // Store selected variation ID and enable button
                                addSizeToCartBtn.dataset.variationId = this.dataset.variationId;
                                addSizeToCartBtn.disabled = false;
                            });
                        });
                    } else {
                        modalBody.innerHTML = '<p>No size variations available for this ring.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching ring sizes:', error);
                    modalBody.innerHTML = '<p class="text-danger">Error loading sizes.</p>';
                });
        });

        // Handle adding to cart from the modal
        document.getElementById('addSizeToCartBtn').addEventListener('click', function() {
            const productId = this.dataset.productId;
            const variationId = this.dataset.variationId;
            const quantity = 1; // Default quantity to 1 when adding from card
            const sizeErrorDiv = document.getElementById('sizeError');

            if (!variationId) {
                sizeErrorDiv.classList.remove('d-none');
                return;
            }

            // Hide error and add to cart
            sizeErrorDiv.classList.add('d-none');
            addToCart(productId, variationId, quantity);

            // Close modal
            const modal = bootstrap.Modal.getInstance(ringSizeModal);
            modal.hide();
        });

        // Helper function for HTML escaping (since we are using innerHTML)
        function htmlspecialchars(str) {
            return str.replace(/&/g, '&amp;')
                      .replace(/</g, '&lt;')
                      .replace(/>/g, '&gt;')
                      .replace(/"/g, '&quot;')
                      .replace(/'/g, '&#039;');
        }

        // Handle opening the Buy Now modal
        const buyNowModal = document.getElementById('buyNowModal');
        buyNowModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Button that triggered the modal
            if (!button) {
                console.error('No button found that triggered the modal');
                return;
            }
            const productId = button.dataset.productId; // Extract product ID
            const productCategorySlug = button.dataset.productCategorySlug; // Extract category slug
            const modalBodyDetails = buyNowModal.querySelector('#buyNowProductDetails');
            const buyNowProductIdInput = buyNowModal.querySelector('#buyNowProductId');
            const buyNowVariationIdInput = buyNowModal.querySelector('#buyNowVariationId');
            const sizeErrorDiv = buyNowModal.querySelector('#buyNowErrors');
            const submitBuyNowBtn = buyNowModal.querySelector('#submitBuyNow');

            // Clear previous content and reset state
            modalBodyDetails.innerHTML = '';
            sizeErrorDiv.classList.add('d-none');
            sizeErrorDiv.innerHTML = '';
            buyNowProductIdInput.value = productId;
            buyNowVariationIdInput.value = ''; // Reset variation
            submitBuyNowBtn.disabled = false; // Enable button initially

            // Fetch product details and variations if it's a ring
            fetch(`/Glamour-shopv1.3/public/get-product-details.php?product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const product = data.product;
                        let detailsHtml = `
                            <h5>${htmlspecialchars(product.name)}</h5>
                            <p class="text-muted">KES ${parseFloat(product.price).toFixed(2)}</p>
                        `;

                        if (product.is_ring && data.variations && data.variations.length > 0) {
                            detailsHtml += `
                                <div class="mb-3">
                                    <label class="form-label">Select Ring Size *</label>
                                    <div class="ring-size-selector" id="buyNowRingSizeOptions">
                                    `;
                            data.variations.forEach(variation => {
                                detailsHtml += `
                                    <div class="ring-size-option ${variation.stock <= 0 ? 'out-of-stock' : 'available'}"
                                         data-variation-id="${variation.id}"
                                         data-stock="${variation.stock}">
                                        ${htmlspecialchars(variation.name)}
                                        <small>${variation.stock} in stock</small>
                                    </div>
                                `;
                            });
                            detailsHtml += '</div></div>';
                        } else if (product.is_ring) {
                            detailsHtml += '<p class="text-danger">No size variations available for this ring.</p>';
                            submitBuyNowBtn.disabled = true; // Disable if no variations for ring
                        }

                        modalBodyDetails.innerHTML = detailsHtml;

                        // Add event listeners to new size options if they exist
                        if (product.is_ring) {
                             const buyNowRingSizeOptions = modalBodyDetails.querySelector('#buyNowRingSizeOptions');
                             if(buyNowRingSizeOptions) {
                                 buyNowRingSizeOptions.querySelectorAll('.ring-size-option.available').forEach(option => {
                                     option.addEventListener('click', function() {
                                         // Remove selected class from all available options
                                         buyNowRingSizeOptions.querySelectorAll('.ring-size-option.available').forEach(opt => opt.classList.remove('selected'));

                                         // Add selected class to clicked option
                                         this.classList.add('selected');

                                         // Store selected variation ID
                                         buyNowVariationIdInput.value = this.dataset.variationId;
                                         sizeErrorDiv.classList.add('d-none'); // Hide error if size is selected
                                     });
                                 });
                             }
                        }

                    } else {
                        modalBodyDetails.innerHTML = '<p class="text-danger">Error loading product details.</p>';
                        submitBuyNowBtn.disabled = true; // Disable if product details fail to load
                    }
                })
                .catch(error => {
                    console.error('Error fetching product details:', error);
                    modalBodyDetails.innerHTML = '<p class="text-danger">Error loading product details.</p>';
                    submitBuyNowBtn.disabled = true; // Disable on fetch error
                });
        });

        // Handle submitting the Buy Now form
        document.getElementById('submitBuyNow').addEventListener('click', function() {
            const form = document.getElementById('buyNowForm');
            const sizeErrorDiv = document.getElementById('buyNowErrors');
            const buyNowVariationIdInput = document.getElementById('buyNowVariationId');
            const buyNowProductIdInput = document.getElementById('buyNowProductId');

            // Validate ring size selection if it's a ring
            const productCategorySlug = buyNowModal.querySelector('.btn-buy-now').dataset.productCategorySlug; // Get slug from the triggering button
            if (productCategorySlug === 'rings' && !buyNowVariationIdInput.value) {
                sizeErrorDiv.classList.remove('d-none');
                sizeErrorDiv.innerHTML = 'Please select a ring size.';
                return;
            }

            // Reset errors
            sizeErrorDiv.classList.add('d-none');
            sizeErrorDiv.innerHTML = '';

            // Validate form fields
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);

            // Append product_id and variation_id manually if needed (FormData might not include hidden fields always?)
            // Redundant if form fields are correctly set, but as a fallback
            formData.set('product_id', buyNowProductIdInput.value);
            if (buyNowVariationIdInput.value) {
                 formData.set('variation_id', buyNowVariationIdInput.value);
            }
             formData.set('quantity', 1); // Ensure quantity is 1 for Buy Now

            // Submit order to checkout processing script
            fetch('/Glamour-shopv1.3/public/checkout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to WhatsApp or success page
                    // Assuming checkout.php returns whatsapp_url on success
                    if (data.whatsapp_url) {
                         window.location.href = data.whatsapp_url;
                    } else { // Fallback to cart or a success page if no whatsapp_url
                         window.location.href = '/Glamour-shopv1.3/public/cart.php?order_placed=1'; // Example redirect
                    }
                } else {
                    // Show errors returned from checkout.php
                    sizeErrorDiv.classList.remove('d-none');
                    sizeErrorDiv.innerHTML = data.message || (data.errors ? data.errors.join('<br>') : 'An error occurred.');
                }
            })
            .catch(error => {
                console.error('Error submitting Buy Now form:', error);
                sizeErrorDiv.classList.remove('d-none');
                sizeErrorDiv.innerHTML = 'An error occurred while processing your order. Please try again.';
            });
        });

        // Set minimum date for pickup to tomorrow in the Buy Now modal
        const buyNowPickupDate = document.getElementById('buyNowPickupDate');
        if(buyNowPickupDate) {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            buyNowPickupDate.min = tomorrow.toISOString().split('T')[0];
        }
    });
    </script>
</body>
</html> 