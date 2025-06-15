<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check permissions
requirePermission('manage_products');

// Get product ID
$id = $_GET['id'] ?? null;
if (!$id) {
    redirectWithMessage('/Glamour-shopv1.3/admin/products/index.php', 'error', 'Invalid product ID');
}

try {
    $db = getDB();
    
    // Get product details first
    $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        redirectWithMessage('/Glamour-shopv1.3/admin/products/index.php', 'error', 'Product not found');
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Delete product variations first (if any)
        $stmt = $db->prepare("DELETE FROM product_variations WHERE product_id = :id");
        $stmt->execute([':id' => $id]);
        
        // Delete order items referencing this product
        $stmt = $db->prepare("DELETE FROM order_items WHERE product_id = :id");
        $stmt->execute([':id' => $id]);
        
        // Delete the product
        $stmt = $db->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        // Delete product image if exists
        if (!empty($product['image_path'])) {
            $image_path = __DIR__ . '/../../' . $product['image_path'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Create notification
        createNotification($db, 'Product Deleted', "Product #$id has been deleted.");
        
        // Commit transaction
        $db->commit();
        
        // Redirect with success message
        redirectWithMessage('/Glamour-shopv1.3/admin/products/index.php', 'success', 'Product deleted successfully');
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    // Log error and redirect with error message
    error_log('Error deleting product: ' . $e->getMessage());
    redirectWithMessage('/Glamour-shopv1.3/admin/products/index.php', 'error', 'Error deleting product: ' . $e->getMessage());
} 