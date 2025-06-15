<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Check permissions
requirePermission('manage_products');

// Get product ID
$product_id = $_POST['product_id'] ?? null;
if (!$product_id) {
    redirectWithMessage('/Glamour-shopv1.3/admin/products/index.php', 'error', 'Invalid product ID');
}

try {
    $db = getDB();

    // Begin transaction
    $db->beginTransaction();

    // Get selected attributes
    $attributes = $_POST['attributes'] ?? [];
    
    // Clear existing attribute selections for this product
    $stmt = $db->prepare("DELETE FROM ring_custom_attributes WHERE product_id = :product_id");
    $stmt->execute([':product_id' => $product_id]);

    // Insert new attribute selections
    $stmt = $db->prepare("
        INSERT INTO ring_custom_attributes (product_id, attribute_id, value_id)
        VALUES (:product_id, :attribute_id, :value_id)
    ");

    foreach ($attributes as $type => $values) {
        if (is_array($values)) {
            foreach ($values as $value_id) {
                $stmt->execute([
                    ':product_id' => $product_id,
                    ':attribute_id' => $type,
                    ':value_id' => $value_id
                ]);
            }
        }
    }

    // Commit transaction
    $db->commit();

    // Redirect back with success message
    redirectWithMessage(
        "/Glamour-shopv1.3/admin/products/ring-variations.php?id=$product_id",
        'success',
        'Ring attributes saved successfully'
    );

} catch (PDOException $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    redirectWithMessage(
        "/Glamour-shopv1.3/admin/products/ring-variations.php?id=$product_id",
        'error',
        'Error saving ring attributes: ' . $e->getMessage()
    );
} 