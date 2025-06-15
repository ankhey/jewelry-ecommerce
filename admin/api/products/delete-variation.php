<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// Check permissions
requirePermission('manage_products');

// Set response header
header('Content-Type: application/json');

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode([
        'success' => false,
        'message' => 'Variation ID is required'
    ]);
    exit();
}

try {
    // Start transaction
    $db = getDB();
    $db->exec('BEGIN TRANSACTION');
    
    // Delete variation
    $stmt = $db->prepare("DELETE FROM product_variations WHERE id = :id");
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    
    // Commit transaction
    $db->exec('COMMIT');
    
    // Create notification
    createNotification($db, 'Product Variation Deleted', "Variation #$id has been deleted.");
    
    echo json_encode([
        'success' => true,
        'message' => 'Variation deleted successfully'
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    $db->exec('ROLLBACK');
    
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting variation: ' . $e->getMessage()
    ]);
} 