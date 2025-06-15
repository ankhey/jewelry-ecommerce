<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

try {
    $db = getDB();
    
    // Get ring size variations for the product
    $stmt = $db->prepare("
        SELECT id, name, stock 
        FROM product_variations 
        WHERE product_id = :product_id 
        AND name LIKE 'Size %'
        ORDER BY CAST(SUBSTRING(name, 6) AS UNSIGNED)
    ");
    $stmt->execute([':product_id' => $product_id]);
    $variations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'variations' => $variations]);
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching variations']);
}
?> 