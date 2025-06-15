<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Check permissions
requirePermission('manage_products');

// Get variation ID
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: /admin/products/index.php');
    exit();
}

try {
    $db = getDB();
    
    // Get variation details
    $stmt = $db->prepare("SELECT * FROM product_variations WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $variation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$variation) {
        header('Location: /Glamour-shopv1.3/admin/products/ring-variations.php?id=' . $variation['product_id']);
        exit();
    }
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $errors = [];
        
        // Validate required fields
        if (empty($_POST['name'])) {
            $errors[] = 'Variation name is required';
        }
        
        if (!isset($_POST['price_adjustment']) || !is_numeric($_POST['price_adjustment'])) {
            $errors[] = 'Price adjustment must be a number';
        }
        
        if (!isset($_POST['stock']) || !is_numeric($_POST['stock']) || $_POST['stock'] < 0) {
            $errors[] = 'Stock must be a positive number';
        }
        
        if (empty($errors)) {
            try {
                // Update variation
                $stmt = $db->prepare("
                    UPDATE product_variations 
                    SET name = :name,
                        price_adjustment = :price_adjustment,
                        stock = :stock,
                        updated_at = datetime('now')
                    WHERE id = :id
                ");
                
                $stmt->bindValue(':id', $id);
                $stmt->bindValue(':name', $_POST['name']);
                $stmt->bindValue(':price_adjustment', $_POST['price_adjustment']);
                $stmt->bindValue(':stock', $_POST['stock']);
                
                $stmt->execute();
                
                // Create notification
                createNotification($db, 'Product Variation Updated', "Variation #$id has been updated.");
                
                // Redirect to variations page
                header("Location: /admin/products/ring-variations.php?id=" . $variation['product_id']);
                exit();
            } catch (Exception $e) {
                $errors[] = 'Error updating variation: ' . $e->getMessage();
            }
        }
    }
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// Set page title
$page_title = 'Edit Variation';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3"><?php echo $page_title; ?></h1>
                <p class="text-muted mb-0">
                    <?php echo htmlspecialchars($variation['product_name']); ?>
                </p>
            </div>
            <a href="/admin/products/ring-variations.php?id=<?php echo $variation['product_id']; ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Variations
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Edit Variation</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Variation Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($variation['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="price_adjustment" class="form-label">Price Adjustment</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="price_adjustment" 
                                           name="price_adjustment" step="0.01" 
                                           value="<?php echo $variation['price_adjustment']; ?>" required>
                                </div>
                                <div class="form-text">
                                    Positive value adds to base price, negative value subtracts
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock</label>
                                <input type="number" class="form-control" id="stock" name="stock" 
                                       min="0" value="<?php echo $variation['stock']; ?>" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 