<?php
// Database Configuration for Election System
// Automatically creates database/tables as needed

// MySQL Configuration (XAMPP)
define('DB_HOST', 'localhost');
define('DB_NAME', 'election');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    // Try to connect to existing database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables if they don't exist
    createElectionTables($pdo);
    
} catch(PDOException $e) {
    // If database doesn't exist, create it
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        try {
            // Connect without specifying database to create it
            $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create the database
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`");
            $pdo = null; // Close connection
            
            // Now connect to the created database
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create tables
            createElectionTables($pdo);
        } catch(PDOException $createDbException) {
            die("MySQL Database Creation failed: " . $createDbException->getMessage());
        }
    } else {
        die("MySQL Connection failed: " . $e->getMessage());
    }
}

// Function to create election tables
function createElectionTables($pdo) {
    $sql = "
    CREATE TABLE IF NOT EXISTS positions (
        position_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        position_name VARCHAR(100) NOT NULL UNIQUE,
        max_winners INT(11) DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    
    CREATE TABLE IF NOT EXISTS candidates (
        candidate_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        position_id INT(11) NOT NULL,
        photo VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (position_id) REFERENCES positions(position_id) ON DELETE CASCADE,
        KEY position_id (position_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    
    CREATE TABLE IF NOT EXISTS users (
        user_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'voter') DEFAULT 'voter',
        has_voted TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    
    CREATE TABLE IF NOT EXISTS votes (
        vote_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        voter_id INT(11) NOT NULL,
        candidate_id INT(11) NOT NULL,
        position_id INT(11) NOT NULL,
        voted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (voter_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (candidate_id) REFERENCES candidates(candidate_id) ON DELETE CASCADE,
        FOREIGN KEY (position_id) REFERENCES positions(position_id) ON DELETE CASCADE,
        KEY voter_id (voter_id),
        KEY candidate_id (candidate_id),
        KEY position_id (position_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    
    CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(50) NOT NULL PRIMARY KEY,
        setting_value TEXT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    
    CREATE TABLE IF NOT EXISTS winners (
        winner_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        position_id INT(11) NOT NULL,
        position_name VARCHAR(255) NOT NULL,
        candidate_id INT(11) NOT NULL,
        candidate_name VARCHAR(255) NOT NULL,
        vote_count INT(11) DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (position_id) REFERENCES positions(position_id),
        FOREIGN KEY (candidate_id) REFERENCES candidates(candidate_id),
        KEY position_id (position_id),
        KEY candidate_id (candidate_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    $statements = explode(';', $sql);
    foreach($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch(PDOException $e) {
                // Ignore errors for existing tables/columns
            }
        }
    }
}

// Global database connection (PDO)
$GLOBALS['pdo'] = $pdo;

// For backward compatibility with mysqli code, create mysqli connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>