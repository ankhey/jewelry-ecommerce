<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Ensure user is logged in
requireLogin();

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    header('Location: index.php');
    exit();
}

try {
    $db = getDB();
    
    // Get order details
    $stmt = $db->prepare("
        SELECT 
            o.id,
            o.customer_id,
            o.total_amount,
            o.pickup_location,
            o.pickup_date,
            o.pickup_time,
            o.status,
            o.created_at,
            c.name as customer_name,
            c.email as customer_email,
            c.phone as customer_phone,
            c.review as customer_review
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        WHERE o.id = :id
    ");
    $stmt->execute([':id' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header('Location: index.php');
        exit();
    }

    // Get order items
    $stmt = $db->prepare("
        SELECT oi.*, p.name as product_name, p.image_path
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = :order_id
    ");
    $stmt->execute([':order_id' => $order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Order view error: ' . $e->getMessage());
    die('An error occurred while loading the order details. Please try again later.');
}

// Set page title
$page_title = "Order #{$order_id} Details";

// Start output buffering
ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Order #<?php echo $order_id; ?> Details</h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="fw-bold">Name:</label>
                        <p class="mb-1"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Email:</label>
                        <p class="mb-1"><?php echo htmlspecialchars($order['customer_email']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Phone:</label>
                        <p class="mb-1"><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                    </div>
                    <?php if ($order['customer_review']): ?>
                        <div class="mb-0">
                            <label class="fw-bold">Review:</label>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['customer_review'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Pickup Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="fw-bold">Location:</label>
                        <p class="mb-1"><?php echo htmlspecialchars($order['pickup_location']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Date:</label>
                        <p class="mb-1"><?php echo date('M j, Y', strtotime($order['pickup_date'])); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Time:</label>
                        <p class="mb-1"><?php echo date('g:i A', strtotime($order['pickup_time'])); ?></p>
                    </div>
                    <div class="mb-0">
                        <label class="fw-bold">Status:</label>
                        <span class="badge bg-<?php 
                            echo match($order['status']) {
                                'pending' => 'warning',
                                'processing' => 'info',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'secondary'
                            };
                        ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Order Items</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($item['image_path']): ?>
                                            <img src="/Glamour-shopv1.3/<?php echo $item['image_path']; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                 class="me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </div>
                                </td>
                                <td>KES <?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>KES <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td><strong>KES <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout.php';
?> 