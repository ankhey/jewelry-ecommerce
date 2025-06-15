<?php
require_once __DIR__ . '/../includes/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: /Glamour-shopv1.3/admin/dashboard.php');
    exit();
}

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $errors = [];

    if (empty($email)) {
        $errors[] = 'Email is required';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    }

    if (empty($errors)) {
        try {
            $db = getDB();
            
            // Debug database connection
            echo "<!-- Database connection successful -->";
            
            $stmt = $db->prepare("SELECT * FROM admin_users WHERE email = :email");
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch();
            
            // Debug user query
            echo "<!-- User query executed. Found user: " . ($user ? 'yes' : 'no') . " -->";
            
            if ($user) {
                // Debug password verification
                $password_verify_result = password_verify($password, $user['password']);
                echo "<!-- Password verification result: " . ($password_verify_result ? 'true' : 'false') . " -->";
                
                if ($password_verify_result) {
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_name'] = $user['name'];
                    header('Location: /Glamour-shopv1.3/admin/dashboard.php');
                    exit();
                }
            }
            $errors[] = 'Invalid email or password';
            
        } catch (PDOException $e) {
            // Debug database errors
            error_log("Login error: " . $e->getMessage());
            echo "<!-- Database error: " . htmlspecialchars($e->getMessage()) . " -->";
            $errors[] = 'A system error occurred. Please try again later.';
        }
    }
}

// Set page title
$page_title = 'Admin Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background: none;
            border-bottom: none;
            text-align: center;
            padding: 20px;
        }
        .card-header img {
            max-height: 60px;
        }
        .card-body {
            padding: 30px;
        }
        .form-control {
            border-radius: 5px;
            padding: 10px 15px;
        }
        .btn-primary {
            padding: 10px 20px;
            border-radius: 5px;
        }
        .forgot-password {
            text-align: right;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-header">
                    <h1>Glamour Shop</h1>
                    <h4 class="mt-3">Admin Login</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="forgot-password">
                            <a href="/Glamour-shopv1.3/admin/forgot-password.php">Forgot Password?</a>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 