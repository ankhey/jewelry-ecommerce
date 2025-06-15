<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Check permissions
requirePermission('manage_products');

// Get product ID
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: /Glamour-shopv1.3/admin/products/index.php');
    exit();
}

try {
    $db = getDB();
    
    // Get product details
    $stmt = $db->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header('Location: /Glamour-shopv1.3/admin/products/index.php');
        exit();
    }
    
    // Get product variations
    $stmt = $db->prepare("
        SELECT * FROM product_variations 
        WHERE product_id = :product_id
        ORDER BY created_at DESC
    ");
    $stmt->execute([':product_id' => $id]);
    $variations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if product_images table exists
    $images = [];
    try {
        // Get product images
        $stmt = $db->prepare("
            SELECT * FROM product_images 
            WHERE product_id = :product_id
            ORDER BY is_primary DESC, created_at ASC
        ");
        $stmt->execute([':product_id' => $id]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Table doesn't exist or other error, just continue with empty images array
        $images = [];
    }
    
    // Get recent orders for this product
    $stmt = $db->prepare("
        SELECT o.*, oi.quantity, oi.price as item_price, c.name as customer_name
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN customers c ON o.customer_id = c.id
        WHERE oi.product_id = :product_id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([':product_id' => $id]);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// Set page title
$page_title = 'Product Details: ' . $product['name'];

// Start output buffering
ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><?php echo $page_title; ?></h1>
        <div>
            <a href="/Glamour-shopv1.3/admin/products/index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Products
            </a>
            <a href="/Glamour-shopv1.3/admin/products/edit.php?id=<?php echo $id; ?>" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit Product
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Product Images -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Product Images</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($images)): ?>
                        <div class="row g-3">
                            <?php foreach ($images as $image): ?>
                                <div class="col-6">
                                    <div class="position-relative">
                                        <img src="/Glamour-shopv1.3/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="img-fluid rounded">
                                        <?php if ($image['is_primary']): ?>
                                            <span class="badge bg-primary position-absolute top-0 end-0 m-2">Primary</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-image text-muted fs-1"></i>
                            <p class="text-muted mt-2">No images available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Product Details -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Product Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Product ID:</div>
                        <div class="col-md-9"><?php echo $product['id']; ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Name:</div>
                        <div class="col-md-9"><?php echo htmlspecialchars($product['name']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Category:</div>
                        <div class="col-md-9"><?php echo htmlspecialchars($product['category_name']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Price:</div>
                        <div class="col-md-9">KES <?php echo number_format($product['price'], 2); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Stock:</div>
                        <div class="col-md-9">
                            <span class="badge bg-<?php echo $product['stock'] > 10 ? 'success' : ($product['stock'] > 0 ? 'warning' : 'danger'); ?>">
                                <?php echo $product['stock']; ?> units
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Status:</div>
                        <div class="col-md-9">
                            <span class="badge bg-<?php echo $product['is_visible'] ? 'success' : 'secondary'; ?>">
                                <?php echo $product['is_visible'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Created:</div>
                        <div class="col-md-9"><?php echo formatDate($product['created_at']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Last Updated:</div>
                        <div class="col-md-9"><?php echo formatDate($product['updated_at']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Description:</div>
                        <div class="col-md-9"><?php echo nl2br(htmlspecialchars($product['description'])); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Product Variations -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Product Variations</h5>
                    <a href="/Glamour-shopv1.3/admin/products/ring-variations.php?product_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg"></i> Add Variation
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($variations)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($variations as $variation): ?>
                                        <tr>
                                            <td><?php echo $variation['id']; ?></td>
                                            <td><?php echo htmlspecialchars($variation['name']); ?></td>
                                            <td>KES <?php echo number_format($variation['price'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $variation['stock'] > 10 ? 'success' : ($variation['stock'] > 0 ? 'warning' : 'danger'); ?>">
                                                    <?php echo $variation['stock']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="/Glamour-shopv1.3/admin/products/edit-variation.php?id=<?php echo $variation['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-box text-muted fs-1"></i>
                            <p class="text-muted mt-2">No variations available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Orders</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_orders)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>
                                                <a href="/Glamour-shopv1.3/admin/orders/view.php?id=<?php echo $order['id']; ?>">
                                                    #<?php echo $order['id']; ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                            <td><?php echo $order['quantity']; ?></td>
                                            <td>KES <?php echo number_format($order['item_price'] * $order['quantity'], 2); ?></td>
                                            <td><?php echo formatDate($order['created_at']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($order['status']) {
                                                        'pending' => 'warning',
                                                        'processing' => 'info',
                                                        'shipped' => 'primary',
                                                        'delivered' => 'success',
                                                        'cancelled' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-cart text-muted fs-1"></i>
                            <p class="text-muted mt-2">No recent orders for this product</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout file
require_once __DIR__ . '/layout.php';
?> 