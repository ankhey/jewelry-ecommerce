<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

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

    // Get variations data
    $variations = $_POST['variations'] ?? [];
    
    // Prepare statements
    $insert_stmt = $db->prepare("
        INSERT INTO ring_variations (
            product_id, sku, size_value_id, metal_value_id, 
            gemstone_value_id, finish_value_id, price_adjustment, 
            stock, image_path
        ) VALUES (
            :product_id, :sku, :size_value_id, :metal_value_id,
            :gemstone_value_id, :finish_value_id, :price_adjustment,
            :stock, :image_path
        )
    ");

    $update_stmt = $db->prepare("
        UPDATE ring_variations SET
            sku = :sku,
            size_value_id = :size_value_id,
            metal_value_id = :metal_value_id,
            gemstone_value_id = :gemstone_value_id,
            finish_value_id = :finish_value_id,
            price_adjustment = :price_adjustment,
            stock = :stock,
            image_path = COALESCE(:image_path, image_path)
        WHERE id = :id AND product_id = :product_id
    ");

    // Process each variation
    foreach ($variations as $variation_id => $variation) {
        // Handle image upload
        $image_path = null;
        if (isset($_FILES['variations']['name'][$variation_id]['image']) && 
            $_FILES['variations']['error'][$variation_id]['image'] === UPLOAD_ERR_OK) {
            
            $file = $_FILES['variations']['tmp_name'][$variation_id]['image'];
            $filename = $_FILES['variations']['name'][$variation_id]['image'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            // Generate unique filename
            $new_filename = uniqid('ring_var_') . '.' . $ext;
            $upload_path = __DIR__ . '/../../uploads/variations/' . $new_filename;
            
            // Create directory if it doesn't exist
            if (!is_dir(dirname($upload_path))) {
                mkdir(dirname($upload_path), 0777, true);
            }
            
            // Move uploaded file
            if (move_uploaded_file($file, $upload_path)) {
                $image_path = '/uploads/variations/' . $new_filename;
            }
        }

        // Prepare data
        $data = [
            ':product_id' => $product_id,
            ':sku' => $variation['sku'],
            ':size_value_id' => $variation['size'] ?: null,
            ':metal_value_id' => $variation['metal'] ?: null,
            ':gemstone_value_id' => $variation['gemstone'] ?: null,
            ':finish_value_id' => $variation['finish'] ?: null,
            ':price_adjustment' => $variation['price_adjustment'],
            ':stock' => $variation['stock'],
            ':image_path' => $image_path
        ];

        if (strpos($variation_id, 'new_') === 0) {
            // Insert new variation
            $insert_stmt->execute($data);
        } else {
            // Update existing variation
            $data[':id'] = $variation_id;
            $update_stmt->execute($data);
        }
    }

    // Commit transaction
    $db->commit();

    // Redirect back with success message
    redirectWithMessage(
        "/Glamour-shopv1.3/admin/products/ring-variations.php?id=$product_id",
        'success',
        'Ring variations saved successfully'
    );

} catch (PDOException $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    redirectWithMessage(
        "/Glamour-shopv1.3/admin/products/ring-variations.php?id=$product_id",
        'error',
        'Error saving ring variations: ' . $e->getMessage()
    );
} 