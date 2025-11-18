<?php
/**
 * Single-File Veterinarian System
 * Auto-creates database and tables on first access
 * Full CRUD for Veterinarians, Pet Owners, and Pets
 */

// Start output buffering to prevent any output before redirects
ob_start();
session_start();
date_default_timezone_set('Asia/Manila');

// ============================================================================
// DATABASE CONFIGURATION & AUTO-CREATION
// ============================================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'veterinarian');
define('DB_USER', 'root');
define('DB_PASS', '');

// Database connection with auto-creation
try {
    // Try to connect to existing database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Create tables if they don't exist
    createVeterinarianTables($pdo);
    
} catch(PDOException $e) {
    // If database doesn't exist, create it
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        try {
            // Connect without specifying database to create it
            $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create the database
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $pdo = null; // Close connection
            
            // Now connect to the created database
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Create tables
            createVeterinarianTables($pdo);
        } catch(PDOException $createDbException) {
            die("Database Creation failed: " . $createDbException->getMessage());
        }
    } else {
        die("Database Connection failed: " . $e->getMessage());
    }
}

// Function to create all veterinarian tables
function createVeterinarianTables($pdo) {
    $tables = [
        // Veterinarians Table
        "CREATE TABLE IF NOT EXISTS veterinarian (
            vetID INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            vetFName VARCHAR(50) NOT NULL,
            vetMName VARCHAR(50) DEFAULT NULL,
            vetLName VARCHAR(50) NOT NULL,
            vetSpecialty VARCHAR(100) DEFAULT NULL,
            vetPhone VARCHAR(20) DEFAULT NULL,
            vetEmail VARCHAR(100) DEFAULT NULL,
            vetStat ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        // Pet Owners Table
        "CREATE TABLE IF NOT EXISTS pet_owner (
            ownerID INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            ownerFName VARCHAR(50) NOT NULL,
            ownerMName VARCHAR(50) DEFAULT NULL,
            ownerLName VARCHAR(50) NOT NULL,
            ownerPhone VARCHAR(20) DEFAULT NULL,
            ownerEmail VARCHAR(100) DEFAULT NULL,
            ownerAddress TEXT DEFAULT NULL,
            ownerStat ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        // Pets Table
        "CREATE TABLE IF NOT EXISTS pet (
            petID INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            petName VARCHAR(100) NOT NULL,
            petType VARCHAR(50) DEFAULT NULL,
            petBreed VARCHAR(50) DEFAULT NULL,
            petAge INT(11) DEFAULT NULL,
            petGender ENUM('Male', 'Female', 'Unknown') DEFAULT 'Unknown',
            ownerID INT(11) NOT NULL,
            vetID INT(11) DEFAULT NULL,
            petStat ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ownerID) REFERENCES pet_owner(ownerID) ON DELETE CASCADE,
            FOREIGN KEY (vetID) REFERENCES veterinarian(vetID) ON DELETE SET NULL
        ) ENGINE=InnoDB",
        
        // Appointments Table
        "CREATE TABLE IF NOT EXISTS appointments (
            appointmentID INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            petID INT(11) NOT NULL,
            vetID INT(11) NOT NULL,
            ownerID INT(11) NOT NULL,
            appointmentDate DATE NOT NULL,
            appointmentTime TIME NOT NULL,
            appointmentReason TEXT DEFAULT NULL,
            appointmentStatus ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
            appointmentNotes TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (petID) REFERENCES pet(petID) ON DELETE CASCADE,
            FOREIGN KEY (vetID) REFERENCES veterinarian(vetID) ON DELETE CASCADE,
            FOREIGN KEY (ownerID) REFERENCES pet_owner(ownerID) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    ];
    
    foreach($tables as $sql) {
        try {
            $pdo->exec($sql);
        } catch(PDOException $e) {
            // Ignore errors for existing tables
        }
    }
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function redirect($url) {
    // Ensure no output before redirect
    if (ob_get_level()) {
        ob_end_clean();
    }
    // Build absolute URL if relative
    if (strpos($url, 'http') !== 0) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script = $_SERVER['SCRIPT_NAME'];
        $base = dirname($script);
        if ($base === '/' || $base === '\\' || $base === '.') {
            $base = '';
        }
        $base = str_replace('/index.php', '', $base);
        $url = $protocol . '://' . $host . $base . '/' . ltrim($url, '/');
    }
    header("Location: $url", true, 303); // 303 See Other - forces GET request, prevents POST resubmission
    exit;
}

function getFullName($fname, $mname, $lname) {
    $name = trim($fname);
    if (!empty($mname)) {
        $name .= ' ' . trim($mname);
    }
    $name .= ' ' . trim($lname);
    return $name;
}

// ============================================================================
// ROUTING SYSTEM
// ============================================================================

$page = isset($_GET['page']) ? $_GET['page'] : 'admin';
$action = isset($_POST['action']) ? $_POST['action'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get messages from session (for redirects)
$message = $_SESSION['message'] ?? '';
$messageType = $_SESSION['messageType'] ?? '';
// Clear session messages after reading
unset($_SESSION['message'], $_SESSION['messageType']);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    switch($page) {
        case 'veterinarian':
            handleVeterinarianAction($pdo, $action);
            break;
        case 'pet_owner':
            handlePetOwnerAction($pdo, $action);
            break;
        case 'pet':
            handlePetAction($pdo, $action);
            break;
        case 'appointments':
            handleAppointmentAction($pdo, $action);
            break;
    }
}

// ============================================================================
// HANDLERS FOR CRUD OPERATIONS
// ============================================================================

function handleVeterinarianAction($pdo, $action) {
    if ($action == 'add') {
        $fname = trim($_POST['vetFName'] ?? '');
        $mname = trim($_POST['vetMName'] ?? '');
        $lname = trim($_POST['vetLName'] ?? '');
        $specialty = trim($_POST['vetSpecialty'] ?? '');
        $phone = trim($_POST['vetPhone'] ?? '');
        $email = trim($_POST['vetEmail'] ?? '');
        
        if (empty($fname) || empty($lname)) {
            $_SESSION['message'] = "First name and last name are required.";
            $_SESSION['messageType'] = 'danger';
            redirect("?page=veterinarian");
            return;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO veterinarian (vetFName, vetMName, vetLName, vetSpecialty, vetPhone, vetEmail, vetStat) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$fname, $mname, $lname, $specialty, $phone, $email]);
            $_SESSION['message'] = "Veterinarian added successfully!";
            $_SESSION['messageType'] = 'success';
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['messageType'] = 'danger';
        }
        redirect("?page=veterinarian");
    } elseif ($action == 'edit') {
        $id = (int)($_POST['vetID'] ?? 0);
        $fname = trim($_POST['vetFName'] ?? '');
        $mname = trim($_POST['vetMName'] ?? '');
        $lname = trim($_POST['vetLName'] ?? '');
        $specialty = trim($_POST['vetSpecialty'] ?? '');
        $phone = trim($_POST['vetPhone'] ?? '');
        $email = trim($_POST['vetEmail'] ?? '');
        
        try {
            $stmt = $pdo->prepare("UPDATE veterinarian SET vetFName = ?, vetMName = ?, vetLName = ?, vetSpecialty = ?, vetPhone = ?, vetEmail = ? WHERE vetID = ?");
            $stmt->execute([$fname, $mname, $lname, $specialty, $phone, $email, $id]);
            $_SESSION['message'] = "Veterinarian updated successfully!";
            $_SESSION['messageType'] = 'success';
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['messageType'] = 'danger';
        }
        redirect("?page=veterinarian");
    } elseif ($action == 'delete') {
        $id = (int)($_POST['vetID'] ?? 0);
        
        try {
            // Check for associated pets
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pet WHERE vetID = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['message'] = "Cannot delete veterinarian with associated pets.";
                $_SESSION['messageType'] = 'danger';
                redirect("?page=veterinarian");
                return;
            }
            
            $stmt = $pdo->prepare("DELETE FROM veterinarian WHERE vetID = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "Veterinarian deleted successfully!";
            $_SESSION['messageType'] = 'success';
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['messageType'] = 'danger';
        }
        redirect("?page=veterinarian");
    } elseif ($action == 'deactivate') {
        $id = (int)($_POST['vetID'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE veterinarian SET vetStat = 'inactive' WHERE vetID = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "Veterinarian deactivated successfully!";
            $_SESSION['messageType'] = 'success';
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['messageType'] = 'danger';
        }
        redirect("?page=veterinarian");
    } elseif ($action == 'activate') {
        $id = (int)($_POST['vetID'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE veterinarian SET vetStat = 'active' WHERE vetID = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "Veterinarian activated successfully!";
            $_SESSION['messageType'] = 'success';
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['messageType'] = 'danger';
        }
        redirect("?page=veterinarian");
    }
}

function handlePetOwnerAction($pdo, $action) {
    if ($action == 'add') {
        $fname = trim($_POST['ownerFName'] ?? '');
        $mname = trim($_POST['ownerMName'] ?? '');
        $lname = trim($_POST['ownerLName'] ?? '');
        $phone = trim($_POST['ownerPhone'] ?? '');
        $email = trim($_POST['ownerEmail'] ?? '');
        $address = trim($_POST['ownerAddress'] ?? '');
        
        if (empty($fname) || empty($lname)) {
            $_SESSION['message'] = "First name and last name are required.";
            $_SESSION['messageType'] = 'danger';
            redirect("?page=pet_owner");
            return;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO pet_owner (ownerFName, ownerMName, ownerLName, ownerPhone, ownerEmail, ownerAddress, ownerStat) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$fname, $mname, $lname, $phone, $email, $address]);
            $_SESSION['message'] = "Pet owner added successfully!";
            $_SESSION['messageType'] = 'success';
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['messageType'] = 'danger';
        }
        redirect("?page=pet_owner");
    } elseif ($action == 'edit') {
        $id = (int)($_POST['ownerID'] ?? 0);
        $fname = trim($_POST['ownerFName'] ?? '');
        $mname = trim($_POST['ownerMName'] ?? '');
        $lname = trim($_POST['ownerLName'] ?? '');
        $phone = trim($_POST['ownerPhone'] ?? '');
        $email = trim($_POST['ownerEmail'] ?? '');
        $address = trim($_POST['ownerAddress'] ?? '');
        
        try {
            $stmt = $pdo->prepare("UPDATE pet_owner SET ownerFName = ?, ownerMName = ?, ownerLName = ?, ownerPhone = ?, ownerEmail = ?, ownerAddress = ? WHERE ownerID = ?");
            $stmt->execute([$fname, $mname, $lname, $phone, $email, $address, $id]);
            $_SESSION['message'] = "Pet owner updated successfully!";
            $_SESSION['messageType'] = 'success';
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['messageType'] = 'danger';
        }
        redirect("?page=pet_owner");
    } elseif ($action == 'delete') {
        $id = (int)($_POST['ownerID'] ?? 0);
        
        try {
            // Check for associated pets
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pet WHERE ownerID = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['message'] = "Cannot delete pet owner with associated pets.";
                $_SESSION['messageType'] = 'danger';
                redirect("?page=pet_owner");
                return;
            }
            
            $stmt = $pdo->prepare("DELETE FROM pet_owner WHERE ownerID = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "Pet owner deleted successfully!";
            $_SESSION['messageType'] = 'success';
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['messageType'] = 'danger';
        }
        redirect("?page=pet_owner");
    } elseif ($action == 'deactivate') {
        $id = (int)($_POST['ownerID'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE pet_owner SET ownerStat = 'inactive' WHERE ownerID = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "Pet owner deactivated successfully!";
            $_SESSION['messageType'] = 'success';
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['messageType'] = 'danger';
        }
        redirect("?page=pet_owner");
    } elseif ($action == 'activate') {
        $id = (int)($_POST['ownerID'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE pet_owner SET ownerStat = 'active' WHERE ownerID = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "Pet owner activated successfully!";
            $_SESSION['messageType'] = 'success';
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['messageType'] = 'danger';
        }
        redirect("?page=pet_owner");
    }
}

function handlePetAction($pdo, $action) {
    if ($action == 'add') {
        $petName = trim($_POST['petName'] ?? '');
        $petType = trim($_POST['petType'] ?? '');
        $petBreed = trim($_POST['petBreed'] ?? '');
        $petAge = !empty($_POST['petAge']) ? (int)$_POST['petAge'] : NULL;
        $petGender = $_POST['petGender'] ?? 'Unknown';
        $ownerID = (int)($_POST['ownerID'] ?? 0);
        
        if (empty($petName) || $ownerID == 0) {
            $_SESSION['message'] = "Pet name and owner are required.";
            $_SESSION['messageType'] = 'danger';
            redirect("?page=pet");
            return;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO pet (petName, petType, petBreed, petAge, petGender, ownerID, petStat) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$petName, $petType, $petBreed, $petAge, $petGender, $ownerID]);
            $_SESSION['message'] = "Pet added successfully!";
            $_SESSION['messageType'] = 'success';
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['messageType'] = 'danger';
        }
        redirect("?page=pet");
    } elseif ($action == 'edit') {
        $id = (int)($_POST['petID'] ?? 0);
        $petName = trim($_POST['petName'] ?? '');
        $petType = trim($_POST['petType'] ?? '');
        $petBreed = trim($_POST['petBreed'] ?? '');
        $petAge = !empty($_POST['petAge']) ? (int)$_POST['petAge'] : NULL;
        $petGender = $_POST['petGender'] ?? 'Unknown';
        $ownerID = (int)($_POST['ownerID'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE pet SET petName = ?, petType = ?, petBreed = ?, petAge = ?, petGender = ?, ownerID = ? WHERE petID = ?");
            $stmt->execute([$petName, $petType, $petBreed, $petAge, $petGender, $ownerID, $id]);
            $_SESSION['message'] = "Pet updated successfully!";
            $_SESSION['messageType'] = 'success';
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['messageType'] = 'danger';
        }
        redirect("?page=pet");
    } elseif ($action == 'delete') {
        $id = (int)($_POST['petID'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("DELETE FROM pet WHERE petID = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "Pet deleted successfully!";
            $_SESSION['messageType'] = 'success';
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['messageType'] = 'danger';
        }
        redirect("?page=pet");
    } elseif ($action == 'deactivate') {
        $id = (int)($_POST['petID'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE pet SET petStat = 'inactive' WHERE petID = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "Pet deactivated successfully!";
            $_SESSION['messageType'] = 'success';
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['messageType'] = 'danger';
        }
        redirect("?page=pet");
    } elseif ($action == 'activate') {
        $id = (int)($_POST['petID'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE pet SET petStat = 'active' WHERE petID = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "Pet activated successfully!";
            $_SESSION['messageType'] = 'success';
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['messageType'] = 'danger';
        }
        redirect("?page=pet");
    }
}

function handleAppointmentAction($pdo, $action) {
    if ($action == 'add') {
        $petID = (int)($_POST['petID'] ?? 0);
        $vetID = (int)($_POST['vetID'] ?? 0);
        $ownerID = (int)($_POST['ownerID'] ?? 0);
        $appointmentDate = $_POST['appointmentDate'] ?? '';
        $appointmentTime = $_POST['appointmentTime'] ?? '';
        $appointmentReason = trim($_POST['appointmentReason'] ?? '');
        $appointmentStatus = $_POST['appointmentStatus'] ?? 'scheduled';
        $appointmentNotes = trim($_POST['appointmentNotes'] ?? '');
        
        if (empty($petID) || empty($vetID) || empty($ownerID) || empty($appointmentDate) || empty($appointmentTime)) {
            $_SESSION['message'] = "Pet, Veterinarian, Owner, Date, and Time are required.";
            $_SESSION['messageType'] = 'danger';
            redirect("?page=appointments");
            return;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO appointments (petID, vetID, ownerID, appointmentDate, appointmentTime, appointmentReason, appointmentStatus, appointmentNotes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$petID, $vetID, $ownerID, $appointmentDate, $appointmentTime, $appointmentReason, $appointmentStatus, $appointmentNotes]);
            $_SESSION['message'] = "Appointment added successfully!";
            $_SESSION['messageType'] = 'success';
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['messageType'] = 'danger';
        }
        redirect("?page=appointments");
    } elseif ($action == 'edit') {
        $id = (int)($_POST['appointmentID'] ?? 0);
        $petID = (int)($_POST['petID'] ?? 0);
        $vetID = (int)($_POST['vetID'] ?? 0);
        $ownerID = (int)($_POST['ownerID'] ?? 0);
        $appointmentDate = $_POST['appointmentDate'] ?? '';
        $appointmentTime = $_POST['appointmentTime'] ?? '';
        $appointmentReason = trim($_POST['appointmentReason'] ?? '');
        $appointmentStatus = $_POST['appointmentStatus'] ?? 'scheduled';
        $appointmentNotes = trim($_POST['appointmentNotes'] ?? '');
        
        try {
            $stmt = $pdo->prepare("UPDATE appointments SET petID = ?, vetID = ?, ownerID = ?, appointmentDate = ?, appointmentTime = ?, appointmentReason = ?, appointmentStatus = ?, appointmentNotes = ? WHERE appointmentID = ?");
            $stmt->execute([$petID, $vetID, $ownerID, $appointmentDate, $appointmentTime, $appointmentReason, $appointmentStatus, $appointmentNotes, $id]);
            $_SESSION['message'] = "Appointment updated successfully!";
            $_SESSION['messageType'] = 'success';
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['messageType'] = 'danger';
        }
        redirect("?page=appointments");
    } elseif ($action == 'delete') {
        $id = (int)($_POST['appointmentID'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("DELETE FROM appointments WHERE appointmentID = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "Appointment deleted successfully!";
            $_SESSION['messageType'] = 'success';
        } catch(PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['messageType'] = 'danger';
        }
        redirect("?page=appointments");
    }
}

// ============================================================================
// HTML OUTPUT
// ============================================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veterinarian System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f4f4f4; }
        nav { background: #0a2a43; color: #fff; padding: 12px; }
        nav a { color: #fff; margin-right: 12px; text-decoration: none; font-weight: 600; }
        nav a:hover { text-decoration: underline; }
        hr { border: none; border-top: 1px solid #ccc; margin: 0; }
        .container, .content-wrapper { padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; background: #fff; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #e5f1fb; }
        button { padding: 6px 12px; margin: 2px; cursor: pointer; }
        form.inline, form[style*="display:inline"] { display: inline; }
        .card-grid { display: flex; gap: 12px; flex-wrap: wrap; }
        .card { background: #fff; padding: 15px; flex: 1 1 200px; border: 1px solid #ccc; border-radius: 4px; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .message.success { background: #d1e7dd; color: #0f5132; }
        .message.danger { background: #f8d7da; color: #842029; }
        form input[type="text"], form input[type="email"], form input[type="number"], form input[type="date"], form input[type="time"], form select, form textarea {
            width: 100%; padding: 6px; margin: 4px 0 10px 0; box-sizing: border-box;
        }
        form textarea { resize: vertical; }
        .section-box { background:#fff; padding:15px; border:1px solid #ccc; border-radius:4px; margin-bottom:20px; }
        .section-box h5 { margin-top:0; }
    </style>
</head>
<body>
    <div>
        <!-- Navigation -->
        <nav>
            <a href="?page=admin">Veterinarian System</a> |
            <a href="?page=admin">Dashboard</a> |
            <a href="?page=veterinarian">Veterinarians</a> |
            <a href="?page=pet_owner">Pet Owners</a> |
            <a href="?page=pet">Pets</a> |
            <a href="?page=appointments">Appointments</a>
        </nav>
        <hr>
        
        <!-- Search Bar -->
        <form method="get" style="margin:10px 0;">
            <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">
            <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" style="padding:5px; width:300px;">
            <button type="submit">Search</button>
            <?php if ($search): ?>
                <a href="?page=<?= htmlspecialchars($page) ?>">Clear</a>
            <?php endif; ?>
        </form>

        <!-- Messages -->
        <?php if ($message): ?>
            <div>
                <strong><?= $messageType == 'success' ? 'Success' : 'Error' ?>:</strong> <?= htmlspecialchars($message) ?>
            </div>
            <br>
        <?php endif; ?>

        <!-- Page Content -->
        <?php
        // Admin Dashboard
        if ($page == 'admin' || $page == ''): 
            $stats = [
                'veterinarians' => $pdo->query("SELECT COUNT(*) FROM veterinarian")->fetchColumn(),
                'pet_owners' => $pdo->query("SELECT COUNT(*) FROM pet_owner")->fetchColumn(),
                'pets' => $pdo->query("SELECT COUNT(*) FROM pet")->fetchColumn(),
                'appointments' => $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn()
            ];
        ?>
            <div>
                <h3>Dashboard</h3>
                <div>
                    <div>
                        <h5>Veterinarians</h5>
                        <h2><?= $stats['veterinarians'] ?></h2>
                    </div>
                    <div>
                        <h5>Pet Owners</h5>
                        <h2><?= $stats['pet_owners'] ?></h2>
                    </div>
                    <div>
                        <h5>Pets</h5>
                        <h2><?= $stats['pets'] ?></h2>
                    </div>
                    <div>
                        <h5>Appointments</h5>
                        <h2><?= $stats['appointments'] ?></h2>
                    </div>
                </div>
            </div>
        <?php
        // Veterinarians Page
        elseif ($page == 'veterinarian'):
            if ($search) {
                $stmt = $pdo->prepare("SELECT * FROM veterinarian WHERE CONCAT(vetFName, ' ', COALESCE(vetMName, ''), ' ', vetLName) LIKE ? OR vetSpecialty LIKE ? ORDER BY vetLName, vetFName");
                $stmt->execute(["%$search%", "%$search%"]);
                $veterinarians = $stmt->fetchAll();
            } else {
                $veterinarians = $pdo->query("SELECT * FROM veterinarian ORDER BY vetLName, vetFName")->fetchAll();
            }
        ?>
            <div>
                <h5>Manage Veterinarians <button onclick="showVeterinarianForm('add')">Add Veterinarian</button></h5>
                <table border="1">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Specialty</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($veterinarians as $vet): ?>
                            <tr>
                                <td><?= $vet['vetID'] ?></td>
                                <td><?= htmlspecialchars(getFullName($vet['vetFName'], $vet['vetMName'], $vet['vetLName'])) ?></td>
                                <td><?= htmlspecialchars($vet['vetSpecialty'] ?? '') ?></td>
                                <td><?= htmlspecialchars($vet['vetPhone'] ?? '') ?></td>
                                <td><?= htmlspecialchars($vet['vetEmail'] ?? '') ?></td>
                                <td><?= $vet['vetStat'] ?></td>
                                <td>
                                    <button onclick="editVeterinarian(<?= $vet['vetID'] ?>, '<?= htmlspecialchars($vet['vetFName']) ?>', '<?= htmlspecialchars($vet['vetMName'] ?? '') ?>', '<?= htmlspecialchars($vet['vetLName']) ?>', '<?= htmlspecialchars($vet['vetSpecialty'] ?? '') ?>', '<?= htmlspecialchars($vet['vetPhone'] ?? '') ?>', '<?= htmlspecialchars($vet['vetEmail'] ?? '') ?>')">Edit</button>
                                    <?php if ($vet['vetStat'] == 'active'): ?>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Deactivate this veterinarian?')">
                                            <input type="hidden" name="action" value="deactivate">
                                            <input type="hidden" name="vetID" value="<?= $vet['vetID'] ?>">
                                            <button type="submit">Deactivate</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="activate">
                                            <input type="hidden" name="vetID" value="<?= $vet['vetID'] ?>">
                                            <button type="submit">Activate</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Permanently delete this veterinarian? This cannot be undone!')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="vetID" value="<?= $vet['vetID'] ?>">
                                        <button type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Veterinarian Form -->
            <div id="veterinarianForm" style="display:none; border:1px solid #000; padding:10px; margin:10px 0;">
                <h5 id="veterinarianFormTitle">Add Veterinarian</h5>
                <form method="post">
                    <input type="hidden" name="action" id="veterinarian_action" value="add">
                    <input type="hidden" name="vetID" id="vetID">
                    <div>
                        <label>First Name</label>
                        <input type="text" name="vetFName" id="vetFName" required>
                    </div>
                    <div>
                        <label>Middle Name</label>
                        <input type="text" name="vetMName" id="vetMName">
                    </div>
                    <div>
                        <label>Last Name</label>
                        <input type="text" name="vetLName" id="vetLName" required>
                    </div>
                    <div>
                        <label>Specialty</label>
                        <input type="text" name="vetSpecialty" id="vetSpecialty">
                    </div>
                    <div>
                        <label>Phone</label>
                        <input type="text" name="vetPhone" id="vetPhone">
                    </div>
                    <div>
                        <label>Email</label>
                        <input type="email" name="vetEmail" id="vetEmail">
                    </div>
                    <div>
                        <button type="button" onclick="document.getElementById('veterinarianForm').style.display='none'">Cancel</button>
                        <button type="submit">Save</button>
                    </div>
                </form>
            </div>

            <script>
            function showVeterinarianForm(mode, id, fname, mname, lname, specialty, phone, email) {
                document.getElementById('veterinarian_action').value = mode;
                document.getElementById('vetID').value = id || '';
                document.getElementById('vetFName').value = fname || '';
                document.getElementById('vetMName').value = mname || '';
                document.getElementById('vetLName').value = lname || '';
                document.getElementById('vetSpecialty').value = specialty || '';
                document.getElementById('vetPhone').value = phone || '';
                document.getElementById('vetEmail').value = email || '';
                document.getElementById('veterinarianFormTitle').textContent = mode == 'add' ? 'Add Veterinarian' : 'Edit Veterinarian';
                document.getElementById('veterinarianForm').style.display = 'block';
            }
            function editVeterinarian(id, fname, mname, lname, specialty, phone, email) {
                showVeterinarianForm('edit', id, fname, mname, lname, specialty, phone, email);
            }
            </script>
        <?php
        // Pet Owners Page
        elseif ($page == 'pet_owner'):
            $per_page = 10;
            $current_page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
            $offset = ($current_page - 1) * $per_page;
            
            if ($search) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM pet_owner WHERE CONCAT(ownerFName, ' ', COALESCE(ownerMName, ''), ' ', ownerLName) LIKE ? OR ownerPhone LIKE ? OR ownerEmail LIKE ?");
                $stmt->execute(["%$search%", "%$search%", "%$search%"]);
                $total = $stmt->fetchColumn();
                $total_pages = ceil($total / $per_page);
                
                $per_page = (int)$per_page;
                $offset = (int)$offset;
                $owners = $pdo->prepare("SELECT * FROM pet_owner WHERE CONCAT(ownerFName, ' ', COALESCE(ownerMName, ''), ' ', ownerLName) LIKE ? OR ownerPhone LIKE ? OR ownerEmail LIKE ? ORDER BY ownerLName, ownerFName LIMIT $per_page OFFSET $offset");
                $owners->bindValue(1, "%$search%", PDO::PARAM_STR);
                $owners->bindValue(2, "%$search%", PDO::PARAM_STR);
                $owners->bindValue(3, "%$search%", PDO::PARAM_STR);
                $owners->execute();
                $owners = $owners->fetchAll();
            } else {
                $total = $pdo->query("SELECT COUNT(*) FROM pet_owner")->fetchColumn();
                $total_pages = ceil($total / $per_page);
                
                $per_page = (int)$per_page;
                $offset = (int)$offset;
                $owners = $pdo->prepare("SELECT * FROM pet_owner ORDER BY ownerLName, ownerFName LIMIT $per_page OFFSET $offset");
                $owners->execute();
                $owners = $owners->fetchAll();
            }
        ?>
            <div>
                <h5>Manage Pet Owners <button onclick="showPetOwnerForm('add')">Add Pet Owner</button></h5>
                <table border="1">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($owners as $owner): ?>
                            <tr>
                                <td><?= $owner['ownerID'] ?></td>
                                <td><?= htmlspecialchars(getFullName($owner['ownerFName'], $owner['ownerMName'], $owner['ownerLName'])) ?></td>
                                <td><?= htmlspecialchars($owner['ownerPhone'] ?? '') ?></td>
                                <td><?= htmlspecialchars($owner['ownerEmail'] ?? '') ?></td>
                                <td><?= $owner['ownerStat'] ?></td>
                                <td>
                                    <button onclick="editPetOwner(<?= $owner['ownerID'] ?>, '<?= htmlspecialchars($owner['ownerFName']) ?>', '<?= htmlspecialchars($owner['ownerMName'] ?? '') ?>', '<?= htmlspecialchars($owner['ownerLName']) ?>', '<?= htmlspecialchars($owner['ownerPhone'] ?? '') ?>', '<?= htmlspecialchars($owner['ownerEmail'] ?? '') ?>', '<?= htmlspecialchars($owner['ownerAddress'] ?? '') ?>')">Edit</button>
                                    <?php if ($owner['ownerStat'] == 'active'): ?>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Deactivate this pet owner?')">
                                            <input type="hidden" name="action" value="deactivate">
                                            <input type="hidden" name="ownerID" value="<?= $owner['ownerID'] ?>">
                                            <button type="submit">Deactivate</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="activate">
                                            <input type="hidden" name="ownerID" value="<?= $owner['ownerID'] ?>">
                                            <button type="submit">Activate</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Permanently delete this pet owner? This cannot be undone!')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="ownerID" value="<?= $owner['ownerID'] ?>">
                                        <button type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div>
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $current_page): ?>
                                <strong><?= $i ?></strong>
                            <?php else: ?>
                                <a href="?page=pet_owner&p=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pet Owner Form -->
            <div id="petOwnerForm" style="display:none; border:1px solid #000; padding:10px; margin:10px 0;">
                <h5 id="petOwnerFormTitle">Add Pet Owner</h5>
                <form method="post">
                    <input type="hidden" name="action" id="pet_owner_action" value="add">
                    <input type="hidden" name="ownerID" id="ownerID">
                    <div>
                        <label>First Name</label>
                        <input type="text" name="ownerFName" id="ownerFName" required>
                    </div>
                    <div>
                        <label>Middle Name</label>
                        <input type="text" name="ownerMName" id="ownerMName">
                    </div>
                    <div>
                        <label>Last Name</label>
                        <input type="text" name="ownerLName" id="ownerLName" required>
                    </div>
                    <div>
                        <label>Phone</label>
                        <input type="text" name="ownerPhone" id="ownerPhone">
                    </div>
                    <div>
                        <label>Email</label>
                        <input type="email" name="ownerEmail" id="ownerEmail">
                    </div>
                    <div>
                        <label>Address</label>
                        <textarea name="ownerAddress" id="ownerAddress" rows="3"></textarea>
                    </div>
                    <div>
                        <button type="button" onclick="document.getElementById('petOwnerForm').style.display='none'">Cancel</button>
                        <button type="submit">Save</button>
                    </div>
                </form>
            </div>

            <script>
            function showPetOwnerForm(mode, id, fname, mname, lname, phone, email, address) {
                document.getElementById('pet_owner_action').value = mode;
                document.getElementById('ownerID').value = id || '';
                document.getElementById('ownerFName').value = fname || '';
                document.getElementById('ownerMName').value = mname || '';
                document.getElementById('ownerLName').value = lname || '';
                document.getElementById('ownerPhone').value = phone || '';
                document.getElementById('ownerEmail').value = email || '';
                document.getElementById('ownerAddress').value = address || '';
                document.getElementById('petOwnerFormTitle').textContent = mode == 'add' ? 'Add Pet Owner' : 'Edit Pet Owner';
                document.getElementById('petOwnerForm').style.display = 'block';
            }
            function editPetOwner(id, fname, mname, lname, phone, email, address) {
                showPetOwnerForm('edit', id, fname, mname, lname, phone, email, address);
            }
            </script>
        <?php
        // Pets Page
        elseif ($page == 'pet'):
            if ($search) {
                $stmt = $pdo->prepare("
                    SELECT p.*, o.ownerFName, o.ownerMName, o.ownerLName
                    FROM pet p
                    LEFT JOIN pet_owner o ON p.ownerID = o.ownerID
                    WHERE p.petName LIKE ? OR p.petType LIKE ? OR p.petBreed LIKE ? 
                    OR CONCAT(o.ownerFName, ' ', COALESCE(o.ownerMName, ''), ' ', o.ownerLName) LIKE ?
                    ORDER BY p.petName
                ");
                $stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%"]);
                $pets = $stmt->fetchAll();
            } else {
                $pets = $pdo->query("
                    SELECT p.*, o.ownerFName, o.ownerMName, o.ownerLName
                    FROM pet p
                    LEFT JOIN pet_owner o ON p.ownerID = o.ownerID
                    ORDER BY p.petName
                ")->fetchAll();
            }
            $owners = $pdo->query("SELECT * FROM pet_owner WHERE ownerStat = 'active' ORDER BY ownerLName, ownerFName")->fetchAll();
        ?>
            <div>
                <h5>Manage Pets <button onclick="showPetForm('add')">Add Pet</button></h5>
                <table border="1">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pet Name</th>
                            <th>Type</th>
                            <th>Breed</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pets as $pet): ?>
                            <tr>
                                <td><?= $pet['petID'] ?></td>
                                <td><?= htmlspecialchars($pet['petName']) ?></td>
                                <td><?= htmlspecialchars($pet['petType'] ?? '') ?></td>
                                <td><?= htmlspecialchars($pet['petBreed'] ?? '') ?></td>
                                <td><?= $pet['petAge'] ?? '' ?></td>
                                <td><?= $pet['petGender'] ?></td>
                                <td><?= htmlspecialchars(getFullName($pet['ownerFName'] ?? '', $pet['ownerMName'] ?? '', $pet['ownerLName'] ?? '')) ?></td>
                                <td><?= $pet['petStat'] ?></td>
                                <td>
                                    <button onclick="editPet(<?= $pet['petID'] ?>, '<?= htmlspecialchars($pet['petName']) ?>', '<?= htmlspecialchars($pet['petType'] ?? '') ?>', '<?= htmlspecialchars($pet['petBreed'] ?? '') ?>', <?= $pet['petAge'] ?? 'null' ?>, '<?= $pet['petGender'] ?>', <?= $pet['ownerID'] ?>)">Edit</button>
                                    <?php if ($pet['petStat'] == 'active'): ?>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Deactivate this pet?')">
                                            <input type="hidden" name="action" value="deactivate">
                                            <input type="hidden" name="petID" value="<?= $pet['petID'] ?>">
                                            <button type="submit">Deactivate</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="activate">
                                            <input type="hidden" name="petID" value="<?= $pet['petID'] ?>">
                                            <button type="submit">Activate</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Permanently delete this pet? This cannot be undone!')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="petID" value="<?= $pet['petID'] ?>">
                                        <button type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pet Form -->
            <div id="petForm" style="display:none; border:1px solid #000; padding:10px; margin:10px 0;">
                <h5 id="petFormTitle">Add Pet</h5>
                <form method="post">
                    <input type="hidden" name="action" id="pet_action" value="add">
                    <input type="hidden" name="petID" id="petID">
                    <div>
                        <label>Pet Name</label>
                        <input type="text" name="petName" id="petName" required>
                    </div>
                    <div>
                        <label>Type</label>
                        <input type="text" name="petType" id="petType" placeholder="e.g., Dog, Cat, Bird">
                    </div>
                    <div>
                        <label>Breed</label>
                        <input type="text" name="petBreed" id="petBreed">
                    </div>
                    <div>
                        <label>Age</label>
                        <input type="number" name="petAge" id="petAge" min="0">
                    </div>
                    <div>
                        <label>Gender</label>
                        <select name="petGender" id="petGender">
                            <option value="Unknown">Unknown</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label>Owner</label>
                        <select name="ownerID" id="pet_ownerID" required>
                            <option value="">Select Owner</option>
                            <?php foreach($owners as $owner): ?>
                                <option value="<?= $owner['ownerID'] ?>"><?= htmlspecialchars(getFullName($owner['ownerFName'], $owner['ownerMName'], $owner['ownerLName'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="button" onclick="document.getElementById('petForm').style.display='none'">Cancel</button>
                        <button type="submit">Save</button>
                    </div>
                </form>
            </div>

            <script>
            function showPetForm(mode, id, name, type, breed, age, gender, ownerID) {
                document.getElementById('pet_action').value = mode;
                document.getElementById('petID').value = id || '';
                document.getElementById('petName').value = name || '';
                document.getElementById('petType').value = type || '';
                document.getElementById('petBreed').value = breed || '';
                document.getElementById('petAge').value = age || '';
                document.getElementById('petGender').value = gender || 'Unknown';
                document.getElementById('pet_ownerID').value = ownerID || '';
                document.getElementById('petFormTitle').textContent = mode == 'add' ? 'Add Pet' : 'Edit Pet';
                document.getElementById('petForm').style.display = 'block';
            }
            function editPet(id, name, type, breed, age, gender, ownerID) {
                showPetForm('edit', id, name, type, breed, age, gender, ownerID);
            }
            </script>
        <?php
        // Appointments Page
        elseif ($page == 'appointments'):
            if ($search) {
                $stmt = $pdo->prepare("
                    SELECT a.*, 
                           p.petName, 
                           v.vetFName, v.vetMName, v.vetLName,
                           o.ownerFName, o.ownerMName, o.ownerLName
                    FROM appointments a
                    LEFT JOIN pet p ON a.petID = p.petID
                    LEFT JOIN veterinarian v ON a.vetID = v.vetID
                    LEFT JOIN pet_owner o ON a.ownerID = o.ownerID
                    WHERE p.petName LIKE ? 
                    OR CONCAT(v.vetFName, ' ', COALESCE(v.vetMName, ''), ' ', v.vetLName) LIKE ?
                    OR CONCAT(o.ownerFName, ' ', COALESCE(o.ownerMName, ''), ' ', o.ownerLName) LIKE ?
                    OR a.appointmentReason LIKE ?
                    ORDER BY a.appointmentDate DESC, a.appointmentTime DESC
                ");
                $stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%"]);
                $appointments = $stmt->fetchAll();
            } else {
                $appointments = $pdo->query("
                    SELECT a.*, 
                           p.petName, 
                           v.vetFName, v.vetMName, v.vetLName,
                           o.ownerFName, o.ownerMName, o.ownerLName
                    FROM appointments a
                    LEFT JOIN pet p ON a.petID = p.petID
                    LEFT JOIN veterinarian v ON a.vetID = v.vetID
                    LEFT JOIN pet_owner o ON a.ownerID = o.ownerID
                    ORDER BY a.appointmentDate DESC, a.appointmentTime DESC
                ")->fetchAll();
            }
            $pets = $pdo->query("SELECT * FROM pet WHERE petStat = 'active' ORDER BY petName")->fetchAll();
            $veterinarians = $pdo->query("SELECT * FROM veterinarian WHERE vetStat = 'active' ORDER BY vetLName, vetFName")->fetchAll();
            $owners = $pdo->query("SELECT * FROM pet_owner WHERE ownerStat = 'active' ORDER BY ownerLName, ownerFName")->fetchAll();
        ?>
            <div>
                <h5>Manage Appointments <button onclick="showAppointmentForm('add')">Add Appointment</button></h5>
                <table border="1">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Pet</th>
                            <th>Veterinarian</th>
                            <th>Owner</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($appointments as $apt): ?>
                            <tr>
                                <td><?= $apt['appointmentID'] ?></td>
                                <td><?= date('M d, Y', strtotime($apt['appointmentDate'])) ?></td>
                                <td><?= date('h:i A', strtotime($apt['appointmentTime'])) ?></td>
                                <td><?= htmlspecialchars($apt['petName'] ?? '') ?></td>
                                <td><?= htmlspecialchars(getFullName($apt['vetFName'] ?? '', $apt['vetMName'] ?? '', $apt['vetLName'] ?? '')) ?></td>
                                <td><?= htmlspecialchars(getFullName($apt['ownerFName'] ?? '', $apt['ownerMName'] ?? '', $apt['ownerLName'] ?? '')) ?></td>
                                <td><?= htmlspecialchars(substr($apt['appointmentReason'] ?? '', 0, 50)) ?><?= strlen($apt['appointmentReason'] ?? '') > 50 ? '...' : '' ?></td>
                                <td><?= ucfirst(str_replace('_', ' ', $apt['appointmentStatus'])) ?></td>
                                <td>
                                    <button onclick="editAppointment(<?= $apt['appointmentID'] ?>, <?= $apt['petID'] ?>, <?= $apt['vetID'] ?>, <?= $apt['ownerID'] ?>, '<?= $apt['appointmentDate'] ?>', '<?= $apt['appointmentTime'] ?>', '<?= htmlspecialchars($apt['appointmentReason'] ?? '', ENT_QUOTES) ?>', '<?= $apt['appointmentStatus'] ?>', '<?= htmlspecialchars($apt['appointmentNotes'] ?? '', ENT_QUOTES) ?>')">Edit</button>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Permanently delete this appointment? This cannot be undone!')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="appointmentID" value="<?= $apt['appointmentID'] ?>">
                                        <button type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Appointment Form -->
            <div id="appointmentForm" style="display:none; border:1px solid #000; padding:10px; margin:10px 0;">
                <h5 id="appointmentFormTitle">Add Appointment</h5>
                <form method="post">
                    <input type="hidden" name="action" id="appointment_action" value="add">
                    <input type="hidden" name="appointmentID" id="appointmentID">
                    <div>
                        <label>Pet</label>
                        <select name="petID" id="appointment_petID" required>
                            <option value="">Select Pet</option>
                            <?php foreach($pets as $pet): ?>
                                <option value="<?= $pet['petID'] ?>"><?= htmlspecialchars($pet['petName']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Veterinarian</label>
                        <select name="vetID" id="appointment_vetID" required>
                            <option value="">Select Veterinarian</option>
                            <?php foreach($veterinarians as $vet): ?>
                                <option value="<?= $vet['vetID'] ?>"><?= htmlspecialchars(getFullName($vet['vetFName'], $vet['vetMName'], $vet['vetLName'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Owner</label>
                        <select name="ownerID" id="appointment_ownerID" required>
                            <option value="">Select Owner</option>
                            <?php foreach($owners as $owner): ?>
                                <option value="<?= $owner['ownerID'] ?>"><?= htmlspecialchars(getFullName($owner['ownerFName'], $owner['ownerMName'], $owner['ownerLName'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Date</label>
                        <input type="date" name="appointmentDate" id="appointmentDate" required>
                    </div>
                    <div>
                        <label>Time</label>
                        <input type="time" name="appointmentTime" id="appointmentTime" required>
                    </div>
                    <div>
                        <label>Reason</label>
                        <textarea name="appointmentReason" id="appointmentReason" rows="3"></textarea>
                    </div>
                    <div>
                        <label>Status</label>
                        <select name="appointmentStatus" id="appointmentStatus">
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="no_show">No Show</option>
                        </select>
                    </div>
                    <div>
                        <label>Notes</label>
                        <textarea name="appointmentNotes" id="appointmentNotes" rows="3"></textarea>
                    </div>
                    <div>
                        <button type="button" onclick="document.getElementById('appointmentForm').style.display='none'">Cancel</button>
                        <button type="submit">Save</button>
                    </div>
                </form>
            </div>

            <script>
            function showAppointmentForm(mode, id, petID, vetID, ownerID, date, time, reason, status, notes) {
                document.getElementById('appointment_action').value = mode;
                document.getElementById('appointmentID').value = id || '';
                document.getElementById('appointment_petID').value = petID || '';
                document.getElementById('appointment_vetID').value = vetID || '';
                document.getElementById('appointment_ownerID').value = ownerID || '';
                document.getElementById('appointmentDate').value = date || '';
                document.getElementById('appointmentTime').value = time || '';
                document.getElementById('appointmentReason').value = reason || '';
                document.getElementById('appointmentStatus').value = status || 'scheduled';
                document.getElementById('appointmentNotes').value = notes || '';
                document.getElementById('appointmentFormTitle').textContent = mode == 'add' ? 'Add Appointment' : 'Edit Appointment';
                document.getElementById('appointmentForm').style.display = 'block';
            }
            function editAppointment(id, petID, vetID, ownerID, date, time, reason, status, notes) {
                showAppointmentForm('edit', id, petID, vetID, ownerID, date, time, reason, status, notes);
            }
            </script>
        <?php endif; ?>
    </div>

</body>
</html>

