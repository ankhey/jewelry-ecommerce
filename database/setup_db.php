<?php
require_once __DIR__ . '/../includes/config.php';

try {
    $db = getDB();
    
    // Read the schema file
    $schema = file_get_contents(__DIR__ . '/schema_mysql.sql');
    
    // Split the schema into individual statements
    $statements = array_filter(
        array_map(
            'trim',
            explode(';', $schema)
        )
    );
    
    // Execute each statement
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $db->exec($statement);
        }
    }
    
    echo "Database schema updated successfully!<br>";
    echo "All tables created and default data inserted.<br>";
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
} 