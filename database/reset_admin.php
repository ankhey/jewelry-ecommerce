<?php
require_once __DIR__ . '/../includes/config.php';

try {
    $db = getDB();
    
    // Create a new password hash
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // First try to update existing admin user
    $stmt = $db->prepare("
        UPDATE admin_users 
        SET password = :password 
        WHERE email = :email
    ");
    
    $result = $stmt->execute([
        ':email' => 'admin@glamourshop.com',
        ':password' => $hash
    ]);
    
    // If no rows were updated, insert new admin user
    if ($stmt->rowCount() === 0) {
        $stmt = $db->prepare("
            INSERT INTO admin_users (email, password, name) 
            VALUES (:email, :password, :name)
        ");
        
        $stmt->execute([
            ':email' => 'admin@glamourshop.com',
            ':password' => $hash,
            ':name' => 'Admin User'
        ]);
    }
    
    echo "Admin password has been reset successfully!<br>";
    echo "Email: admin@glamourshop.com<br>";
    echo "Password: admin123";
    
} catch (PDOException $e) {
    die('Failed to reset admin password: ' . $e->getMessage());
} 