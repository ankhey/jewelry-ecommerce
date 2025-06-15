<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/layout.php';

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$order_id) {
    header('Location: /');
    exit();
}

try {
    $db = getDB();
    
    // Get order details
    $stmt = $db->prepare("
        SELECT o.*, c.first_name, c.last_name, c.email, c.phone, c.address, c.city, c.state, c.zip_code, c.country
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        WHERE o.id = :id
    ");
    $stmt->execute([':id' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: /Glamour-shopv1.3/');
        exit();
    }
    
    // Get order items
    $stmt = $db->prepare("
        SELECT oi.*, p.name as product_name, p.image_path, v.name as variation_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_variations v ON oi.variation_id = v.id
        WHERE oi.order_id = :order_id
    ");
    $stmt->execute([':order_id' => $order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// Set page title
$page_title = 'Order Confirmation';

// Set breadcrumbs
$breadcrumbs = [
    ['text' => 'Order Confirmation', 'url' => '', 'active' => true]
];

// Start output buffering
ob_start();
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    <h1 class="mt-3">Thank You!</h1>
                    <p class="lead">Your order has been successfully placed.</p>
                    <p>Order #<?php echo $order_id; ?></p>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Order Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Customer Information</h6>
                            <p class="mb-1"><?php echo $order['customer_name']; ?></p>
                            <p class="mb-1"><?php echo $order['email']; ?></p>
                            <p class="mb-0"><?php echo nl2br($order['shipping_address']); ?></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6>Order Information</h6>
                            <p class="mb-1">Order Date: <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                            <p class="mb-1">Status: <span class="badge bg-primary"><?php echo ucfirst($order['status']); ?></span></p>
                            <p class="mb-0">Payment Status: <span class="badge bg-success"><?php echo ucfirst($order['payment_status']); ?></span></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($order_items as $item): ?>
                        <div class="row mb-3">
                            <div class="col-md-2">
                                <img src="<?php echo $item['image_path']; ?>" 
                                     alt="<?php echo $item['product_name']; ?>"
                                     class="img-fluid">
                            </div>
                            <div class="col-md-7">
                                <h6 class="mb-1"><?php echo $item['product_name']; ?></h6>
                                <?php if ($item['variation_name']): ?>
                                    <p class="text-muted mb-1"><?php echo $item['variation_name']; ?></p>
                                <?php endif; ?>
                                <p class="text-muted mb-0">Quantity: <?php echo $item['quantity']; ?></p>
                            </div>
                            <div class="col-md-3 text-end">
                                <p class="mb-0">$<?php echo number_format($item['price'], 2); ?></p>
                            </div>
                        </div>
                        <?php if ($item !== end($order_items)): ?>
                            <hr>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h6>Order Summary</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping</span>
                                <span>Free</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total</strong>
                                <strong>$<?php echo number_format($order['total_amount'], 2); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="/" class="btn btn-primary">Continue Shopping</a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php';
?> 