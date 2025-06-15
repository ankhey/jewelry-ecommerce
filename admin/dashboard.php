<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin(); // Ensure only logged-in admins can access

// Set page title
$page_title = 'Dashboard';

try {
    $db = getDB();
    
    // Total orders
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders");
    $total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Total revenue (excluding cancelled orders)
    $stmt = $db->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
    $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Total products
    $stmt = $db->query("SELECT COUNT(*) as count FROM products");
    $total_products = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Recent orders
    $stmt = $db->prepare("
        SELECT o.*, c.name as customer_name 
        FROM orders o 
        JOIN customers c ON o.customer_id = c.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Low stock products
    $stmt = $db->prepare("
        SELECT p.*, 
               COALESCE(SUM(pv.stock), p.stock) as total_stock 
        FROM products p 
        LEFT JOIN product_variations pv ON p.id = pv.product_id 
        WHERE p.is_visible = 1 
        GROUP BY p.id 
        HAVING total_stock <= 5 
        ORDER BY total_stock ASC 
        LIMIT 5
    ");
    $stmt->execute();
    $low_stock_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Dashboard error: ' . $e->getMessage());
    die('An error occurred while loading the dashboard. Please try again later.');
}

// Helper function for status colors
function getStatusColor($status) {
    return match(strtolower($status)) {
        'completed' => 'success',
        'pending' => 'warning',
        'processing' => 'info',
        'cancelled' => 'danger',
        default => 'secondary'
    };
}

// Start output buffering
ob_start();
?>

<div class="admin-layout-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Dashboard</h1>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Total Orders</h6>
                            <h2 class="mt-2 mb-0"><?php echo number_format($total_orders); ?></h2>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-cart"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Total Revenue</h6>
                            <h2 class="mt-2 mb-0">KES <?php echo number_format($total_revenue, 2); ?></h2>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-cash"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Total Products</h6>
                            <h2 class="mt-2 mb-0"><?php echo number_format($total_products); ?></h2>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Orders and Low Stock -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Orders</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_orders)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-3">No recent orders</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>
                                                <a href="/Glamour-shopv1.3/admin/orders/view.php?id=<?php echo $order['id']; ?>">
                                                    #<?php echo $order['id']; ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                            <td>KES <?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getStatusColor($order['status']); ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Low Stock Products</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Stock</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($low_stock_products)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-3">No low stock products</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($low_stock_products as $product): ?>
                                        <tr>
                                            <td>
                                                <a href="/Glamour-shopv1.3/admin/products/edit.php?id=<?php echo $product['id']; ?>">
                                                    <?php echo htmlspecialchars($product['name']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $product['total_stock'] == 0 ? 'danger' : 'warning'; ?>">
                                                    <?php echo $product['total_stock']; ?> units
                                                </span>
                                            </td>
                                            <td>
                                                <a href="/Glamour-shopv1.3/admin/products/edit.php?id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil"></i> Update Stock
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}
.stat-card:hover {
    transform: translateY(-5px);
}
.stat-icon {
    font-size: 2rem;
    opacity: 0.8;
}
.table td {
    vertical-align: middle;
}
</style>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout file
require_once __DIR__ . '/layout.php';
?> 