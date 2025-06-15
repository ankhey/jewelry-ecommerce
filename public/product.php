<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/layout.php';

// Get product slug from URL
$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : null;

if (!$slug) {
    header('Location: /');
    exit();
}

try {
    $db = getDB();
    
    // Get product details
    $stmt = $db->prepare("
        SELECT p.*, c.name as category_name, c.slug as category_slug
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = :id AND p.is_visible = 1
    ");
    $stmt->execute([':id' => $slug]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header('Location: /Glamour-shopv1.3/');
        exit();
    }
    
    // Get product variations
    $stmt = $db->prepare("SELECT * FROM product_variations WHERE product_id = :id ORDER BY name");
    $stmt->execute([':id' => $slug]);
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
        ':product_id' => $slug
    ]);
    $related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if this is a ring product
    $is_ring = ($product['category_slug'] === 'rings');
    
    // If it's a ring, get available sizes
    $ring_sizes = [];
    if ($is_ring) {
        // Get all variations that are ring sizes
        $stmt = $db->prepare("
            SELECT * FROM product_variations 
            WHERE product_id = :product_id 
            AND name LIKE 'Size %' 
            ORDER BY CAST(SUBSTRING(name, 6) AS UNSIGNED)
        ");
        $stmt->execute([':product_id' => $slug]);
        $ring_sizes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// Set page title
$page_title = $product['name'];

// Set breadcrumbs
$breadcrumbs = [
    ['text' => 'Products', 'url' => '/', 'active' => false],
    ['text' => $product['category_name'], 'url' => "/?category=" . strtolower($product['category_name']), 'active' => false],
    ['text' => $product['name'], 'url' => '', 'active' => true]
];

// Start output buffering
ob_start();
?>

<div class="container">
    <div class="row">
        <!-- Product Image -->
        <div class="col-md-6">
            <img src="<?php echo $product['image_path']; ?>" 
                 alt="<?php echo $product['name']; ?>"
                 class="img-fluid product-detail">
        </div>

        <!-- Product Info -->
        <div class="col-md-6">
            <h1 class="mb-3"><?php echo $product['name']; ?></h1>
            <p class="text-muted mb-3"><?php echo $product['category_name']; ?></p>
            
            <div class="mb-4">
                <span class="h3 text-primary">$<?php echo number_format($product['price'], 2); ?></span>
                <span class="badge <?php echo $product['stock'] > 0 ? 'bg-success' : 'bg-danger'; ?> ms-2">
                    <?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                </span>
            </div>

            <div class="mb-4">
                <h5>Description</h5>
                <p><?php echo nl2br($product['description']); ?></p>
            </div>

            <?php if ($product['stock'] > 0): ?>
                <form id="add-to-cart-form" class="mb-4">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    
                    <?php if ($is_ring && !empty($ring_sizes)): ?>
                        <!-- Ring Size Selection -->
                        <div class="mb-3">
                            <h5>Select Ring Size</h5>
                            <div class="ring-size-selector">
                                <?php foreach ($ring_sizes as $size): ?>
                                    <div class="ring-size-option" 
                                         data-variation-id="<?php echo $size['id']; ?>"
                                         data-price-adjustment="<?php echo $size['price_adjustment']; ?>"
                                         data-stock="<?php echo $size['stock']; ?>">
                                        <?php echo $size['name']; ?>
                                        <?php if ($size['price_adjustment'] != 0): ?>
                                            (<?php echo $size['price_adjustment'] > 0 ? '+' : ''; ?>
                                            $<?php echo number_format($size['price_adjustment'], 2); ?>)
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="variation_id" id="selected-variation" required>
                            <div id="size-error" class="text-danger mt-2" style="display: none;">Please select a ring size</div>
                        </div>
                    <?php elseif (!empty($variations)): ?>
                        <div class="mb-3">
                            <h5>Select Variation</h5>
                            <div class="product-variations">
                                <?php foreach ($variations as $variation): ?>
                                    <div class="variation-option" 
                                         data-variation-id="<?php echo $variation['id']; ?>"
                                         data-price-adjustment="<?php echo $variation['price_adjustment']; ?>">
                                        <?php echo $variation['name']; ?>
                                        <?php if ($variation['price_adjustment'] != 0): ?>
                                            (<?php echo $variation['price_adjustment'] > 0 ? '+' : ''; ?>
                                            $<?php echo number_format($variation['price_adjustment'], 2); ?>)
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="variation_id" id="selected-variation" required>
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

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Add to Cart</button>
                        <button type="button" class="btn btn-success btn-lg" id="buy-now">Buy Now</button>
                    </div>
                </form>
            <?php endif; ?>

            <!-- Additional Info -->
            <div class="mt-4">
                <h5>Product Details</h5>
                <ul class="list-unstyled">
                    <li><strong>SKU:</strong> <?php echo $product['id']; ?></li>
                    <li><strong>Category:</strong> <?php echo $product['category_name']; ?></li>
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
            <div class="row">
                <?php foreach ($related_products as $related): ?>
                    <div class="col-md-3">
                        <div class="product-card">
                            <img src="<?php echo $related['image_path']; ?>" 
                                 alt="<?php echo $related['name']; ?>"
                                 class="card-img-top">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $related['name']; ?></h5>
                                <p class="price">$<?php echo number_format($related['price'], 2); ?></p>
                                <a href="/product.php?slug=<?php echo $related['slug']; ?>" 
                                   class="btn btn-primary btn-sm">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
.ring-size-selector {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
}

.ring-size-option {
    display: inline-block;
    width: 50px;
    height: 50px;
    line-height: 50px;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.2s;
}

.ring-size-option:hover {
    border-color: #007bff;
    background-color: #f8f9fa;
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
}
</style>

<?php
$content = ob_get_clean();

// Add page-specific JavaScript
$page_scripts = <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variation selection
    const variationOptions = document.querySelectorAll('.variation-option');
    const ringSizeOptions = document.querySelectorAll('.ring-size-option');
    const selectedVariationInput = document.getElementById('selected-variation');
    const sizeError = document.getElementById('size-error');
    const addToCartForm = document.getElementById('add-to-cart-form');
    
    // Handle ring size selection
    if (ringSizeOptions.length > 0) {
        ringSizeOptions.forEach(option => {
            // Check if size is out of stock
            if (parseInt(option.dataset.stock) <= 0) {
                option.classList.add('out-of-stock');
                option.title = 'Out of stock';
            } else {
                option.addEventListener('click', function() {
                    ringSizeOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedVariationInput.value = this.dataset.variationId;
                    sizeError.style.display = 'none';
                });
            }
        });
        
        // Form validation for ring sizes
        addToCartForm.addEventListener('submit', function(e) {
            if (!selectedVariationInput.value) {
                e.preventDefault();
                sizeError.style.display = 'block';
                return false;
            }
        });
    }
    
    // Handle other variations
    variationOptions.forEach(option => {
        option.addEventListener('click', function() {
            variationOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            selectedVariationInput.value = this.dataset.variationId;
        });
    });

    // Quantity controls
    const quantityInput = document.getElementById('quantity');
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

    // Buy Now button
    document.getElementById('buy-now').addEventListener('click', function() {
        const form = document.getElementById('add-to-cart-form');
        if (form.checkValidity()) {
            const formData = new FormData(form);
            formData.append('buy_now', '1');
            form.submit();
        } else {
            form.reportValidity();
        }
    });
});
</script>
HTML;

require_once __DIR__ . '/../includes/layout.php';
?> 