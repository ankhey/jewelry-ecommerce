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
if (!isset($data['key']) || !isset($data['quantity'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit();
}

$key = $data['key'];
$quantity = (int)$data['quantity'];

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

try {
    $db = getDB();
    
    // Parse the cart item key to get product_id and variation_id
    $parts = explode('_', $key);
    $product_id = (int)$parts[0];
    $variation_id = isset($parts[1]) ? (int)$parts[1] : null;
    
    // Get product details
    $stmt = $db->prepare("
        SELECT p.*, c.slug as category_slug
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = :id
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
    
    // For ring products, verify variation exists IF one was provided in the key
    if ($is_ring && $variation_id && !isset($_SESSION['cart'][$key]['variation_id'])) {
         // This case should ideally not happen if items are added correctly, but as a fallback
         // This might catch issues with legacy cart items.
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
                 'message' => 'Invalid variation selected'
             ]);
             exit();
         }
    }

    // If variation is selected (either from the key or already in the session data)
    $current_variation_id_in_cart = isset($_SESSION['cart'][$key]['variation_id']) ? $_SESSION['cart'][$key]['variation_id'] : null;

    if ($variation_id || $current_variation_id_in_cart) {
        $target_variation_id = $variation_id ? $variation_id : $current_variation_id_in_cart;
        $stmt = $db->prepare("
            SELECT * FROM product_variations 
            WHERE id = :variation_id 
            AND product_id = :product_id
        ");
        $stmt->execute([
            ':variation_id' => $target_variation_id,
            ':product_id' => $product_id
        ]);
        $variation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$variation) {
            // Remove the item from the cart if the variation is invalid or missing
            unset($_SESSION['cart'][$key]);
            echo json_encode([
                'success' => true,
                'message' => 'Item with invalid variation removed from cart.'
            ]);
            exit();
        }
        
        // Check stock for the variation
        if ($variation['stock'] < $quantity) {
            echo json_encode([
                'success' => false,
                'message' => 'Not enough stock available for the selected size'
            ]);
            exit();
        }
    } else {
        // Check main product stock (for products without variations or legacy ring items without variation_id)
        if ($product['stock'] < $quantity) {
            echo json_encode([
                'success' => false,
                'message' => 'Not enough stock available'
            ]);
            exit();
        }
    }
    
    // Update or remove cart item
    if ($quantity <= 0) {
        // Remove item from cart
        unset($_SESSION['cart'][$key]);
    } else {
        // Update quantity
        $_SESSION['cart'][$key]['quantity'] = $quantity;
    }
    
    // Calculate total items in cart
    $cart_count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Cart updated successfully',
        'cart_count' => $cart_count
    ]);
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating the cart'
    ]);
}
?> 