<?php
require_once __DIR__ . '/../includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set header to return JSON
header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['product_id']) || !isset($data['quantity'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit();
}

$product_id = (int)$data['product_id'];
$quantity = (int)$data['quantity'];
$variation_id = isset($data['variation_id']) ? (int)$data['variation_id'] : null;
$is_hold = isset($data['is_hold']) ? (bool)$data['is_hold'] : false;

// Validate quantity
if ($quantity < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid quantity'
    ]);
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
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
        exit();
    }
    
    // Check if this is a ring product
    $is_ring = ($product['category_slug'] === 'rings');
    
    // For ring products, verify variation exists
    if ($is_ring && !$variation_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Ring size is required'
        ]);
        exit();
    }
    
    // Check stock
    if ($variation_id) {
        $stmt = $db->prepare("
            SELECT * FROM product_variations 
            WHERE id = :variation_id 
            AND product_id = :product_id
        ");
        $stmt->execute([
            ':variation_id' => $variation_id,
            ':product_id' => $product_id
        ]);
        $variation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$variation) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid ring size'
            ]);
            exit();
        }
        
        if ($variation['stock'] < $quantity) {
            echo json_encode([
                'success' => false,
                'message' => 'Not enough stock available for the selected size'
            ]);
            exit();
        }
    } else {
        if ($product['stock'] < $quantity) {
            echo json_encode([
                'success' => false,
                'message' => 'Not enough stock available'
            ]);
            exit();
        }
    }
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Create cart key
    $cart_key = $product_id;
    if ($variation_id) {
        $cart_key .= '_' . $variation_id;
    }
    
    // Add to cart
    $_SESSION['cart'][$cart_key] = [
        'product_id' => $product_id,
        'variation_id' => $variation_id,
        'quantity' => $quantity,
        'is_hold' => $is_hold,
        'hold_expires' => $is_hold ? time() + (15 * 60) : null // 15 minutes hold
    ];
    
    // Calculate total items in cart
    $cart_count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Item added to cart',
        'cart_count' => $cart_count
    ]);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?> 