<?php
$current_page = basename($_SERVER['PHP_SELF']);
$is_admin = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
?>
<header class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="/Glamour-shopv1.3/<?php echo $is_admin ? 'admin/index.php' : 'public/index.php'; ?>">
            <?php echo SITE_NAME; ?><?php echo $is_admin ? ' Admin' : ''; ?>
        </a>
        
        <?php if (!$is_admin): ?>
            <a class="nav-link text-light position-relative ms-auto" href="/Glamour-shopv1.3/public/cart.php">
                <i class="bi bi-cart fs-4"></i>
                <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?php echo count($_SESSION['cart']); ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endif; ?>
    </div>
</header> 
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
<link rel="stylesheet" href="/Glamour-shopv1.3/assets/css/layout.css"> 