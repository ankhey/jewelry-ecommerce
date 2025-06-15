<?php
require_once __DIR__ . '/../includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $db = getDB();
    
    // Get cart items with product details
    $cart_items = [];
    $total = 0;
    
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $item) {
            // Parse the cart item key to get product_id and variation_id
            $parts = explode('_', $key);
            $product_id = (int)$parts[0];
            $variation_id = isset($parts[1]) ? (int)$parts[1] : null;
            
            $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
            $stmt->execute([':id' => $product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                $variation = null;
                if ($variation_id) {
                    $stmt = $db->prepare("SELECT * FROM product_variations WHERE id = :id");
                    $stmt->execute([':id' => $variation_id]);
                    $variation = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                
                $price = $product['price'];
                if ($variation) {
                    $price += $variation['price_adjustment'];
                }
                
                $item_total = $price * $item['quantity'];
                $total += $item_total;
                
                $cart_items[] = [
                    'id' => $product_id,
                    'name' => $product['name'],
                    'price' => $price,
                    'quantity' => $item['quantity'],
                    'total' => $item_total,
                    'variation' => $variation
                ];
            }
        }
    }
    
    // Output the cart summary HTML
    if (empty($cart_items)) {
        echo '<div class="alert alert-warning">Your cart is empty.</div>';
    } else {
        foreach ($cart_items as $item) {
            echo '<div class="d-flex justify-content-between mb-2">';
            echo '<div>';
            echo '<span class="fw-medium">' . htmlspecialchars($item['name']) . '</span>';
            echo '<small class="text-muted d-block">Qty: ' . $item['quantity'] . '</small>';
            
            // Display variation (ring size) if available
            if ($item['variation']) {
                echo '<small class="text-muted">Size: ' . htmlspecialchars($item['variation']['name']) . '</small>';
            }
            
            echo '</div>';
            echo '<span>KES ' . number_format($item['total'], 2) . '</span>';
            echo '</div>';
        }
        
        echo '<hr>';
        echo '<div class="d-flex justify-content-between">';
        echo '<strong>Total</strong>';
        echo '<strong>KES ' . number_format($total, 2) . '</strong>';
        echo '</div>';
    }
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo '<div class="alert alert-danger">An error occurred while loading your cart summary.</div>';
} 