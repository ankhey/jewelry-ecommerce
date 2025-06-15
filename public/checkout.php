<?php
require_once __DIR__ . '/../includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'errors' => ['Your cart is empty']
    ]);
    exit();
}

try {
    $db = getDB();
    
    // Get cart items with product details
    $cart_items = [];
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
            
            $cart_items[] = [
                'id' => $product_id,
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $item['quantity'],
                'variation_id' => $variation_id,
                'variation' => $variation
            ];
        }
    }
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate form data
        $errors = [];
        $required_fields = ['name', 'email', 'phone', 'pickup_location', 'pickup_date', 'pickup_time', 'review'];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }

        if (empty($errors)) {
            // Get cart total
            $total = 0;
            foreach ($cart_items as $item) {
                $price = $item['price'];
                if (isset($item['variation'])) {
                    $price += $item['variation']['price_adjustment'];
                }
                $item_total = $price * $item['quantity'];
                $total += $item_total;
            }

            // Create customer record
            $stmt = $db->prepare("INSERT INTO customers (name, email, phone, pickup_location, review) 
                                 VALUES (:name, :email, :phone, :pickup_location, :review)");
            $stmt->bindValue(':name', $_POST['name']);
            $stmt->bindValue(':email', $_POST['email']);
            $stmt->bindValue(':phone', $_POST['phone']);
            $stmt->bindValue(':pickup_location', $_POST['pickup_location']);
            $stmt->bindValue(':review', $_POST['review']);
            $stmt->execute();
            $customer_id = $db->lastInsertId();

            // Create order record
            $stmt = $db->prepare("INSERT INTO orders (customer_id, total_amount, pickup_location, pickup_date, pickup_time, status) 
                                 VALUES (:customer_id, :total_amount, :pickup_location, :pickup_date, :pickup_time, 'pending')");
            $stmt->bindValue(':customer_id', $customer_id);
            $stmt->bindValue(':total_amount', $total);
            $stmt->bindValue(':pickup_location', $_POST['pickup_location']);
            $stmt->bindValue(':pickup_date', $_POST['pickup_date']);
            $stmt->bindValue(':pickup_time', $_POST['pickup_time']);
            $stmt->execute();
            $order_id = $db->lastInsertId();

            // Create order items and update stock
            foreach ($cart_items as $item) {
                $price = $item['price'];
                if (isset($item['variation'])) {
                    $price += $item['variation']['price_adjustment'];
                }
                
                $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, variation_id, quantity, price) 
                                     VALUES (:order_id, :product_id, :variation_id, :quantity, :price)");
                $stmt->bindValue(':order_id', $order_id);
                $stmt->bindValue(':product_id', $item['id']);
                $stmt->bindValue(':variation_id', $item['variation_id'] ?? null);
                $stmt->bindValue(':quantity', $item['quantity']);
                $stmt->bindValue(':price', $price);
                $stmt->execute();
                
                // Update product stock
                $stmt = $db->prepare("UPDATE products SET stock = stock - :quantity WHERE id = :product_id");
                $stmt->bindValue(':quantity', $item['quantity']);
                $stmt->bindValue(':product_id', $item['id']);
                $stmt->execute();

                // If this was a held item, remove it from the cart
                $cart_key = $item['id'];
                if (isset($item['variation_id'])) {
                    $cart_key .= '_' . $item['variation_id'];
                }
                if (isset($_SESSION['cart'][$cart_key]) && $_SESSION['cart'][$cart_key]['is_hold']) {
                    unset($_SESSION['cart'][$cart_key]);
                }
            }

            // Clear non-held items from cart
            foreach ($_SESSION['cart'] as $key => $item) {
                if (!isset($item['is_hold']) || !$item['is_hold']) {
                    unset($_SESSION['cart'][$key]);
                }
            }

            // Prepare WhatsApp message
            $whatsapp_message = "🛍️ *New Order #" . $order_id . "*\n\n";
            $whatsapp_message .= "*Customer Details:*\n";
            $whatsapp_message .= "Name: " . $_POST['name'] . "\n";
            $whatsapp_message .= "Phone: " . $_POST['phone'] . "\n";
            $whatsapp_message .= "Email: " . $_POST['email'] . "\n\n";
            
            $whatsapp_message .= "*Pickup Details:*\n";
            $whatsapp_message .= "Location: " . $_POST['pickup_location'] . "\n";
            $whatsapp_message .= "Date: " . $_POST['pickup_date'] . "\n";
            $whatsapp_message .= "Time: " . $_POST['pickup_time'] . "\n\n";
            
            $whatsapp_message .= "*Order Summary:*\n";
            foreach ($cart_items as $item) {
                $whatsapp_message .= "• " . $item['name'];
                if (isset($item['variation'])) {
                    $whatsapp_message .= " (Size: " . $item['variation']['name'] . ")";
                }
                $whatsapp_message .= " x" . $item['quantity'] . " - KES " . number_format($price * $item['quantity'], 2) . "\n";
            }
            $whatsapp_message .= "\n*Total Amount: KES " . number_format($total, 2) . "*\n\n";
            
            $whatsapp_message .= "*Customer Review:*\n";
            $whatsapp_message .= $_POST['review'];

            // Return success response with WhatsApp redirect
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'order_id' => $order_id,
                'whatsapp_url' => 'https://wa.me/254114595589?text=' . urlencode($whatsapp_message)
            ]);
            exit();
        } else {
            // Return error response for AJAX
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit();
        }
    }
    
    // If not a POST request, return error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'errors' => ['Invalid request method']
    ]);
    exit();
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    // Return error response for AJAX
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'errors' => ['Database error occurred']
    ]);
    exit();
} 