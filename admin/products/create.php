<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// Check permissions
requirePermission('manage_products');

try {
    $db = getDB();

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
    
    // Handle image upload
    $image_path = '';
    if (!empty($_FILES['image']['name'])) {
        $upload_dir = __DIR__ . '/../../assets/images/products/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Get file info
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $_FILES['image']['tmp_name']);
        finfo_close($file_info);
        
        // Define allowed MIME types
        $allowed_mime_types = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif'
        ];
        
        // Check file type
        if (!in_array($mime_type, $allowed_mime_types)) {
            $errors[] = 'Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.';
        } else {
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $image_name = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $image_name;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $errors[] = 'Failed to upload image. Please try again.';
            } else {
                $image_path = 'assets/images/products/' . $image_name;
            }
        }
    }
    
    if (empty($errors)) {
        try {
                // Generate a unique slug
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['name'])));
                $original_slug = $slug;
                $counter = 1;
                
                // Check if slug exists
                $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE slug = :slug");
                $stmt->execute([':slug' => $slug]);
                while ($stmt->fetchColumn() > 0) {
                    $slug = $original_slug . '-' . $counter;
                    $counter++;
                    $stmt->execute([':slug' => $slug]);
                }
            
            // Insert product
            $stmt = $db->prepare("
                INSERT INTO products (
                    name, description, price, stock, category_id, 
                        image_path, is_visible, slug, created_at, updated_at
                ) VALUES (
                    :name, :description, :price, :stock, :category_id,
                        :image_path, :is_visible, :slug, NOW(), NOW()
                    )
                ");
                
                $stmt->execute([
                    ':name' => $_POST['name'],
                    ':description' => $_POST['description'],
                    ':price' => $_POST['price'],
                    ':stock' => $_POST['stock'],
                    ':category_id' => $_POST['category_id'],
                    ':image_path' => $image_path,
                    ':is_visible' => isset($_POST['is_visible']) ? 1 : 0,
                    ':slug' => $slug
                ]);
                
                $product_id = $db->lastInsertId();
                
                // Handle ring sizes if category is rings
                if ($_POST['category_id'] == 1) { // Assuming 1 is the ID for rings category
                    if (!empty($_POST['ring_sizes'])) {
                        foreach ($_POST['ring_sizes'] as $size_data) {
                            if (!empty($size_data['size']) && !empty($size_data['quantity'])) {
                                $stmt = $db->prepare("
                                    INSERT INTO product_variations (
                                        product_id, name, price_adjustment, stock, created_at, updated_at
                                    ) VALUES (
                                        :product_id, :name, 0, :stock, NOW(), NOW()
                                    )
                                ");
                                
                                $stmt->execute([
                                    ':product_id' => $product_id,
                                    ':name' => 'Size ' . $size_data['size'],
                                    ':stock' => $size_data['quantity']
                                ]);
                            }
                        }
                    }
                }
            
            // Create notification
            createNotification($db, 'New Product Added', "Product #$product_id has been added.");
            
            // Redirect to product list
                header('Location: /Glamour-shopv1.3/admin/products/index.php');
            exit();
            } catch (PDOException $e) {
                $errors[] = 'Error creating product: ' . $e->getMessage();
        } catch (Exception $e) {
                $errors[] = 'Error uploading image: ' . $e->getMessage();
            }
        }
    }
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// Set page title
$page_title = 'Add Product';
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
        .admin-layout {
            display: flex;
            min-height: 100vh;
            margin-left: 250px; /* Width of the sidebar */
        }
        .main-content {
            flex: 1;
            padding: 2rem;
            background-color: #f8f9fa;
            width: calc(100% - 250px); /* Subtract sidebar width */
        }
        .content-wrapper {
            max-width: 1400px;
            margin: 0 auto;
        }
        .form-section {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .form-section h2 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 4px;
            margin-top: 1rem;
        }
        .ring-size-row {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .admin-layout {
                margin-left: 0;
            }
            .main-content {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="admin-layout">
    <div class="main-content">
            <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0"><?php echo $page_title; ?></h1>
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

                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-4">
                        <!-- Basic Information Section -->
                        <div class="col-12">
                            <div class="form-section">
                                <h2><i class="bi bi-info-circle"></i> Basic Information</h2>
                                <div class="row g-3">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id" required>
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
                                </div>
                            </div>
                        </div>

                        <!-- Ring Sizes Section -->
                        <div class="col-12" id="ring-sizes-container" style="display: none;">
                            <div class="form-section">
                                <h2><i class="bi bi-grid"></i> Ring Size Variations</h2>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Add ring sizes and their quantities below. Click "Save Variations" to store them.
                                </div>
                                <div id="ring-sizes-inputs">
                                    <!-- Ring sizes will be added here dynamically -->
                                </div>
                                <div class="d-flex gap-2 mt-3">
                                    <button type="button" class="btn btn-secondary" id="add-ring-size">
                                        <i class="bi bi-plus"></i> Add Size
                                    </button>
                                    <button type="button" class="btn btn-success" id="save-variations">
                                        <i class="bi bi-save"></i> Save Variations
                                    </button>
                                </div>
                                <div id="variations-saved-message" class="alert alert-success mt-3" style="display: none;">
                                    <i class="bi bi-check-circle"></i> Ring size variations saved successfully!
                                </div>
                                <div id="variations-table" class="mt-4">
                                    <h3 class="h5 mb-3">Saved Variations</h3>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Size</th>
                                                    <th>Quantity</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="saved-variations-list">
                                                <!-- Saved variations will be listed here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description Section -->
                        <div class="col-12">
                            <div class="form-section">
                                <h2><i class="bi bi-pencil-square"></i> Description</h2>
                                <textarea class="form-control" id="description" name="description" rows="4" 
                                          required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>
                            </div>
                            
                        <!-- Pricing and Stock Section -->
                        <div class="col-md-6">
                            <div class="form-section">
                                <h2><i class="bi bi-currency-dollar"></i> Pricing & Stock</h2>
                                <div class="row g-3">
                                    <div class="col-md-6">
                            <div class="mb-3">
                                <label for="price" class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="price" name="price" 
                                           value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" 
                                           step="0.01" min="0" required>
                                </div>
                            </div>
                                    </div>
                                    <div class="col-md-6">
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock</label>
                                <input type="number" class="form-control" id="stock" name="stock" 
                                       value="<?php echo isset($_POST['stock']) ? htmlspecialchars($_POST['stock']) : ''; ?>" 
                                       min="0" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                </div>
                            
                        <!-- Image Upload Section -->
                        <div class="col-md-6">
                            <div class="form-section">
                                <h2><i class="bi bi-image"></i> Product Image</h2>
                            <div class="mb-3">
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                                    <div class="form-text">Recommended size: 800x800 pixels. Supported formats: JPG, JPEG, PNG, GIF</div>
                                    <div id="image-preview" class="mt-2"></div>
                                </div>
                            </div>
                            </div>
                            
                        <!-- Visibility Section -->
                        <div class="col-12">
                            <div class="form-section">
                                <h2><i class="bi bi-eye"></i> Visibility</h2>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="is_visible" name="is_visible" 
                                           <?php echo !isset($_POST['is_visible']) || $_POST['is_visible'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_visible">Active (Show in shop)</label>
                            </div>
                        </div>
                    </div>
                    
                        <!-- Submit Button -->
                        <div class="col-12">
                            <div class="form-section">
                    <div class="text-end">
                                    <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Save Product
                        </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <script>
        $(document).ready(function() {
            // Store saved variations
            let savedVariations = [];
            
            // Image preview
            $('#image').change(function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#image-preview').html(`<img src="${e.target.result}" class="preview-image">`);
                    }
                    reader.readAsDataURL(file);
                }
            });

            // Show/hide ring sizes based on category selection
            $('#category_id').change(function() {
                var isRingCategory = $(this).val() == '1';
                $('#ring-sizes-container').toggle(isRingCategory);
                if (!isRingCategory) {
                    $('#ring-sizes-inputs').empty();
                    savedVariations = [];
                    updateVariationsTable();
                }
            });
            
            // Add new ring size input
            $('#add-ring-size').click(function() {
                addRingSizeInput();
            });
            
            // Remove ring size input
            $(document).on('click', '.remove-size', function() {
                $(this).closest('.ring-size-row').remove();
                updateRemoveButtons();
            });

            // Save variations
            $('#save-variations').click(function() {
                const variations = [];
                let hasError = false;

                $('#ring-sizes-inputs .ring-size-row').each(function() {
                    const size = $(this).find('input[name^="ring_sizes"][name$="[size]"]').val();
                    const quantity = $(this).find('input[name^="ring_sizes"][name$="[quantity]"]').val();

                    if (size && quantity) {
                        variations.push({
                            size: size,
                            quantity: parseInt(quantity)
                        });
                    } else {
                        hasError = true;
                    }
                });

                if (hasError) {
                    alert('Please fill in both size and quantity for all variations.');
                    return;
                }

                if (variations.length === 0) {
                    alert('Please add at least one ring size variation.');
                    return;
                }

                savedVariations = variations;
                updateVariationsTable();
                
                // Show success message
                $('#variations-saved-message').fadeIn().delay(3000).fadeOut();
                
                // Clear input fields
                $('#ring-sizes-inputs').empty();
                addRingSizeInput();
            });

            // Remove saved variation
            $(document).on('click', '.remove-saved-variation', function() {
                const index = $(this).data('index');
                savedVariations.splice(index, 1);
                updateVariationsTable();
            });
            
            function addRingSizeInput() {
                var index = $('#ring-sizes-inputs .ring-size-row').length;
                var html = `
                    <div class="ring-size-row">
                        <div class="row g-3">
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="ring_sizes[${index}][size]" 
                                   placeholder="Size (e.g., 7.5, 8, 8.5)" pattern="[0-9]+(\.[0-9]+)?">
                        </div>
                        <div class="col-md-5">
                            <input type="number" class="form-control" name="ring_sizes[${index}][quantity]" 
                                   placeholder="Quantity" min="1">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger remove-size">
                                <i class="bi bi-trash"></i>
                            </button>
                            </div>
                        </div>
                    </div>
                `;
                $('#ring-sizes-inputs').append(html);
                updateRemoveButtons();
            }
            
            function updateRemoveButtons() {
                var rows = $('#ring-sizes-inputs .ring-size-row');
                rows.find('.remove-size').show();
                if (rows.length === 1) {
                    rows.find('.remove-size').hide();
                }
            }

            function updateVariationsTable() {
                const tbody = $('#saved-variations-list');
                tbody.empty();

                if (savedVariations.length === 0) {
                    tbody.html('<tr><td colspan="3" class="text-center">No variations saved yet</td></tr>');
                    return;
                }

                savedVariations.forEach((variation, index) => {
                    tbody.append(`
                        <tr>
                            <td>${variation.size}</td>
                            <td>${variation.quantity}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger remove-saved-variation" data-index="${index}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `);
                });
            }
            
            // Initialize with one ring size input
            addRingSizeInput();
            
            // Trigger change event on page load
            $('#category_id').trigger('change');
            
            // Initialize Summernote with reduced height
            $('#description').summernote({
                height: 200,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });

            // Form submission
            $('form').on('submit', function(e) {
                if ($('#category_id').val() == '1' && savedVariations.length === 0) {
                    e.preventDefault();
                    alert('Please save at least one ring size variation before submitting the form.');
                    return false;
                }
            });
        });
    </script>
</body>
</html> 