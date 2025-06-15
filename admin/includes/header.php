<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../../includes/config.php';
}

// Check if admin is logged in and get admin name
$admin_name = $_SESSION['admin_name'] ?? 'Admin';

// Get unread notifications count
$db = getDB();
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0");
    $unread_notifications = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
} catch (PDOException $e) {
    $unread_notifications = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin'; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 250px;
            background: #2c3e50;
            padding-top: 1rem;
            transition: all 0.3s;
            z-index: 1040;
            border-right: 1px solid rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link i {
            font-size: 1.1rem;
        }

        /* Main content */
        .main-content {
            min-height: 100vh;
            background: #f8f9fa;
            padding: 0;
            margin-left: 120px;
            transition: margin-left 0.3s;
        }

        /* Admin Layout Selector 3 - For index pages with 250px margin */
        .admin-layout-3 {
            margin-left: 0;
            min-height: 100vh;
            background: #f8f9fa;
            padding: 1rem;
            margin-top: 30px;
        }

        /* Custom page layout - allows for more flexible positioning */
        .custom-page-layout {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 30px;
            padding: 1rem;
        }

        .custom-page-layout .sidebar-content {
            flex: 0 0 250px;
            background: #fff;
            border-radius: 0.25rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            padding: 1rem;
        }

        .custom-page-layout .main-area {
            flex: 1;
            min-width: 300px;
            background: #fff;
            border-radius: 0.25rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            padding: 1rem;
        }

        /* Admin Layout Selector 1: Top Filters with Content Below */
        .admin-layout-1 {
            margin-top: 30px;
            padding: 1rem;
        }
        
        .admin-layout-1 .filters-section {
            background: #fff;
            border-radius: 0.25rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
        }
        
        .admin-layout-1 .filters-section .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        }
        
        .admin-layout-1 .action-buttons {
            margin-bottom: 1.5rem;
        }
        
        .admin-layout-1 .content-section {
            background: #fff;
            border-radius: 0.25rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .admin-layout-1 .content-section .card-body {
            padding: 0;
        }
        
        .admin-layout-1 .table-responsive {
            margin: 0;
        }
        
        .admin-layout-1 .pagination {
            padding: 1rem;
            margin: 0;
            background-color: #f8f9fa;
            border-top: 1px solid rgba(0, 0, 0, 0.125);
        }

        /* Admin Layout Selector 2: Side-by-Side Layout */
        .admin-layout-2 {
            margin-top: 30px;
            padding: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        
        .admin-layout-2 .filters-section {
            flex: 0 0 300px;
            background: #fff;
            border-radius: 0.25rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .admin-layout-2 .content-section {
            flex: 1;
            min-width: 300px;
            background: #fff;
            border-radius: 0.25rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .admin-layout-2 .content-section .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-layout-2 .content-section .card-body {
            padding: 0;
        }
        
        .admin-layout-2 .table-responsive {
            margin: 0;
        }
        
        .admin-layout-2 .pagination {
            padding: 1rem;
            margin: 0;
            background-color: #f8f9fa;
            border-top: 1px solid rgba(0, 0, 0, 0.125);
        }

        /* Admin navbar */
        .admin-navbar {
            position: fixed;
            top: 0;
            left: 250px;
            right: 0;
            height: 60px;
            background: #fff;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            z-index: 1030;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: left 0.3s;
        }

        /* Sidebar toggle button */
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            color: #2c3e50;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            margin-right: 1rem;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .admin-navbar {
                left: 0;
            }

            .admin-navbar .container-fluid {
                padding: 0 0.5rem;
            }

            .sidebar-toggle {
                display: block;
            }

            .admin-navbar .navbar-brand {
                margin-left: 0;
            }

            /* Adjust main content padding when sidebar is open */
            body.sidebar-open .main-content {
                margin-left: 250px; /* Same width as sidebar */
            }
        }

        /* Smaller screens adjustments */
        @media (max-width: 576px) {
            .admin-layout-2 .filters-section {
                flex-basis: 100%; /* Stack filters on small screens */
            }
        }

        /* Ensure full height for sidebar content */
        .sidebar .d-flex.flex-column {
            height: 100%; /* Ensure the flex container takes full height */
        }

        /* Push logout to the bottom */
        .sidebar .nav-item:last-of-type {
            margin-top: auto; /* This pushes the last item (logout) to the bottom */
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 0.5rem;
        }

        /* Content area below navbar */
        .content-wrapper {
            margin-top: 60px;
            padding: 1.5rem;
        }

        /* Cards */
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        }

        /* Tables */
        .table > :not(caption) > * > * {
            padding: 1rem;
        }

        .table th {
            font-weight: 600;
            color: #2c3e50;
        }

        /* Buttons */
        .btn-group > .btn {
            padding: 0.375rem 0.75rem;
        }

        /* Brand */
        .navbar-brand {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.25rem;
            padding: 0;
            margin: 0;
        }

        /* User dropdown */
        .user-dropdown .dropdown-toggle::after {
            display: none;
        }

        .user-dropdown .dropdown-menu {
            min-width: 200px;
            padding: 0.5rem 0;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar d-flex flex-column">
        <div class="sidebar-header p-3">
            <h4 class="text-white mb-0"><?php echo SITE_NAME; ?> Admin</h4>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/dashboard.php') !== false ? 'active' : ''; ?>" href="/Glamour-shopv1.3/admin/dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/products/') !== false ? 'active' : ''; ?>" href="/Glamour-shopv1.3/admin/products/">
                    <i class="bi bi-box"></i> Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/orders/') !== false ? 'active' : ''; ?>" href="/Glamour-shopv1.3/admin/orders/">
                    <i class="bi bi-cart"></i> Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/customers/') !== false ? 'active' : ''; ?>" href="/Glamour-shopv1.3/admin/customers/">
                    <i class="bi bi-people"></i> Customers
                </a>
            </li>
            <!-- Logout Link -->
            <li class="nav-item">
                <a class="nav-link" href="/Glamour-shopv1.3/admin/logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="admin-navbar d-flex align-items-center">
            <div class="d-flex align-items-center">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="mb-0"><?php echo $page_title ?? 'Admin Dashboard'; ?></h5>
            </div>
            <div class="user-dropdown">
                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle text-dark text-decoration-none" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <span class="ms-2"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="/Glamour-shopv1.3/admin/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="content-wrapper">
            <?php if (isset($_SESSION['flash'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash']['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['flash']['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash']); endif; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('show');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const isClickInsideSidebar = sidebar.contains(event.target);
        const isClickOnToggle = sidebarToggle.contains(event.target);
        
        if (!isClickInsideSidebar && !isClickOnToggle && window.innerWidth <= 768) {
            sidebar.classList.remove('show');
        }
    });
});
</script>
</body>
</html> 