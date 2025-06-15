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
    
    // Get product details
    $stmt = $db->prepare("
        SELECT p.*, c.slug as category_slug
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = :id AND p.is_visible = 1
    ");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
         echo json_encode(['success' => false, 'message' => 'Product not found or not visible.']);
         exit();
    }

    $is_ring = ($product['category_slug'] === 'rings');
    $variations = [];

    // Get variations if it's a ring product
    if ($is_ring) {
        $stmt = $db->prepare("
            SELECT id, name, stock 
            FROM product_variations 
            WHERE product_id = :product_id 
            AND name LIKE 'Size %'
            ORDER BY CAST(SUBSTRING(name, 6) AS UNSIGNED)
        ");
        $stmt->execute([':product_id' => $product_id]);
        $variations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(['success' => true, 'product' => $product, 'variations' => $variations, 'is_ring' => $is_ring]);
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching product details.']);
}
?> 