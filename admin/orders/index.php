<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$page_title = "Orders";

// Get search parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Initialize pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    $db = getDB();
    
    // Process status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['status'];
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Get current order status
            $stmt = $db->prepare("SELECT status FROM orders WHERE id = :id");
            $stmt->execute([':id' => $order_id]);
            $current_status = $stmt->fetchColumn();
            
            // If order is being cancelled and wasn't cancelled before
            if ($new_status === 'cancelled' && $current_status !== 'cancelled') {
                // Get order items with current stock levels
                $stmt = $db->prepare("
                    SELECT 
                        oi.*,
                        p.name as product_name,
                        p.stock as current_stock
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = :order_id
                ");
                $stmt->execute([':order_id' => $order_id]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Restore stock for each item
                foreach ($items as $item) {
                    $new_stock = $item['current_stock'] + $item['quantity'];
                    
                    $stmt = $db->prepare("
                        UPDATE products 
                        SET stock = :new_stock,
                            updated_at = NOW()
                        WHERE id = :product_id
                    ");
                    
                    $stmt->execute([
                        ':new_stock' => $new_stock,
                        ':product_id' => $item['product_id']
                    ]);
                    
                    // Log the stock update
                    error_log(sprintf(
                        "Order #%d cancelled: Restored %d units to product '%s' (Previous stock: %d, New stock: %d)",
                        $order_id,
                        $item['quantity'],
                        $item['product_name'],
                        $item['current_stock'],
                        $new_stock
                    ));
                }
                
                // Set success message with stock details
                $_SESSION['success_message'] = sprintf(
                    "Order #%d has been cancelled and %d items have been returned to stock.",
                    $order_id,
                    count($items)
                );
            }
            
            // Update order status
            $stmt = $db->prepare("
                UPDATE orders 
                SET status = :status,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':status' => $new_status,
                ':id' => $order_id
            ]);
            
            // Commit transaction
            $db->commit();
            
            // Redirect to refresh the page
            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollBack();
            error_log("Error updating order #$order_id status: " . $e->getMessage());
            $_SESSION['error_message'] = "Error updating order status. Please try again.";
            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
            exit();
        }
    }
    
    // Build the query
    $params = [];
    $where_clauses = [];
    
    if ($search) {
        $where_clauses[] = "(c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR o.pickup_location LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($status) {
        $where_clauses[] = "o.status = ?";
        $params[] = $status;
    }
    
    $where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as count FROM orders o JOIN customers c ON o.customer_id = c.id $where_sql";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $total_pages = ceil($total_orders / $per_page);
    
    // Get orders
    $order_by = match($sort) {
        'oldest' => 'o.created_at ASC',
        default => 'o.created_at DESC'
    };
    
    $sql = "
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
        $where_sql
        ORDER BY $order_by
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    die('An error occurred while loading orders. Please try again later.');
}

// Start output buffering
ob_start();
?>

<div class="admin-layout-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Orders</h1>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by customer name, email, phone or location...">
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Sort By</label>
                        <select name="sort" class="form-select">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Pickup Details</th>
                            <th class="text-end">Total Amount</th>
                            <th class="text-center">Status</th>
                            <th>Order Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <div class="fw-medium">#<?php echo $order['id']; ?></div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-light rounded-circle me-2 d-flex align-items-center justify-content-center">
                                        <i class="bi bi-person text-primary"></i>
                                    </div>
                                    <div>
                                        <?php echo htmlspecialchars($order['customer_name']); ?>
                                        <?php if ($order['customer_review']): ?>
                                            <br><small class="text-muted"><i class="bi bi-chat-quote"></i> Has review</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="tel:<?php echo htmlspecialchars($order['customer_phone']); ?>" class="text-body d-block">
                                    <?php echo htmlspecialchars($order['customer_phone']); ?>
                                </a>
                                <a href="mailto:<?php echo htmlspecialchars($order['customer_email']); ?>" class="text-muted small">
                                    <?php echo htmlspecialchars($order['customer_email']); ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-medium"><?php echo htmlspecialchars($order['pickup_location']); ?></div>
                                <small class="text-muted">
                                    <?php echo date('M j, Y', strtotime($order['pickup_date'])); ?> at 
                                    <?php echo date('g:i A', strtotime($order['pickup_time'])); ?>
                                </small>
                            </td>
                            <td class="text-end">KES <?php echo number_format($order['total_amount'], 2); ?></td>
                            <td class="text-center">
                                <form method="POST" class="status-form">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <select name="status" class="form-select form-select-sm status-select" 
                                            onchange="this.form.submit()">
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>
                                            Pending
                                        </option>
                                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>
                                            Processing
                                        </option>
                                        <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>
                                            Completed
                                        </option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>
                                            Cancelled
                                        </option>
                                    </select>
                                </form>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="view.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-sm btn-outline-info" 
                                       title="View Order">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    No orders found
                                    <?php if ($search || $status): ?>
                                        matching your filters
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav class="mt-4" aria-label="Orders pagination">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo urlencode($sort); ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo urlencode($sort); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo urlencode($sort); ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 16px;
}
.status-select {
    min-width: 130px;
    border: 1px solid rgba(0,0,0,.125);
    background-color: var(--bs-white);
}
.status-select option {
    background-color: var(--bs-white) !important;
}
</style> 

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout file
require_once __DIR__ . '/../layout.php';
?> 