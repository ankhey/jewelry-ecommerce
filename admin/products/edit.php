<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check permissions
requirePermission('manage_products');

// Get product ID
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: /admin/products/index.php');
    exit();
}

try {
    $db = getDB();
    
    // Get product details with category information
    $stmt = $db->prepare("
        SELECT p.*, c.slug as category_slug 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
        header('Location: /Glamour-shopv1.3/admin/products/index.php');
    exit();
}

// Get categories for dropdown
    $stmt = $db->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate required fields
    $required_fields = ['name', 'description', 'price', 'stock', 'category_id'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst($field) . ' is required';
        }
    }
    
    // Validate price
    if (!empty($_POST['price']) && !is_numeric($_POST['price']) || $_POST['price'] < 0) {
        $errors[] = 'Price must be a positive number';
    }
    
    // Validate stock
    if (!empty($_POST['stock']) && (!is_numeric($_POST['stock']) || $_POST['stock'] < 0)) {
        $errors[] = 'Stock must be a positive number';
    }
    
        if (empty($errors)) {
            try {
    // Handle image upload
                $image_path = $product['image_path']; // Keep existing image path by default
                
    if (!empty($_FILES['image']['name'])) {
                    $upload_dir = __DIR__ . '/../../assets/images/products/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (!in_array($file_extension, $allowed_extensions)) {
                        throw new Exception('Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.');
                    }
                    
                    $image_name = uniqid() . '.' . $file_extension;
                    $target_path = $upload_dir . $image_name;
                    
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                        throw new Exception('Failed to upload image.');
                    }
                    
                    // Delete old image if it exists
                    if (!empty($product['image_path'])) {
                        $old_image_path = __DIR__ . '/../../' . $product['image_path'];
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            }
            
                    $image_path = 'assets/images/products/' . $image_name;
                }
                
                // Generate slug from name
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['name'])));
                $original_slug = $slug;
                $counter = 1;
                
                // Check if slug exists (excluding current product)
                $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE slug = :slug AND id != :id");
                $stmt->execute([':slug' => $slug, ':id' => $id]);
                while ($stmt->fetchColumn() > 0) {
                    $slug = $original_slug . '-' . $counter;
                    $counter++;
                    $stmt->execute([':slug' => $slug, ':id' => $id]);
                }
                
            // Update product
            $stmt = $db->prepare("
                UPDATE products SET 
                    name = :name,
                    description = :description,
                    price = :price,
                    stock = :stock,
                    category_id = :category_id,
                        image_path = :image_path,
                    is_visible = :is_visible,
                        slug = :slug,
                        updated_at = NOW()
                WHERE id = :id
            ");
            
                $stmt->execute([
                    ':name' => $_POST['name'],
                    ':description' => $_POST['description'],
                    ':price' => $_POST['price'],
                    ':stock' => $_POST['stock'],
                    ':category_id' => $_POST['category_id'],
                    ':image_path' => $image_path,
                    ':is_visible' => isset($_POST['is_visible']) ? 1 : 0,
                    ':slug' => $slug,
                    ':id' => $id
                ]);
            
                // Handle variations for ring products
                if ($product['category_slug'] === 'rings' && isset($_POST['variations'])) {
                    // Delete existing variations
                    $stmt = $db->prepare("DELETE FROM product_variations WHERE product_id = :product_id");
                    $stmt->execute([':product_id' => $id]);
                    
                    // Insert new variations
                    $stmt = $db->prepare("
                        INSERT INTO product_variations (product_id, name, stock) 
                        VALUES (:product_id, :name, :stock)
                    ");
                    
                    foreach ($_POST['variations'] as $variation) {
                        if ($variation['stock'] > 0) {
                            $stmt->execute([
                                ':product_id' => $id,
                                ':name' => $variation['name'],
                                ':stock' => $variation['stock']
                            ]);
                        }
                    }
                }
                
            // Create notification
            createNotification($db, 'Product Updated', "Product #$id has been updated.");
            
            // Redirect to product list
                header('Location: /Glamour-shopv1.3/admin/products/index.php');
            exit();
        } catch (Exception $e) {
            $errors[] = 'Error updating product: ' . $e->getMessage();
        }
    }
    }
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// Set page title
$page_title = 'Edit Product';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <style>
        .main-content {
            padding: 20px;
            transition: margin-left 0.3s;
            margin-left: 100px;
            width: calc(100% - 100px);
            position: relative;
            min-height: 100vh;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="main-content">
        <div class="container-fluid px-4">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3"><?php echo $page_title; ?></h1>
                        <a href="/Glamour-shopv1.3/admin/products/index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Products
                        </a>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Product Name</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="10" 
                                                      required><?php echo htmlspecialchars($product['description']); ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="category_id" class="form-label">Category</label>
                                            <select class="form-select" id="category_id" name="category_id" required>
                                                <option value="">Select Category</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>" 
                                                            <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="price" class="form-label">Price</label>
                                            <div class="input-group">
                                                    <span class="input-group-text">KES</span>
                                                <input type="number" class="form-control" id="price" name="price" 
                                                       value="<?php echo htmlspecialchars($product['price']); ?>" 
                                                       step="0.01" min="0" required>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="stock" class="form-label">Stock</label>
                                            <input type="number" class="form-control" id="stock" name="stock" 
                                                   value="<?php echo htmlspecialchars($product['stock']); ?>" 
                                                   min="0" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="image" class="form-label">Product Image</label>
                                                <?php if (!empty($product['image_path'])): ?>
                                                <div class="mb-2">
                                                        <img src="/Glamour-shopv1.3/<?php echo htmlspecialchars($product['image_path']); ?>" 
                                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                             class="img-thumbnail" 
                                                             style="width: 200px; height: 200px; object-fit: cover;">
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                                <div class="form-text">
                                                    Recommended size: 800x800 pixels. Images will be displayed as squares.<br>
                                                    Supported formats: JPG, JPEG, PNG, GIF
                                                </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="is_visible" name="is_visible" 
                                                       <?php echo $product['is_visible'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_visible">Active</label>
                                            </div>
                                        </div>

                                        <?php if ($product['category_slug'] === 'rings'): ?>
                                            <div class="mb-3">
                                                <label class="form-label">Ring Sizes</label>
                                                <div class="ring-sizes-container">
                                                    <?php
                                                    // Get existing variations
                                                    $stmt = $db->prepare("
                                                        SELECT * FROM product_variations 
                                                        WHERE product_id = :product_id 
                                                        AND name LIKE 'Size %'
                                                        ORDER BY CAST(SUBSTRING(name, 6) AS UNSIGNED)
                                                    ");
                                                    $stmt->execute([':product_id' => $id]);
                                                    $variations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                    
                                                    // Create a map of existing variations
                                                    $variation_map = [];
                                                    foreach ($variations as $variation) {
                                                        $size = (int)substr($variation['name'], 6);
                                                        $variation_map[$size] = $variation;
                                                    }
                                                    
                                                    // Display size options from 17 to 20
                                                    for ($size = 17; $size <= 20; $size++):
                                                        $variation = $variation_map[$size] ?? null;
                                                    ?>
                                                        <div class="ring-size-edit mb-2">
                                                            <div class="input-group">
                                                                <span class="input-group-text">Size <?php echo $size; ?></span>
                                                                <input type="number" 
                                                                       class="form-control" 
                                                                       name="variations[<?php echo $size; ?>][stock]" 
                                                                       value="<?php echo $variation ? $variation['stock'] : 0; ?>"
                                                                       min="0"
                                                                       placeholder="Stock">
                                                                <input type="hidden" 
                                                                       name="variations[<?php echo $size; ?>][id]" 
                                                                       value="<?php echo $variation ? $variation['id'] : ''; ?>">
                                                                <input type="hidden" 
                                                                       name="variations[<?php echo $size; ?>][name]" 
                                                                       value="Size <?php echo $size; ?>">
                                                            </div>
                                                        </div>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                        <a href="/Glamour-shopv1.3/admin/products/index.php" class="btn btn-secondary">
                                            <i class="bi bi-x"></i> Cancel
                                        </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#description').summernote({
                height: 300,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
        });
    </script>
</body>
</html> 