<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$page_title = "Add Product";
$success_message = '';
$error_message = '';

// Get categories for the dropdown
try {
    $db = getDB();
    $stmt = $db->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error fetching categories: ' . $e->getMessage());
    $error_message = 'Error loading categories. Please try again.';
    $categories = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? 0;
        $stock = $_POST['stock'] ?? 0;
        $category_id = $_POST['category_id'] ?? null;
        $status = $_POST['status'] ?? 'active';

        // Validate required fields
        if (empty($name)) {
            throw new Exception('Product name is required.');
        }

        if (!is_numeric($price) || $price < 0) {
            throw new Exception('Price must be a valid number.');
        }

        if (!is_numeric($stock) || $stock < 0) {
            throw new Exception('Stock must be a valid number.');
        }

        // Handle image upload
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['image']['type'];
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Invalid image type. Only JPG, PNG and GIF are allowed.');
            }

            // Create upload directory if it doesn't exist
            $upload_dir = __DIR__ . '/../../uploads/products';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate unique filename
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('product_') . '.' . $extension;
            $target_path = $upload_dir . '/' . $filename;

            // Move uploaded file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = 'uploads/products/' . $filename;
            } else {
                throw new Exception('Failed to upload image.');
            }
        }

        // Insert product into database
        $sql = "
            INSERT INTO products (
                name, description, price, stock, category_id, 
                image_path, status, created_at, updated_at
            ) VALUES (
                :name, :description, :price, :stock, :category_id,
                :image_path, :status, NOW(), NOW()
            )
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':name' => $name,
            ':description' => $description,
            ':price' => $price,
            ':stock' => $stock,
            ':category_id' => $category_id ?: null,
            ':image_path' => $image_path,
            ':status' => $status
        ]);

        $success_message = 'Product added successfully!';
        
        // Redirect to products list after successful addition
        header('Location: index.php?success=Product added successfully');
        exit();

    } catch (Exception $e) {
        error_log('Error adding product: ' . $e->getMessage());
        $error_message = $e->getMessage();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" for="name">Product Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        <div class="invalid-feedback">Please enter a product name.</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" for="category_id">Category</label>
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo isset($_POST['category_id']) && $_POST['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" for="price">Price (KES) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required
                               value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                        <div class="invalid-feedback">Please enter a valid price.</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" for="stock">Stock <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="stock" name="stock" min="0" required
                               value="<?php echo htmlspecialchars($_POST['stock'] ?? ''); ?>">
                        <div class="invalid-feedback">Please enter a valid stock quantity.</div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" for="image">Product Image</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div class="form-text">Supported formats: JPG, PNG, GIF</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="col-12">
                    <hr>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Image preview
document.getElementById('image').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.createElement('img');
            preview.src = e.target.result;
            preview.style.maxWidth = '200px';
            preview.style.marginTop = '10px';
            preview.className = 'img-thumbnail';
            
            var container = document.getElementById('image').parentNode;
            var existingPreview = container.querySelector('img');
            if (existingPreview) {
                container.removeChild(existingPreview);
            }
            container.appendChild(preview);
        }
        reader.readAsDataURL(this.files[0]);
    }
});
</script> 