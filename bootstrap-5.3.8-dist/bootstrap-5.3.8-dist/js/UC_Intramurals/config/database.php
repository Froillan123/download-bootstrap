<?php
// Database Configuration for UC Intramurals System - Supports both SQLite and MySQL
// Automatically creates database/tables as needed

// Choose your database type: 'mysql' or 'sqlite'
define('DB_TYPE', 'mysql'); 

if (DB_TYPE === 'mysql') {
    // MySQL Configuration (XAMPP)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'uc_intramurals');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    
    try {
        // Try to connect to existing database
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create tables if they don't exist
        createMySQLTables($pdo);
        
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
                createMySQLTables($pdo);
            } catch(PDOException $createDbException) {
                die("MySQL Database Creation failed: " . $createDbException->getMessage());
            }
        } else {
            die("MySQL Connection failed: " . $e->getMessage());
        }
    }
    
} else {
    // SQLite Configuration
    define('DB_FILE', 'database/uc_intramurals.db');
    
    // Ensure database directory exists
    $dbDir = dirname(DB_FILE);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }
    
    try {
        $pdo = new PDO("sqlite:" . DB_FILE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create tables if they don't exist
        createSQLiteTables($pdo);
        
    } catch(PDOException $e) {
        die("SQLite Connection failed: " . $e->getMessage());
    }
}

// Function to create MySQL tables
function createMySQLTables($pdo) {
    $sql = "
    CREATE TABLE IF NOT EXISTS department (
        deptId INT AUTO_INCREMENT PRIMARY KEY,
        deptName VARCHAR(255) NOT NULL
    );
    
    CREATE TABLE IF NOT EXISTS registrations (
        userName VARCHAR(255) PRIMARY KEY,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'tournament_manager', 'coach', 'dean', 'athlete') NOT NULL
    );
    
    CREATE TABLE IF NOT EXISTS tournamentmanager (
        userName VARCHAR(255) PRIMARY KEY,
        fname VARCHAR(255) NOT NULL,
        lname VARCHAR(255) NOT NULL,
        mobile VARCHAR(20) NOT NULL,
        deptID INT NOT NULL,
        FOREIGN KEY (userName) REFERENCES registrations(userName) ON DELETE CASCADE,
        FOREIGN KEY (deptID) REFERENCES department(deptId) ON DELETE CASCADE
    );
    
    CREATE TABLE IF NOT EXISTS coach (
        userName VARCHAR(255) PRIMARY KEY,
        fname VARCHAR(255) NOT NULL,
        lname VARCHAR(255) NOT NULL,
        mobile VARCHAR(20) NOT NULL,
        deptID INT NOT NULL,
        FOREIGN KEY (userName) REFERENCES registrations(userName) ON DELETE CASCADE,
        FOREIGN KEY (deptID) REFERENCES department(deptId) ON DELETE CASCADE
    );
    
    CREATE TABLE IF NOT EXISTS dean (
        userName VARCHAR(255) PRIMARY KEY,
        fname VARCHAR(255) NOT NULL,
        lname VARCHAR(255) NOT NULL,
        mobile VARCHAR(20) NOT NULL,
        deptID INT NOT NULL,
        FOREIGN KEY (userName) REFERENCES registrations(userName) ON DELETE CASCADE,
        FOREIGN KEY (deptID) REFERENCES department(deptId) ON DELETE CASCADE
    );
    
    CREATE TABLE IF NOT EXISTS event (
        EventID INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(255) NOT NULL,
        eventName VARCHAR(255) NOT NULL,
        noOfParticipants INT NOT NULL,
        tournamentmanager VARCHAR(255) NOT NULL,
        FOREIGN KEY (tournamentmanager) REFERENCES tournamentmanager(userName) ON DELETE CASCADE
    );
    
    CREATE TABLE IF NOT EXISTS athlete_profile (
        IDnum VARCHAR(255) PRIMARY KEY,
        eventID INT NOT NULL,
        deptID INT NOT NULL,
        lastname VARCHAR(255) NOT NULL,
        firstname VARCHAR(255) NOT NULL,
        middleInit VARCHAR(10),
        course VARCHAR(255) NOT NULL,
        year INT NOT NULL,
        civilStatus VARCHAR(50) NOT NULL,
        gender ENUM('Male', 'Female', 'Other') NOT NULL,
        birthdate DATE NOT NULL,
        contactNo VARCHAR(20) NOT NULL,
        address TEXT NOT NULL,
        coachID VARCHAR(255) NOT NULL,
        deanID VARCHAR(255) NOT NULL,
        coach_approved ENUM('pending', 'approved', 'disapproved') DEFAULT 'pending',
        dean_approved ENUM('pending', 'approved', 'disapproved') DEFAULT 'pending',
        admin_approved ENUM('pending', 'approved', 'disapproved') DEFAULT 'pending',
        coach_approved_at DATETIME NULL,
        dean_approved_at DATETIME NULL,
        admin_approved_at DATETIME NULL,
        FOREIGN KEY (IDnum) REFERENCES registrations(userName) ON DELETE CASCADE,
        FOREIGN KEY (eventID) REFERENCES event(EventID) ON DELETE CASCADE,
        FOREIGN KEY (deptID) REFERENCES department(deptId) ON DELETE CASCADE,
        FOREIGN KEY (coachID) REFERENCES coach(userName) ON DELETE CASCADE,
        FOREIGN KEY (deanID) REFERENCES dean(userName) ON DELETE CASCADE
    );
    
    CREATE TABLE IF NOT EXISTS approval_log (
        logID INT AUTO_INCREMENT PRIMARY KEY,
        athleteID VARCHAR(255) NOT NULL,
        approver_role ENUM('coach', 'dean', 'admin') NOT NULL,
        approver_username VARCHAR(255) NOT NULL,
        action ENUM('approved', 'disapproved') NOT NULL,
        timestamp DATETIME NOT NULL,
        notes TEXT,
        FOREIGN KEY (athleteID) REFERENCES athlete_profile(IDnum) ON DELETE CASCADE
    );
    
    CREATE TABLE IF NOT EXISTS schedule (
        scheduleID INT AUTO_INCREMENT PRIMARY KEY,
        day DATE NOT NULL,
        timeStart TIME NOT NULL,
        timeEnd TIME NOT NULL,
        eventID INT NOT NULL,
        venue TEXT NOT NULL,
        inCharge TEXT NOT NULL,
        sro_nocp TEXT,
        FOREIGN KEY (eventID) REFERENCES event(EventID) ON DELETE CASCADE
    );
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
    
    // Add approval columns to existing athlete_profile table if they don't exist
    try {
        $pdo->exec("ALTER TABLE athlete_profile ADD COLUMN coach_approved ENUM('pending', 'approved', 'disapproved') DEFAULT 'pending'");
    } catch(PDOException $e) {
        // Column already exists, ignore
    }
    try {
        $pdo->exec("ALTER TABLE athlete_profile ADD COLUMN dean_approved ENUM('pending', 'approved', 'disapproved') DEFAULT 'pending'");
    } catch(PDOException $e) {
        // Column already exists, ignore
    }
    try {
        $pdo->exec("ALTER TABLE athlete_profile ADD COLUMN admin_approved ENUM('pending', 'approved', 'disapproved') DEFAULT 'pending'");
    } catch(PDOException $e) {
        // Column already exists, ignore
    }
    try {
        $pdo->exec("ALTER TABLE athlete_profile ADD COLUMN coach_approved_at DATETIME NULL");
    } catch(PDOException $e) {
        // Column already exists, ignore
    }
    try {
        $pdo->exec("ALTER TABLE athlete_profile ADD COLUMN dean_approved_at DATETIME NULL");
    } catch(PDOException $e) {
        // Column already exists, ignore
    }
    try {
        $pdo->exec("ALTER TABLE athlete_profile ADD COLUMN admin_approved_at DATETIME NULL");
    } catch(PDOException $e) {
        // Column already exists, ignore
    }
}

// Function to create SQLite tables
function createSQLiteTables($pdo) {
    $sql = "
    CREATE TABLE IF NOT EXISTS department (
        deptId INTEGER PRIMARY KEY AUTOINCREMENT,
        deptName TEXT NOT NULL
    );
    
    CREATE TABLE IF NOT EXISTS registrations (
        userName TEXT PRIMARY KEY,
        password TEXT NOT NULL,
        role TEXT NOT NULL CHECK(role IN ('admin', 'tournament_manager', 'coach', 'dean', 'athlete'))
    );
    
    CREATE TABLE IF NOT EXISTS tournamentmanager (
        userName TEXT PRIMARY KEY,
        fname TEXT NOT NULL,
        lname TEXT NOT NULL,
        mobile TEXT NOT NULL,
        deptID INTEGER NOT NULL,
        FOREIGN KEY (userName) REFERENCES registrations(userName) ON DELETE CASCADE,
        FOREIGN KEY (deptID) REFERENCES department(deptId) ON DELETE CASCADE
    );
    
    CREATE TABLE IF NOT EXISTS coach (
        userName TEXT PRIMARY KEY,
        fname TEXT NOT NULL,
        lname TEXT NOT NULL,
        mobile TEXT NOT NULL,
        deptID INTEGER NOT NULL,
        FOREIGN KEY (userName) REFERENCES registrations(userName) ON DELETE CASCADE,
        FOREIGN KEY (deptID) REFERENCES department(deptId) ON DELETE CASCADE
    );
    
    CREATE TABLE IF NOT EXISTS dean (
        userName TEXT PRIMARY KEY,
        fname TEXT NOT NULL,
        lname TEXT NOT NULL,
        mobile TEXT NOT NULL,
        deptID INTEGER NOT NULL,
        FOREIGN KEY (userName) REFERENCES registrations(userName) ON DELETE CASCADE,
        FOREIGN KEY (deptID) REFERENCES department(deptId) ON DELETE CASCADE
    );
    
    CREATE TABLE IF NOT EXISTS event (
        EventID INTEGER PRIMARY KEY AUTOINCREMENT,
        category TEXT NOT NULL,
        eventName TEXT NOT NULL,
        noOfParticipants INTEGER NOT NULL,
        tournamentmanager TEXT NOT NULL,
        FOREIGN KEY (tournamentmanager) REFERENCES tournamentmanager(userName) ON DELETE CASCADE
    );
    
    CREATE TABLE IF NOT EXISTS athlete_profile (
        IDnum TEXT PRIMARY KEY,
        eventID INTEGER NOT NULL,
        deptID INTEGER NOT NULL,
        lastname TEXT NOT NULL,
        firstname TEXT NOT NULL,
        middleInit TEXT,
        course TEXT NOT NULL,
        year INTEGER NOT NULL,
        civilStatus TEXT NOT NULL,
        gender TEXT NOT NULL CHECK(gender IN ('Male', 'Female', 'Other')),
        birthdate DATE NOT NULL,
        contactNo TEXT NOT NULL,
        address TEXT NOT NULL,
        coachID TEXT NOT NULL,
        deanID TEXT NOT NULL,
        coach_approved TEXT DEFAULT 'pending' CHECK(coach_approved IN ('pending', 'approved', 'disapproved')),
        dean_approved TEXT DEFAULT 'pending' CHECK(dean_approved IN ('pending', 'approved', 'disapproved')),
        admin_approved TEXT DEFAULT 'pending' CHECK(admin_approved IN ('pending', 'approved', 'disapproved')),
        coach_approved_at TEXT,
        dean_approved_at TEXT,
        admin_approved_at TEXT,
        FOREIGN KEY (IDnum) REFERENCES registrations(userName) ON DELETE CASCADE,
        FOREIGN KEY (eventID) REFERENCES event(EventID) ON DELETE CASCADE,
        FOREIGN KEY (deptID) REFERENCES department(deptId) ON DELETE CASCADE,
        FOREIGN KEY (coachID) REFERENCES coach(userName) ON DELETE CASCADE,
        FOREIGN KEY (deanID) REFERENCES dean(userName) ON DELETE CASCADE
    );
    
    CREATE TABLE IF NOT EXISTS approval_log (
        logID INTEGER PRIMARY KEY AUTOINCREMENT,
        athleteID TEXT NOT NULL,
        approver_role TEXT NOT NULL CHECK(approver_role IN ('coach', 'dean', 'admin')),
        approver_username TEXT NOT NULL,
        action TEXT NOT NULL CHECK(action IN ('approved', 'disapproved')),
        timestamp TEXT NOT NULL,
        notes TEXT,
        FOREIGN KEY (athleteID) REFERENCES athlete_profile(IDnum) ON DELETE CASCADE
    );
    
    CREATE TABLE IF NOT EXISTS schedule (
        scheduleID INTEGER PRIMARY KEY AUTOINCREMENT,
        day DATE NOT NULL,
        timeStart TIME NOT NULL,
        timeEnd TIME NOT NULL,
        eventID INTEGER NOT NULL,
        venue TEXT NOT NULL,
        inCharge TEXT NOT NULL,
        sro_nocp TEXT,
        FOREIGN KEY (eventID) REFERENCES event(EventID) ON DELETE CASCADE
    );
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
    
    // Add approval columns to existing athlete_profile table if they don't exist (SQLite)
    try {
        $pdo->exec("ALTER TABLE athlete_profile ADD COLUMN coach_approved TEXT DEFAULT 'pending'");
    } catch(PDOException $e) {
        // Column already exists, ignore
    }
    try {
        $pdo->exec("ALTER TABLE athlete_profile ADD COLUMN dean_approved TEXT DEFAULT 'pending'");
    } catch(PDOException $e) {
        // Column already exists, ignore
    }
    try {
        $pdo->exec("ALTER TABLE athlete_profile ADD COLUMN admin_approved TEXT DEFAULT 'pending'");
    } catch(PDOException $e) {
        // Column already exists, ignore
    }
    try {
        $pdo->exec("ALTER TABLE athlete_profile ADD COLUMN coach_approved_at TEXT");
    } catch(PDOException $e) {
        // Column already exists, ignore
    }
    try {
        $pdo->exec("ALTER TABLE athlete_profile ADD COLUMN dean_approved_at TEXT");
    } catch(PDOException $e) {
        // Column already exists, ignore
    }
    try {
        $pdo->exec("ALTER TABLE athlete_profile ADD COLUMN admin_approved_at TEXT");
    } catch(PDOException $e) {
        // Column already exists, ignore
    }
}

// Global database connection
$GLOBALS['pdo'] = $pdo;
?>