<?php
require_once __DIR__ . '/../includes/config.php';

try {
    $db = getDB();
    
    // Check if admin_users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'admin_users'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Create admin_users table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                reset_token VARCHAR(255),
                reset_token_expires DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "admin_users table created successfully!<br>";
    } else {
        echo "admin_users table already exists.<br>";
    }
    
    // Show table structure
    $stmt = $db->query("DESCRIBE admin_users");
    echo "<br>Table structure:<br>";
    while ($row = $stmt->fetch()) {
        echo htmlspecialchars($row['Field'] . " - " . $row['Type']) . "<br>";
    }
    
} catch (PDOException $e) {
    die('Database verification failed: ' . $e->getMessage());
} 