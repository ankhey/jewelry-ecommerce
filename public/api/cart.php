<?php
require_once __DIR__ . '/../../includes/config.php';

// Set JSON response header
header('Content-Type: application/json');

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle different actions
switch ($data['action']) {
    case 'add':
        // Validate required fields
        if (!isset($data['product_id']) || !isset($data['quantity'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }

        // Get product details
        try {
            $db = getDB();
            
            $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
            $stmt->execute([':id' => $data['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found']);
                exit();
            }

            // Check stock
            if ($product['stock'] < $data['quantity']) {
                echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
                exit();
            }

            // Add to cart
            $cart_item = [
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity']
            ];

            if (isset($data['variation_id'])) {
                $cart_item['variation_id'] = $data['variation_id'];
            }

            $_SESSION['cart'][] = $cart_item;
            echo json_encode(['success' => true, 'message' => 'Item added to cart']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'remove':
        // Validate required fields
        if (!isset($data['product_id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing product ID']);
            exit();
        }

        // Remove item from cart
        $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($data) {
            if ($item['product_id'] == $data['product_id']) {
                if (isset($data['variation_id'])) {
                    return $item['variation_id'] != $data['variation_id'];
                }
                return false;
            }
            return true;
        });

        echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
        break;

    case 'update':
        // Validate required fields
        if (!isset($data['product_id']) || !isset($data['quantity'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }

        // Get product details
        try {
            $db = getDB();
            
            $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
            $stmt->execute([':id' => $data['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found']);
                exit();
            }

            // Check stock
            if ($product['stock'] < $data['quantity']) {
                echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
                exit();
            }

            // Update item quantity
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['product_id'] == $data['product_id']) {
                    if (isset($data['variation_id'])) {
                        if ($item['variation_id'] == $data['variation_id']) {
                            $item['quantity'] = $data['quantity'];
                            break;
                        }
                    } else {
                        $item['quantity'] = $data['quantity'];
                        break;
                    }
                }
            }

            // Get updated product details
            $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
            $stmt->execute([':id' => $data['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'message' => 'Cart updated']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'clear':
        // Clear cart
        $_SESSION['cart'] = [];
        echo json_encode(['success' => true, 'message' => 'Cart cleared']);
        break;

    default:
 