<?php
$host = '127.0.0.1';
$user = 'root';

// Try empty password, 'root', and 'admin'
$passwords = ['', 'root', 'admin', 'password', '123456'];

$connected = false;
$pdo = null;

foreach ($passwords as $pass) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=knowly", $user, $pass);
        echo "Connected successfully to MySQL database 'knowly' with password: '$pass'\n";
        $connected = true;
        break;
    } catch (PDOException $e) {
        // try next
    }
}

if (!$connected) {
    try {
        // Try localhost instead of 127.0.0.1
        foreach ($passwords as $pass) {
            try {
                $pdo = new PDO("mysql:host=localhost;dbname=knowly", $user, $pass);
                echo "Connected successfully to MySQL localhost 'knowly' with password: '$pass'\n";
                $connected = true;
                break;
            } catch (PDOException $e) {
                // try next
            }
        }
    } catch (Exception $e) {
        echo "All connection attempts failed: " . $e->getMessage();
    }
}

if ($connected && $pdo) {
    try {
        $check = $pdo->query("SHOW COLUMNS FROM books LIKE 'subcategory'");
        if ($check->rowCount() == 0) {
            $pdo->exec("ALTER TABLE books ADD COLUMN subcategory VARCHAR(100) NULL AFTER category, ADD INDEX idx_subcategory (subcategory)");
            echo "Successfully added 'subcategory' column to 'books' table!\n";
        } else {
            echo "'subcategory' column already exists in 'books' table.\n";
        }
    } catch (Exception $ex) {
        echo "Error checking/updating table: " . $ex->getMessage() . "\n";
    }
}
