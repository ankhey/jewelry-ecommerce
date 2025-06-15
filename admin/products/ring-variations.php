<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Check permissions
requirePermission('manage_products');

// Get product ID
$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    redirectWithMessage('/Glamour-shopv1.3/admin/products/index.php', 'error', 'Invalid product ID');
}

try {
    $db = getDB();

    // Get product details
    $stmt = $db->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        redirectWithMessage('/Glamour-shopv1.3/admin/products/index.php', 'error', 'Product not found');
    }

    // Get ring attributes
    $stmt = $db->query("SELECT * FROM ring_attributes ORDER BY type, name");
    $attributes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get attribute values
    $stmt = $db->prepare("
        SELECT rav.*, ra.name as attribute_name, ra.type 
        FROM ring_attribute_values rav
        JOIN ring_attributes ra ON rav.attribute_id = ra.id
        ORDER BY ra.type, ra.name, rav.value
    ");
    $stmt->execute();
    $attribute_values = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get existing variations
    $stmt = $db->prepare("
        SELECT rv.*,
               size.value as size_value,
               metal.value as metal_value,
               gemstone.value as gemstone_value,
               finish.value as finish_value
        FROM ring_variations rv
        LEFT JOIN ring_attribute_values size ON rv.size_value_id = size.id
        LEFT JOIN ring_attribute_values metal ON rv.metal_value_id = metal.id
        LEFT JOIN ring_attribute_values gemstone ON rv.gemstone_value_id = gemstone.id
        LEFT JOIN ring_attribute_values finish ON rv.finish_value_id = finish.id
        WHERE rv.product_id = :product_id
        ORDER BY rv.sku
    ");
    $stmt->execute([':product_id' => $product_id]);
    $variations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

$page_title = 'Ring Variations';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .attribute-section {
            background: #f8f9fa;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .variation-row {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .preview-image {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="main-content">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3"><?php echo $page_title; ?></h1>
                <div>
                    <a href="/Glamour-shopv1.3/admin/products/index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Products
                    </a>
                </div>
            </div>

            <!-- Product Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($product['category_name']); ?></p>
                    <hr>
                    <p class="mb-0">
                        <strong>Base Price:</strong> KES <?php echo number_format($product['price'], 2); ?><br>
                        <strong>Stock:</strong> <?php echo $product['stock']; ?> units
                    </p>
                </div>
            </div>

            <!-- Attributes Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Ring Attributes</h5>
                </div>
                <div class="card-body">
                    <form id="attributesForm" method="POST" action="save-attributes.php">
                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                        
                        <!-- Size Attributes -->
                        <div class="attribute-section">
                            <h6>Ring Sizes</h6>
                            <div class="row g-3" id="sizeAttributes">
                                <?php
                                $size_values = array_filter($attribute_values, function($av) {
                                    return $av['type'] === 'size';
                                });
                                foreach ($size_values as $value): ?>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="attributes[size][]" 
                                               value="<?php echo $value['id']; ?>"
                                               id="size_<?php echo $value['id']; ?>">
                                        <label class="form-check-label" for="size_<?php echo $value['id']; ?>">
                                            <?php echo htmlspecialchars($value['value']); ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Metal Types -->
                        <div class="attribute-section">
                            <h6>Metal Types</h6>
                            <div class="row g-3" id="metalAttributes">
                                <?php
                                $metal_values = array_filter($attribute_values, function($av) {
                                    return $av['type'] === 'metal';
                                });
                                foreach ($metal_values as $value): ?>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="attributes[metal][]" 
                                               value="<?php echo $value['id']; ?>"
                                               id="metal_<?php echo $value['id']; ?>">
                                        <label class="form-check-label" for="metal_<?php echo $value['id']; ?>">
                                            <?php echo htmlspecialchars($value['value']); ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Gemstone Types -->
                        <div class="attribute-section">
                            <h6>Gemstone Types</h6>
                            <div class="row g-3" id="gemstoneAttributes">
                                <?php
                                $gemstone_values = array_filter($attribute_values, function($av) {
                                    return $av['type'] === 'gemstone';
                                });
                                foreach ($gemstone_values as $value): ?>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="attributes[gemstone][]" 
                                               value="<?php echo $value['id']; ?>"
                                               id="gemstone_<?php echo $value['id']; ?>">
                                        <label class="form-check-label" for="gemstone_<?php echo $value['id']; ?>">
                                            <?php echo htmlspecialchars($value['value']); ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Finish Types -->
                        <div class="attribute-section">
                            <h6>Finish Types</h6>
                            <div class="row g-3" id="finishAttributes">
                                <?php
                                $finish_values = array_filter($attribute_values, function($av) {
                                    return $av['type'] === 'finish';
                                });
                                foreach ($finish_values as $value): ?>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="attributes[finish][]" 
                                               value="<?php echo $value['id']; ?>"
                                               id="finish_<?php echo $value['id']; ?>">
                                        <label class="form-check-label" for="finish_<?php echo $value['id']; ?>">
                                            <?php echo htmlspecialchars($value['value']); ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Attributes
                        </button>
                    </form>
                </div>
            </div>

            <!-- Variations Section -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Ring Variations</h5>
                </div>
                <div class="card-body">
                    <form id="variationsForm" method="POST" action="save-variations.php" enctype="multipart/form-data">
                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                        
                        <div id="variationsContainer">
                            <?php foreach ($variations as $variation): ?>
                            <div class="variation-row" data-variation-id="<?php echo $variation['id']; ?>">
                                <div class="row g-3">
                                    <div class="col-md-2">
                                        <label class="form-label">SKU</label>
                                        <input type="text" class="form-control" name="variations[<?php echo $variation['id']; ?>][sku]" 
                                               value="<?php echo htmlspecialchars($variation['sku']); ?>" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Size</label>
                                        <select class="form-select" name="variations[<?php echo $variation['id']; ?>][size]">
                                            <option value="">Select Size</option>
                                            <?php foreach ($size_values as $value): ?>
                                            <option value="<?php echo $value['id']; ?>" 
                                                    <?php echo $variation['size_value_id'] == $value['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($value['value']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Metal</label>
                                        <select class="form-select" name="variations[<?php echo $variation['id']; ?>][metal]">
                                            <option value="">Select Metal</option>
                                            <?php foreach ($metal_values as $value): ?>
                                            <option value="<?php echo $value['id']; ?>"
                                                    <?php echo $variation['metal_value_id'] == $value['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($value['value']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Gemstone</label>
                                        <select class="form-select" name="variations[<?php echo $variation['id']; ?>][gemstone]">
                                            <option value="">Select Gemstone</option>
                                            <?php foreach ($gemstone_values as $value): ?>
                                            <option value="<?php echo $value['id']; ?>"
                                                    <?php echo $variation['gemstone_value_id'] == $value['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($value['value']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Finish</label>
                                        <select class="form-select" name="variations[<?php echo $variation['id']; ?>][finish]">
                                            <option value="">Select Finish</option>
                                            <?php foreach ($finish_values as $value): ?>
                                            <option value="<?php echo $value['id']; ?>"
                                                    <?php echo $variation['finish_value_id'] == $value['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($value['value']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Price Adjustment</label>
                                        <div class="input-group">
                                            <span class="input-group-text">KES</span>
                                            <input type="number" class="form-control" 
                                                   name="variations[<?php echo $variation['id']; ?>][price_adjustment]"
                                                   value="<?php echo $variation['price_adjustment']; ?>" step="0.01">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Stock</label>
                                        <input type="number" class="form-control" 
                                               name="variations[<?php echo $variation['id']; ?>][stock]"
                                               value="<?php echo $variation['stock']; ?>" min="0" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Image</label>
                                        <input type="file" class="form-control" 
                                               name="variations[<?php echo $variation['id']; ?>][image]"
                                               accept="image/*">
                                        <?php if ($variation['image_path']): ?>
                                        <img src="<?php echo htmlspecialchars($variation['image_path']); ?>" 
                                             class="preview-image mt-2" alt="Variation image">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="button" class="btn btn-danger d-block w-100 delete-variation" 
                                                data-variation-id="<?php echo $variation['id']; ?>">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" class="btn btn-secondary mt-3" id="addVariation">
                            <i class="bi bi-plus-lg"></i> Add Variation
                        </button>

                        <button type="submit" class="btn btn-primary mt-3">
                            <i class="bi bi-save"></i> Save Variations
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Add new variation
        $('#addVariation').click(function() {
            const variationId = 'new_' + Date.now();
            const template = `
                <div class="variation-row" data-variation-id="${variationId}">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">SKU</label>
                            <input type="text" class="form-control" name="variations[${variationId}][sku]" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Size</label>
                            <select class="form-select" name="variations[${variationId}][size]">
                                <option value="">Select Size</option>
                                <?php foreach ($size_values as $value): ?>
                                <option value="<?php echo $value['id']; ?>">
                                    <?php echo htmlspecialchars($value['value']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Metal</label>
                            <select class="form-select" name="variations[${variationId}][metal]">
                                <option value="">Select Metal</option>
                                <?php foreach ($metal_values as $value): ?>
                                <option value="<?php echo $value['id']; ?>">
                                    <?php echo htmlspecialchars($value['value']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Gemstone</label>
                            <select class="form-select" name="variations[${variationId}][gemstone]">
                                <option value="">Select Gemstone</option>
                                <?php foreach ($gemstone_values as $value): ?>
                                <option value="<?php echo $value['id']; ?>">
                                    <?php echo htmlspecialchars($value['value']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Finish</label>
                            <select class="form-select" name="variations[${variationId}][finish]">
                                <option value="">Select Finish</option>
                                <?php foreach ($finish_values as $value): ?>
                                <option value="<?php echo $value['id']; ?>">
                                    <?php echo htmlspecialchars($value['value']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Price Adjustment</label>
                            <div class="input-group">
                                <span class="input-group-text">KES</span>
                                <input type="number" class="form-control" 
                                       name="variations[${variationId}][price_adjustment]" step="0.01" value="0">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Stock</label>
                            <input type="number" class="form-control" 
                                   name="variations[${variationId}][stock]" min="0" value="0" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control" 
                                   name="variations[${variationId}][image]" accept="image/*">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-danger d-block w-100 delete-variation" 
                                    data-variation-id="${variationId}">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            `;
            $('#variationsContainer').append(template);
        });

        // Delete variation
        $(document).on('click', '.delete-variation', function() {
            const variationId = $(this).data('variation-id');
            if (confirm('Are you sure you want to delete this variation?')) {
                if (variationId.toString().startsWith('new_')) {
                    $(this).closest('.variation-row').remove();
                } else {
                    // Send AJAX request to delete variation
                    $.post('delete-variation.php', {
                        variation_id: variationId,
                        product_id: <?php echo $product_id; ?>
                    }, function(response) {
                        if (response.success) {
                            $(`[data-variation-id="${variationId}"]`).remove();
                        } else {
                            alert('Error deleting variation: ' + response.message);
                        }
                    });
                }
            }
        });

        // Image preview
        $(document).on('change', 'input[type="file"]', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $(e.target).closest('.col-md-2').find('.preview-image').remove();
                    $(e.target).closest('.col-md-2').append(
                        `<img src="${e.target.result}" class="preview-image mt-2" alt="Variation image">`
                    );
                }
                reader.readAsDataURL(file);
            }
        });
    });
    </script>
</body>
</html> 