<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Check permissions
requirePermission('manage_products');

// Get variation ID and product ID
$variation_id = $_POST['variation_id'] ?? null;
$product_id = $_POST['product_id'] ?? null;

if (!$variation_id || !$product_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid variation ID or product ID'
    ]);
    exit;
}

try {
    $db = getDB();

    // Begin transaction
    $db->beginTransaction();

    // Get variation image path before deletion
    $stmt = $db->prepare("
        SELECT image_path 
        FROM ring_variations 
        WHERE id = :id AND product_id = :product_id
    ");
    $stmt->execute([
        ':id' => $variation_id,
        ':product_id' => $product_id
    ]);
    $variation = $stmt->fetch(PDO::FETCH_ASSOC);

    // Delete the variation
    $stmt = $db->prepare("
        DELETE FROM ring_variations 
        WHERE id = :id AND product_id = :product_id
    ");
    $stmt->execute([
        ':id' => $variation_id,
        ':product_id' => $product_id
    ]);

    // If variation was deleted and had an image, delete the image file
    if ($stmt->rowCount() > 0 && $variation && $variation['image_path']) {
        $image_path = __DIR__ . '/../..' . $variation['image_path'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    // Commit transaction
    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Variation deleted successfully'
    ]);

} catch (PDOException $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting variation: ' . $e->getMessage()
    ]);
} 