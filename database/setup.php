<?php
require_once __DIR__ . '/../includes/config.php';

try {
    // Create database connection
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $pdo->exec("USE " . DB_NAME);

    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/schema_mysql.sql');
    $pdo->exec($schema);

    // Verify admin user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE email = 'admin@glamourshop.com'");
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        // Insert default admin user if not exists
        $stmt = $pdo->prepare("INSERT INTO admin_users (email, password, name) VALUES (?, ?, ?)");
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt->execute(['admin@glamourshop.com', $password_hash, 'Admin User']);
    }

    echo "Database setup completed successfully!\n";
    echo "Default admin credentials:\n";
    echo "Email: admin@glamourshop.com\n";
    echo "Password: admin123\n";

} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage() . "\n");
} 