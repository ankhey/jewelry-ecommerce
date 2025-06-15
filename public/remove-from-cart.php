<?php
require_once __DIR__ . '/../includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set header to return JSON response
header('Content-Type: application/json');

try {
    // Get JSON data from POST request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || !isset($data['product_id'])) {
        throw new Exception('Invalid request data');
    }

    $product_id = (int)$data['product_id'];

    // Remove item from cart
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    } else {
        throw new Exception('Product not in cart');
    }

    // Calculate total items in cart
    $cart_count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Item removed from cart successfully',
        'cart_count' => $cart_count
    ]);

} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 