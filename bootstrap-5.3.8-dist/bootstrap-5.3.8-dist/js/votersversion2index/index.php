<?php
/**
 * Single-File Voting System - Skills Test Version
 * Auto-creates database and tables on first access
 * Matches Skills Test requirements with login functionality
 * 
 * ============================================================================
 * MIGRATION GUIDE - How to adapt this for other modules (Pet Owner, etc.)
 * ============================================================================
 * 
 * To migrate to a different module (e.g., Pet Owner System):
 * 
 * 1. DATABASE CONFIGURATION (Line 139-142):
 *    - Change DB_NAME to your new database name
 *    - Keep DB_HOST, DB_USER, DB_PASS as needed
 * 
 * 2. TABLE STRUCTURE (Line 182-260):
 *    - Rename function: createElectionTables() -> createPetOwnerTables()
 *    - Change table names:
 *      * positions -> pet_categories (or categories)
 *      * candidates -> pets (or items)
 *      * voters -> owners (or users - keep if same)
 *      * votes -> adoptions (or transactions)
 *    - Change column names to match your domain:
 *      * posName -> category_name
 *      * candID -> pet_id
 *      * candFName/candMName/candLName -> pet_name (or keep separate)
 *      * numOfPositions -> max_adoptions
 *      * voted -> has_adopted
 * 
 * 3. FUNCTION NAMES (Throughout file):
 *    - handlePositionAction() -> handleCategoryAction()
 *    - handleCandidateAction() -> handlePetAction()
 *    - handleVoterAction() -> handleOwnerAction()
 *    - handleVoteSubmission() -> handleAdoptionSubmission()
 *    - calculateWinners() -> calculateFeaturedPets()
 * 
 * 4. ROUTING (Line 350-380):
 *    - Change page names:
 *      * 'positions' -> 'categories'
 *      * 'candidates' -> 'pets'
 *      * 'voters' -> 'owners'
 *      * 'vote' -> 'adopt'
 * 
 * 5. HTML LABELS & TEXT (Line 700+):
 *    - Change all display text:
 *      * "Manage Positions" -> "Manage Categories"
 *      * "Add Candidate" -> "Add Pet"
 *      * "Cast Your Vote" -> "Adopt a Pet"
 *      * "Voting Results" -> "Adoption Records"
 * 
 * 6. FORM FIELDS (Throughout HTML):
 *    - Update input names and labels to match your domain
 *    - Change validation logic if needed
 * 
 * 7. BUSINESS LOGIC:
 *    - Modify the voting logic to match your domain logic
 *    - Update calculations (winners -> featured items)
 * 
 * ============================================================================
 * QUICK EXAMPLES - Common Module Adaptations
 * ============================================================================
 * 
 * EXAMPLE 1: Pet Owner System
 * - positions -> pet_categories (Dogs, Cats, Birds)
 * - candidates -> pets (individual pets)
 * - voters -> owners (pet owners)
 * - votes -> adoptions (adoption records)
 * 
 * EXAMPLE 2: Product Inventory System
 * - positions -> categories (Electronics, Clothing, Food)
 * - candidates -> products (individual products)
 * - voters -> suppliers (or keep as users)
 * - votes -> orders (or purchases)
 * 
 * EXAMPLE 3: Event Management System
 * - positions -> event_types (Concert, Conference, Workshop)
 * - candidates -> events (individual events)
 * - voters -> attendees (or participants)
 * - votes -> registrations (or bookings)
 * 
 * ============================================================================
 * HOW TO ADD NEW TABLES/COLUMNS
 * ============================================================================
 * 
 * To add new tables:
 * 1. Add CREATE TABLE query in createElectionTables() function (Line 182)
 * 2. Add CRUD handler function (similar to handlePositionAction)
 * 3. Add routing case in switch statement (Line 350)
 * 4. Add HTML page section (similar to Positions page)
 * 5. Add navigation link in nav section (Line 700)
 * 
 * To add new columns to existing tables:
 * 1. Add column in CREATE TABLE query (Line 182-260)
 * 2. Update INSERT/UPDATE queries in handler functions
 * 3. Update HTML forms to include new fields
 * 4. Update display tables to show new columns
 * 
 * The structure stays the same, just rename everything and add what you need!
 */

session_start();
date_default_timezone_set('Asia/Manila');

// ============================================================================
// DATABASE CONFIGURATION & AUTO-CREATION
// ============================================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'election');
define('DB_USER', 'root');
define('DB_PASS', '');

// Database connection with auto-creation
try {
    // Try to connect to existing database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Create tables if they don't exist
    createElectionTables($pdo);
    
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
            createElectionTables($pdo);
        } catch(PDOException $createDbException) {
            die("Database Creation failed: " . $createDbException->getMessage());
        }
    } else {
        die("Database Connection failed: " . $e->getMessage());
    }
}

// Function to create all election tables - Skills Test Schema
function createElectionTables($pdo) {
    $tables = [
        // Positions Table - Skills Test Schema
        "CREATE TABLE IF NOT EXISTS positions (
            posID INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            posName VARCHAR(100) NOT NULL UNIQUE,
            numOfPositions INT(11) DEFAULT 1,
            posStat ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        // Candidates Table - Skills Test Schema
        "CREATE TABLE IF NOT EXISTS candidates (
            candID INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            candFName VARCHAR(50) NOT NULL,
            candMName VARCHAR(50) DEFAULT NULL,
            candLName VARCHAR(50) NOT NULL,
            posID INT(11) NOT NULL,
            candStat ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (posID) REFERENCES positions(posID) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        // Voters Table - Skills Test Schema
        "CREATE TABLE IF NOT EXISTS voters (
            voterID INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            voterPass VARCHAR(255) NOT NULL,
            voterFName VARCHAR(50) NOT NULL,
            voterMName VARCHAR(50) DEFAULT NULL,
            voterLName VARCHAR(50) NOT NULL,
            voterStat ENUM('active', 'inactive') DEFAULT 'active',
            voted TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        // Votes Table - Skills Test Schema (simplified, no vote_id)
        "CREATE TABLE IF NOT EXISTS votes (
            posID INT(11) NOT NULL,
            voterID INT(11) NOT NULL,
            candid INT(11) NOT NULL,
            voted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (posID, voterID, candid),
            FOREIGN KEY (posID) REFERENCES positions(posID) ON DELETE CASCADE,
            FOREIGN KEY (voterID) REFERENCES voters(voterID) ON DELETE CASCADE,
            FOREIGN KEY (candid) REFERENCES candidates(candID) ON DELETE CASCADE
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
    header("Location: $url");
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

function requireLogin() {
    if (!isset($_SESSION['voterID'])) {
        redirect('?page=login');
    }
}

function requireAdmin() {
    // For Skills Test, we'll allow admin access without login
    // You can add admin login later if needed
    return true;
}

// ============================================================================
// ROUTING SYSTEM
// ============================================================================

$page = isset($_GET['page']) ? $_GET['page'] : 'login';
$action = isset($_POST['action']) ? $_POST['action'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$message = '';
$messageType = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    switch($page) {
        case 'login':
            handleLogin($pdo);
            break;
        case 'positions':
            handlePositionAction($pdo, $action);
            break;
        case 'candidates':
            handleCandidateAction($pdo, $action);
            break;
        case 'voters':
            handleVoterAction($pdo, $action);
            break;
        case 'vote':
            handleVoteSubmission($pdo);
            break;
        case 'calculate_winners':
            calculateWinners($pdo);
            break;
        case 'reset_voting':
            resetVotingSession($pdo);
            break;
    }
}

// ============================================================================
// LOGIN HANDLER
// ============================================================================

function handleLogin($pdo) {
    global $message, $messageType;
    
    $voterID = trim($_POST['voterID'] ?? '');
    $voterPass = $_POST['voterPass'] ?? '';
    
    if (empty($voterID) || empty($voterPass)) {
        $message = "Voter ID and Password are required.";
        $messageType = 'danger';
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM voters WHERE voterID = ?");
        $stmt->execute([$voterID]);
        $voter = $stmt->fetch();
        
        if ($voter && password_verify($voterPass, $voter['voterPass'])) {
            // Check if voter is active
            if ($voter['voterStat'] != 'active') {
                $message = "Your account is inactive. Please contact administrator.";
                $messageType = 'danger';
                return;
            }
            
            // Set session
            $_SESSION['voterID'] = $voter['voterID'];
            $_SESSION['voterName'] = getFullName($voter['voterFName'], $voter['voterMName'], $voter['voterLName']);
            $_SESSION['voted'] = $voter['voted'];
            
            redirect('?page=vote');
        } else {
            $message = "Invalid Voter ID or Password.";
            $messageType = 'danger';
        }
    } catch(PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// ============================================================================
// HANDLERS FOR CRUD OPERATIONS
// ============================================================================

function handlePositionAction($pdo, $action) {
    global $message, $messageType;
    
    if ($action == 'add') {
        $name = trim($_POST['posName'] ?? '');
        $numOfPositions = (int)($_POST['numOfPositions'] ?? 1);
        
        if (empty($name)) {
            $message = "Position name is required.";
            $messageType = 'danger';
            return;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO positions (posName, numOfPositions, posStat) VALUES (?, ?, 'active')");
            $stmt->execute([$name, $numOfPositions]);
            $message = "Position added successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action == 'edit') {
        $id = (int)($_POST['posID'] ?? 0);
        $name = trim($_POST['posName'] ?? '');
        $numOfPositions = (int)($_POST['numOfPositions'] ?? 1);
        
        try {
            $stmt = $pdo->prepare("UPDATE positions SET posName = ?, numOfPositions = ? WHERE posID = ?");
            $stmt->execute([$name, $numOfPositions, $id]);
            $message = "Position updated successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action == 'deactivate') {
        $id = (int)($_POST['posID'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE positions SET posStat = 'inactive' WHERE posID = ?");
            $stmt->execute([$id]);
            $message = "Position deactivated successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action == 'activate') {
        $id = (int)($_POST['posID'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE positions SET posStat = 'active' WHERE posID = ?");
            $stmt->execute([$id]);
            $message = "Position activated successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

function handleCandidateAction($pdo, $action) {
    global $message, $messageType;
    
    if ($action == 'add') {
        $fname = trim($_POST['candFName'] ?? '');
        $mname = trim($_POST['candMName'] ?? '');
        $lname = trim($_POST['candLName'] ?? '');
        $posID = (int)($_POST['posID'] ?? 0);
        
        if (empty($fname) || empty($lname) || $posID == 0) {
            $message = "First name, last name, and position are required.";
            $messageType = 'danger';
            return;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO candidates (candFName, candMName, candLName, posID, candStat) VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([$fname, $mname, $lname, $posID]);
            $message = "Candidate added successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action == 'edit') {
        $id = (int)($_POST['candID'] ?? 0);
        $fname = trim($_POST['candFName'] ?? '');
        $mname = trim($_POST['candMName'] ?? '');
        $lname = trim($_POST['candLName'] ?? '');
        $posID = (int)($_POST['posID'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE candidates SET candFName = ?, candMName = ?, candLName = ?, posID = ? WHERE candID = ?");
            $stmt->execute([$fname, $mname, $lname, $posID, $id]);
            $message = "Candidate updated successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action == 'deactivate') {
        $id = (int)($_POST['candID'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE candidates SET candStat = 'inactive' WHERE candID = ?");
            $stmt->execute([$id]);
            $message = "Candidate deactivated successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action == 'activate') {
        $id = (int)($_POST['candID'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE candidates SET candStat = 'active' WHERE candID = ?");
            $stmt->execute([$id]);
            $message = "Candidate activated successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

function handleVoterAction($pdo, $action) {
    global $message, $messageType;
    
    if ($action == 'add') {
        $fname = trim($_POST['voterFName'] ?? '');
        $mname = trim($_POST['voterMName'] ?? '');
        $lname = trim($_POST['voterLName'] ?? '');
        $voterPass = $_POST['voterPass'] ?? '';
        
        if (empty($fname) || empty($lname) || empty($voterPass)) {
            $message = "First name, last name, and password are required.";
            $messageType = 'danger';
            return;
        }
        
        try {
            $hashedPass = password_hash($voterPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO voters (voterFName, voterMName, voterLName, voterPass, voterStat, voted) VALUES (?, ?, ?, ?, 'active', 0)");
            $stmt->execute([$fname, $mname, $lname, $hashedPass]);
            $message = "Voter added successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action == 'edit') {
        $id = (int)($_POST['voterID'] ?? 0);
        $fname = trim($_POST['voterFName'] ?? '');
        $mname = trim($_POST['voterMName'] ?? '');
        $lname = trim($_POST['voterLName'] ?? '');
        $voterPass = $_POST['voterPass'] ?? '';
        
        try {
            if (!empty($voterPass)) {
                $hashedPass = password_hash($voterPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE voters SET voterFName = ?, voterMName = ?, voterLName = ?, voterPass = ? WHERE voterID = ?");
                $stmt->execute([$fname, $mname, $lname, $hashedPass, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE voters SET voterFName = ?, voterMName = ?, voterLName = ? WHERE voterID = ?");
                $stmt->execute([$fname, $mname, $lname, $id]);
            }
            $message = "Voter updated successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action == 'deactivate') {
        $id = (int)($_POST['voterID'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE voters SET voterStat = 'inactive' WHERE voterID = ?");
            $stmt->execute([$id]);
            $message = "Voter deactivated successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action == 'activate') {
        $id = (int)($_POST['voterID'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE voters SET voterStat = 'active' WHERE voterID = ?");
            $stmt->execute([$id]);
            $message = "Voter activated successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

function handleVoteSubmission($pdo) {
    global $message, $messageType;
    
    requireLogin();
    
    $voterID = $_SESSION['voterID'];
    
    // Check if voter already voted
    $stmt = $pdo->prepare("SELECT voted, voterStat FROM voters WHERE voterID = ?");
    $stmt->execute([$voterID]);
    $voter = $stmt->fetch();
    
    if (!$voter || $voter['voterStat'] != 'active') {
        $message = "Your account is inactive. Please contact administrator.";
        $messageType = 'danger';
        return;
    }
    
    if ($voter['voted']) {
        $message = "You have already voted.";
        $messageType = 'danger';
        return;
    }
    
    if (empty($_POST['votes'])) {
        $message = "Please select at least one candidate.";
        $messageType = 'danger';
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Process votes
        foreach ($_POST['votes'] as $posID => $selected) {
            $posID = (int)$posID;
            $candidate_ids = is_array($selected) ? array_map('intval', $selected) : [(int)$selected];
            
            // Get position info
            $stmt = $pdo->prepare("SELECT numOfPositions, posStat FROM positions WHERE posID = ?");
            $stmt->execute([$posID]);
            $position = $stmt->fetch();
            
            if (!$position || $position['posStat'] != 'active') {
                throw new Exception("Invalid position selected");
            }
            
            if (count($candidate_ids) > $position['numOfPositions']) {
                throw new Exception("You can only vote for up to " . $position['numOfPositions'] . " candidate(s) for this position.");
            }
            
            // Insert votes
            foreach ($candidate_ids as $candid) {
                // Check if candidate is active
                $stmt = $pdo->prepare("SELECT candStat FROM candidates WHERE candID = ? AND posID = ?");
                $stmt->execute([$candid, $posID]);
                $candidate = $stmt->fetch();
                
                if (!$candidate || $candidate['candStat'] != 'active') {
                    throw new Exception("Invalid candidate selected");
                }
                
                // Use INSERT IGNORE to handle duplicate votes
                $stmt = $pdo->prepare("INSERT IGNORE INTO votes (posID, voterID, candid) VALUES (?, ?, ?)");
                $stmt->execute([$posID, $voterID, $candid]);
            }
        }
        
        // Mark voter as voted
        $stmt = $pdo->prepare("UPDATE voters SET voted = 1 WHERE voterID = ?");
        $stmt->execute([$voterID]);
        $_SESSION['voted'] = 1;
        
        $pdo->commit();
        $message = "Vote submitted successfully!";
        $messageType = 'success';
    } catch(Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
        $messageType = 'danger';
    }
}

function calculateWinners($pdo) {
    global $message, $messageType;
    
    try {
        // Get all positions
        $stmt = $pdo->query("SELECT * FROM positions WHERE posStat = 'active' ORDER BY posID");
        $positions = $stmt->fetchAll();
        
        $winners_data = [];
        
        foreach ($positions as $position) {
            $posID = $position['posID'];
            $numOfPositions = $position['numOfPositions'];
            
            // Get top candidates with vote counts using JOIN
            $numOfPositions = (int)$numOfPositions; // Cast to int for LIMIT
            $stmt = $pdo->prepare("
                SELECT c.candID, c.candFName, c.candMName, c.candLName, COUNT(v.candid) as vote_count 
                FROM candidates c 
                LEFT JOIN votes v ON c.candID = v.candid AND v.posID = c.posID
                WHERE c.posID = ? AND c.candStat = 'active'
                GROUP BY c.candID 
                ORDER BY vote_count DESC 
                LIMIT $numOfPositions
            ");
            $stmt->execute([$posID]);
            $winners = $stmt->fetchAll();
            
            foreach ($winners as $winner) {
                $winners_data[] = [
                    'posID' => $posID,
                    'posName' => $position['posName'],
                    'candID' => $winner['candID'],
                    'candidateName' => getFullName($winner['candFName'], $winner['candMName'], $winner['candLName']),
                    'vote_count' => $winner['vote_count']
                ];
            }
        }
        
        $message = "Winners calculated successfully! Found " . count($winners_data) . " winner(s).";
        $messageType = 'success';
        
        // Store winners in session for display (or you can create a winners table)
        $_SESSION['winners'] = $winners_data;
    } catch(PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'danger';
    }
}

function resetVotingSession($pdo) {
    global $message, $messageType;
    
    try {
        $pdo->beginTransaction();
        
        $pdo->exec("DELETE FROM votes");
        $pdo->exec("UPDATE voters SET voted = 0");
        
        $pdo->commit();
        $message = "Voting session reset successfully!";
        $messageType = 'success';
    } catch(PDOException $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
        $messageType = 'danger';
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
    <title>Voting System - Skills Test</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div>
        <?php if ($page != 'login'): ?>
        <!-- Navigation -->
        <nav>
            <?php if (isset($_SESSION['voterID'])): ?>
                <a href="?page=vote">Vote</a> |
                <a href="?page=results">Results</a> |
                <a href="?page=winners">Winners</a> |
                <span>Welcome, <?= htmlspecialchars($_SESSION['voterName'] ?? '') ?></span> |
                <a href="?page=logout">Logout</a>
            <?php else: ?>
                <a href="?page=admin">Admin Dashboard</a> |
                <a href="?page=positions">Positions</a> |
                <a href="?page=candidates">Candidates</a> |
                <a href="?page=voters">Voters</a> |
                <a href="?page=results">Results</a> |
                <a href="?page=winners">Winners</a> |
                <a href="?page=login">Login</a>
            <?php endif; ?>
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
        <?php endif; ?>

        <!-- Messages -->
        <?php if ($message): ?>
            <div>
                <strong><?= $messageType == 'success' ? 'Success' : 'Error' ?>:</strong> <?= htmlspecialchars($message) ?>
            </div>
            <br>
        <?php endif; ?>

        <!-- Page Content -->
        <?php
        // Handle logout
        if ($page == 'logout') {
            session_destroy();
            redirect('?page=login');
        }
        
        // Login Page
        if ($page == 'login'):
        ?>
            <div>
                <h3>Voter Login</h3>
                <form method="post">
                    <input type="hidden" name="page" value="login">
                    <div>
                        <label>Voter ID:</label>
                        <input type="text" name="voterID" required>
                    </div>
                    <div>
                        <label>Password:</label>
                        <input type="password" name="voterPass" required>
                    </div>
                    <div>
                        <button type="submit">Login</button>
                    </div>
                </form>
                <p><a href="?page=admin">Admin Access (No Login Required)</a></p>
            </div>
        <?php
        // Admin Dashboard
        elseif ($page == 'admin' || $page == ''): 
            $stats = [
                'positions' => $pdo->query("SELECT COUNT(*) FROM positions")->fetchColumn(),
                'candidates' => $pdo->query("SELECT COUNT(*) FROM candidates")->fetchColumn(),
                'voters' => $pdo->query("SELECT COUNT(*) FROM voters")->fetchColumn(),
                'votes' => $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn()
            ];
        ?>
            <div>
                <h3>Admin Dashboard</h3>
                <div>
                    <div>
                        <h5>Positions</h5>
                        <h2><?= $stats['positions'] ?></h2>
                    </div>
                    <div>
                        <h5>Candidates</h5>
                        <h2><?= $stats['candidates'] ?></h2>
                    </div>
                    <div>
                        <h5>Voters</h5>
                        <h2><?= $stats['voters'] ?></h2>
                    </div>
                    <div>
                        <h5>Total Votes</h5>
                        <h2><?= $stats['votes'] ?></h2>
                    </div>
                </div>
                <div>
                    <h5>Quick Actions</h5>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Calculate winners now?')">
                        <input type="hidden" name="page" value="calculate_winners">
                        <button type="submit">Calculate Winners</button>
                    </form>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Reset all voting data? This cannot be undone!')">
                        <input type="hidden" name="page" value="reset_voting">
                        <button type="submit">Reset Voting Session</button>
                    </form>
                </div>
            </div>
        <?php
        // Positions Page
        elseif ($page == 'positions'):
            if ($search) {
                $stmt = $pdo->prepare("SELECT * FROM positions WHERE posName LIKE ? ORDER BY posID");
                $stmt->execute(["%$search%"]);
                $positions = $stmt->fetchAll();
            } else {
                $positions = $pdo->query("SELECT * FROM positions ORDER BY posID")->fetchAll();
            }
        ?>
            <div>
                <h5>Manage Positions <button onclick="showPositionForm('add')">Add Position</button></h5>
                <table border="1">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Position Name</th>
                            <th>Number of Positions</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($positions as $pos): ?>
                            <tr>
                                <td><?= $pos['posID'] ?></td>
                                <td><?= htmlspecialchars($pos['posName']) ?></td>
                                <td><?= $pos['numOfPositions'] ?></td>
                                <td><?= $pos['posStat'] ?></td>
                                <td>
                                    <button onclick="editPosition(<?= $pos['posID'] ?>, '<?= htmlspecialchars($pos['posName']) ?>', <?= $pos['numOfPositions'] ?>)">Edit</button>
                                    <?php if ($pos['posStat'] == 'active'): ?>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Deactivate this position?')">
                                            <input type="hidden" name="action" value="deactivate">
                                            <input type="hidden" name="posID" value="<?= $pos['posID'] ?>">
                                            <button type="submit">Deactivate</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="activate">
                                            <input type="hidden" name="posID" value="<?= $pos['posID'] ?>">
                                            <button type="submit">Activate</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Position Form -->
            <div id="positionForm" style="display:none; border:1px solid #000; padding:10px; margin:10px 0;">
                <h5 id="positionFormTitle">Add Position</h5>
                <form method="post">
                    <input type="hidden" name="action" id="position_action" value="add">
                    <input type="hidden" name="posID" id="posID">
                    <div>
                        <label>Position Name</label>
                        <input type="text" name="posName" id="posName" required>
                    </div>
                    <div>
                        <label>Number of Positions</label>
                        <input type="number" name="numOfPositions" id="numOfPositions" value="1" min="1" required>
                    </div>
                    <div>
                        <button type="button" onclick="document.getElementById('positionForm').style.display='none'">Cancel</button>
                        <button type="submit">Save</button>
                    </div>
                </form>
            </div>

            <script>
            function showPositionForm(mode, id, name, num) {
                document.getElementById('position_action').value = mode;
                document.getElementById('posID').value = id || '';
                document.getElementById('posName').value = name || '';
                document.getElementById('numOfPositions').value = num || 1;
                document.getElementById('positionFormTitle').textContent = mode == 'add' ? 'Add Position' : 'Edit Position';
                document.getElementById('positionForm').style.display = 'block';
            }
            function editPosition(id, name, num) {
                showPositionForm('edit', id, name, num);
            }
            </script>
        <?php
        // Candidates Page
        elseif ($page == 'candidates'):
            if ($search) {
                $stmt = $pdo->prepare("
                    SELECT c.*, p.posName 
                    FROM candidates c 
                    JOIN positions p ON c.posID = p.posID 
                    WHERE CONCAT(c.candFName, ' ', COALESCE(c.candMName, ''), ' ', c.candLName) LIKE ? OR p.posName LIKE ?
                    ORDER BY p.posID, c.candLName, c.candFName
                ");
                $stmt->execute(["%$search%", "%$search%"]);
                $candidates = $stmt->fetchAll();
            } else {
                $candidates = $pdo->query("
                    SELECT c.*, p.posName 
                    FROM candidates c 
                    JOIN positions p ON c.posID = p.posID 
                    ORDER BY p.posID, c.candLName, c.candFName
                ")->fetchAll();
            }
            $positions = $pdo->query("SELECT * FROM positions ORDER BY posID")->fetchAll();
        ?>
            <div>
                <h5>Manage Candidates <button onclick="showCandidateForm('add')">Add Candidate</button></h5>
                <table border="1">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($candidates as $cand): ?>
                            <tr>
                                <td><?= $cand['candID'] ?></td>
                                <td><?= htmlspecialchars(getFullName($cand['candFName'], $cand['candMName'], $cand['candLName'])) ?></td>
                                <td><?= htmlspecialchars($cand['posName']) ?></td>
                                <td><?= $cand['candStat'] ?></td>
                                <td>
                                    <button onclick="editCandidate(<?= $cand['candID'] ?>, '<?= htmlspecialchars($cand['candFName']) ?>', '<?= htmlspecialchars($cand['candMName'] ?? '') ?>', '<?= htmlspecialchars($cand['candLName']) ?>', <?= $cand['posID'] ?>)">Edit</button>
                                    <?php if ($cand['candStat'] == 'active'): ?>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Deactivate this candidate?')">
                                            <input type="hidden" name="action" value="deactivate">
                                            <input type="hidden" name="candID" value="<?= $cand['candID'] ?>">
                                            <button type="submit">Deactivate</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="activate">
                                            <input type="hidden" name="candID" value="<?= $cand['candID'] ?>">
                                            <button type="submit">Activate</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Candidate Form -->
            <div id="candidateForm" style="display:none; border:1px solid #000; padding:10px; margin:10px 0;">
                <h5 id="candidateFormTitle">Add Candidate</h5>
                <form method="post">
                    <input type="hidden" name="action" id="candidate_action" value="add">
                    <input type="hidden" name="candID" id="candID">
                    <div>
                        <label>First Name</label>
                        <input type="text" name="candFName" id="candFName" required>
                    </div>
                    <div>
                        <label>Middle Name</label>
                        <input type="text" name="candMName" id="candMName">
                    </div>
                    <div>
                        <label>Last Name</label>
                        <input type="text" name="candLName" id="candLName" required>
                    </div>
                    <div>
                        <label>Position</label>
                        <select name="posID" id="candidate_posID" required>
                            <option value="">Select Position</option>
                            <?php foreach($positions as $pos): ?>
                                <option value="<?= $pos['posID'] ?>"><?= htmlspecialchars($pos['posName']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="button" onclick="document.getElementById('candidateForm').style.display='none'">Cancel</button>
                        <button type="submit">Save</button>
                    </div>
                </form>
            </div>

            <script>
            function showCandidateForm(mode, id, fname, mname, lname, posID) {
                document.getElementById('candidate_action').value = mode;
                document.getElementById('candID').value = id || '';
                document.getElementById('candFName').value = fname || '';
                document.getElementById('candMName').value = mname || '';
                document.getElementById('candLName').value = lname || '';
                document.getElementById('candidate_posID').value = posID || '';
                document.getElementById('candidateFormTitle').textContent = mode == 'add' ? 'Add Candidate' : 'Edit Candidate';
                document.getElementById('candidateForm').style.display = 'block';
            }
            function editCandidate(id, fname, mname, lname, posID) {
                showCandidateForm('edit', id, fname, mname, lname, posID);
            }
            </script>
        <?php
        // Voters Page
        elseif ($page == 'voters'):
            $per_page = 10;
            $current_page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
            $offset = ($current_page - 1) * $per_page;
            
            if ($search) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM voters WHERE CONCAT(voterFName, ' ', COALESCE(voterMName, ''), ' ', voterLName) LIKE ? OR voterID LIKE ?");
                $stmt->execute(["%$search%", "%$search%"]);
                $total = $stmt->fetchColumn();
                $total_pages = ceil($total / $per_page);
                
                $per_page = (int)$per_page;
                $offset = (int)$offset;
                $voters = $pdo->prepare("SELECT * FROM voters WHERE CONCAT(voterFName, ' ', COALESCE(voterMName, ''), ' ', voterLName) LIKE ? OR voterID LIKE ? ORDER BY voterLName, voterFName LIMIT $per_page OFFSET $offset");
                $voters->bindValue(1, "%$search%", PDO::PARAM_STR);
                $voters->bindValue(2, "%$search%", PDO::PARAM_STR);
                $voters->execute();
                $voters = $voters->fetchAll();
            } else {
                $total = $pdo->query("SELECT COUNT(*) FROM voters")->fetchColumn();
                $total_pages = ceil($total / $per_page);
                
                $per_page = (int)$per_page;
                $offset = (int)$offset;
                $voters = $pdo->prepare("SELECT * FROM voters ORDER BY voterLName, voterFName LIMIT $per_page OFFSET $offset");
                $voters->execute();
                $voters = $voters->fetchAll();
            }
        ?>
            <div>
                <h5>Manage Voters <button onclick="showVoterForm('add')">Add Voter</button></h5>
                <table border="1">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Voted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($voters as $voter): ?>
                            <tr>
                                <td><?= $voter['voterID'] ?></td>
                                <td><?= htmlspecialchars(getFullName($voter['voterFName'], $voter['voterMName'], $voter['voterLName'])) ?></td>
                                <td><?= $voter['voterStat'] ?></td>
                                <td><?= $voter['voted'] ? 'Yes' : 'No' ?></td>
                                <td>
                                    <button onclick="editVoter(<?= $voter['voterID'] ?>, '<?= htmlspecialchars($voter['voterFName']) ?>', '<?= htmlspecialchars($voter['voterMName'] ?? '') ?>', '<?= htmlspecialchars($voter['voterLName']) ?>')">Edit</button>
                                    <?php if ($voter['voterStat'] == 'active'): ?>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Deactivate this voter?')">
                                            <input type="hidden" name="action" value="deactivate">
                                            <input type="hidden" name="voterID" value="<?= $voter['voterID'] ?>">
                                            <button type="submit">Deactivate</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="activate">
                                            <input type="hidden" name="voterID" value="<?= $voter['voterID'] ?>">
                                            <button type="submit">Activate</button>
                                        </form>
                                    <?php endif; ?>
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
                                <a href="?page=voters&p=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Voter Form -->
            <div id="voterForm" style="display:none; border:1px solid #000; padding:10px; margin:10px 0;">
                <h5 id="voterFormTitle">Add Voter</h5>
                <form method="post">
                    <input type="hidden" name="action" id="voter_action" value="add">
                    <input type="hidden" name="voterID" id="voterID">
                    <div>
                        <label>First Name</label>
                        <input type="text" name="voterFName" id="voterFName" required>
                    </div>
                    <div>
                        <label>Middle Name</label>
                        <input type="text" name="voterMName" id="voterMName">
                    </div>
                    <div>
                        <label>Last Name</label>
                        <input type="text" name="voterLName" id="voterLName" required>
                    </div>
                    <div>
                        <label>Password</label>
                        <input type="password" name="voterPass" id="voterPass">
                        <small>(Leave blank when editing to keep current password)</small>
                    </div>
                    <div>
                        <button type="button" onclick="document.getElementById('voterForm').style.display='none'">Cancel</button>
                        <button type="submit">Save</button>
                    </div>
                </form>
            </div>

            <script>
            function showVoterForm(mode, id, fname, mname, lname) {
                document.getElementById('voter_action').value = mode;
                document.getElementById('voterID').value = id || '';
                document.getElementById('voterFName').value = fname || '';
                document.getElementById('voterMName').value = mname || '';
                document.getElementById('voterLName').value = lname || '';
                document.getElementById('voterPass').value = '';
                document.getElementById('voterFormTitle').textContent = mode == 'add' ? 'Add Voter' : 'Edit Voter';
                document.getElementById('voterForm').style.display = 'block';
            }
            function editVoter(id, fname, mname, lname) {
                showVoterForm('edit', id, fname, mname, lname);
            }
            </script>
        <?php
        // Vote Page
        elseif ($page == 'vote'):
            requireLogin();
            
            // Check if already voted
            if (isset($_SESSION['voted']) && $_SESSION['voted']) {
                echo "<h3>You have already voted.</h3>";
                echo "<p><a href='?page=results'>View Results</a></p>";
            } else {
                $positions = $pdo->query("SELECT * FROM positions WHERE posStat = 'active' ORDER BY posID")->fetchAll();
                foreach($positions as &$pos) {
                    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE posID = ? AND candStat = 'active' ORDER BY candLName, candFName");
                    $stmt->execute([$pos['posID']]);
                    $pos['candidates'] = $stmt->fetchAll();
                }
        ?>
            <div>
                <h5>Cast Your Vote</h5>
                <p>Welcome, <?= htmlspecialchars($_SESSION['voterName'] ?? '') ?></p>
                <form method="post">
                    <input type="hidden" name="page" value="vote">
                    <?php foreach($positions as $position): ?>
                        <?php if (count($position['candidates']) > 0): ?>
                            <div style="border:1px solid #000; padding:10px; margin:10px 0;">
                                <strong><?= htmlspecialchars($position['posName']) ?></strong>
                                <small>(Select up to <?= $position['numOfPositions'] ?>)</small>
                                <div>
                                    <?php if ($position['numOfPositions'] == 1): ?>
                                        <select name="votes[<?= $position['posID'] ?>]" required>
                                            <option value="">Select Candidate</option>
                                            <?php foreach($position['candidates'] as $cand): ?>
                                                <option value="<?= $cand['candID'] ?>">
                                                    <?= htmlspecialchars(getFullName($cand['candFName'], $cand['candMName'], $cand['candLName'])) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <?php foreach($position['candidates'] as $cand): ?>
                                            <div>
                                                <input type="checkbox" 
                                                       name="votes[<?= $position['posID'] ?>][]" 
                                                       value="<?= $cand['candID'] ?>"
                                                       data-max="<?= $position['numOfPositions'] ?>"
                                                       onchange="checkMax(this, <?= $position['posID'] ?>, <?= $position['numOfPositions'] ?>)">
                                                <label><?= htmlspecialchars(getFullName($cand['candFName'], $cand['candMName'], $cand['candLName'])) ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <button type="submit">Submit Vote</button>
                </form>
            </div>

            <script>
            function checkMax(checkbox, posID, max) {
                const checkboxes = document.querySelectorAll(`input[name="votes[${posID}][]"]:checked`);
                if (checkboxes.length > max) {
                    checkbox.checked = false;
                    alert(`You can only select up to ${max} candidate(s) for this position.`);
                }
            }
            </script>
        <?php
            }
        // Results Page
        elseif ($page == 'results'):
            $per_page = 10;
            $current_page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
            $offset = ($current_page - 1) * $per_page;
            
            // Get total votes per position for percentage calculation
            $position_totals = [];
            $stmt = $pdo->query("SELECT posID, COUNT(*) as total FROM votes GROUP BY posID");
            while ($row = $stmt->fetch()) {
                $position_totals[$row['posID']] = $row['total'];
            }
            
            if ($search) {
                $stmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT c.candID) 
                    FROM votes v 
                    JOIN candidates c ON v.candid = c.candID
                    JOIN positions p ON v.posID = p.posID
                    WHERE CONCAT(c.candFName, ' ', COALESCE(c.candMName, ''), ' ', c.candLName) LIKE ? OR p.posName LIKE ?
                ");
                $stmt->execute(["%$search%", "%$search%"]);
                $total = $stmt->fetchColumn();
                $total_pages = ceil($total / $per_page);
                
                $per_page = (int)$per_page;
                $offset = (int)$offset;
                $results = $pdo->prepare("
                    SELECT c.candID, c.candFName, c.candMName, c.candLName, p.posID, p.posName, COUNT(*) AS votes 
                    FROM votes v 
                    JOIN candidates c ON v.candid = c.candID
                    JOIN positions p ON v.posID = p.posID
                    WHERE CONCAT(c.candFName, ' ', COALESCE(c.candMName, ''), ' ', c.candLName) LIKE ? OR p.posName LIKE ?
                    GROUP BY c.candID, p.posID
                    ORDER BY p.posName, votes DESC
                    LIMIT $per_page OFFSET $offset
                ");
                $results->bindValue(1, "%$search%", PDO::PARAM_STR);
                $results->bindValue(2, "%$search%", PDO::PARAM_STR);
                $results->execute();
                $results = $results->fetchAll();
            } else {
                $total = $pdo->query("SELECT COUNT(DISTINCT CONCAT(c.candID, '-', v.posID)) FROM votes v JOIN candidates c ON v.candid = c.candID")->fetchColumn();
                $total_pages = ceil($total / $per_page);
                
                $per_page = (int)$per_page;
                $offset = (int)$offset;
                $results = $pdo->prepare("
                    SELECT c.candID, c.candFName, c.candMName, c.candLName, p.posID, p.posName, COUNT(*) AS votes 
                    FROM votes v 
                    JOIN candidates c ON v.candid = c.candID
                    JOIN positions p ON v.posID = p.posID
                    GROUP BY c.candID, p.posID
                    ORDER BY p.posName, votes DESC
                    LIMIT $per_page OFFSET $offset
                ");
                $results->execute();
                $results = $results->fetchAll();
            }
        ?>
            <div>
                <h5>Election Results</h5>
                <table border="1">
                    <thead>
                        <tr>
                            <th>Position</th>
                            <th>Candidate</th>
                            <th>Total Votes</th>
                            <th>Voting %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($results as $result): 
                            $total_votes_for_position = $position_totals[$result['posID']] ?? 1;
                            $percentage = $total_votes_for_position > 0 ? ($result['votes'] / $total_votes_for_position) * 100 : 0;
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($result['posName']) ?></td>
                                <td><?= htmlspecialchars(getFullName($result['candFName'], $result['candMName'], $result['candLName'])) ?></td>
                                <td><?= $result['votes'] ?></td>
                                <td><?= number_format($percentage, 2) ?>%</td>
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
                                <a href="?page=results&p=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php
        // Winners Page
        elseif ($page == 'winners'):
            // Get winners from session or calculate on the fly
            if (isset($_SESSION['winners']) && !empty($_SESSION['winners'])) {
                $winners = $_SESSION['winners'];
            } else {
                // Calculate winners on the fly
                $winners = [];
                $positions = $pdo->query("SELECT * FROM positions WHERE posStat = 'active' ORDER BY posID")->fetchAll();
                
                foreach ($positions as $position) {
                    $posID = $position['posID'];
                    $numOfPositions = $position['numOfPositions'];
                    
                    $numOfPositions = (int)$numOfPositions; // Cast to int for LIMIT
                    $stmt = $pdo->prepare("
                        SELECT c.candID, c.candFName, c.candMName, c.candLName, COUNT(v.candid) as vote_count 
                        FROM candidates c 
                        LEFT JOIN votes v ON c.candID = v.candid AND v.posID = c.posID
                        WHERE c.posID = ? AND c.candStat = 'active'
                        GROUP BY c.candID 
                        ORDER BY vote_count DESC 
                        LIMIT $numOfPositions
                    ");
                    $stmt->execute([$posID]);
                    $position_winners = $stmt->fetchAll();
                    
                    foreach ($position_winners as $winner) {
                        $winners[] = [
                            'posID' => $posID,
                            'posName' => $position['posName'],
                            'candID' => $winner['candID'],
                            'candidateName' => getFullName($winner['candFName'], $winner['candMName'], $winner['candLName']),
                            'vote_count' => $winner['vote_count']
                        ];
                    }
                }
            }
            
            // Group winners by position
            $winners_by_position = [];
            foreach ($winners as $winner) {
                $winners_by_position[$winner['posName']][] = $winner;
            }
        ?>
            <div>
                <h5>Election Winners
                    <form method="post" style="display:inline;" onsubmit="return confirm('Calculate winners now?')">
                        <input type="hidden" name="page" value="calculate_winners">
                        <button type="submit">Calculate Winners</button>
                    </form>
                </h5>
                <div>
                    <?php if (count($winners) > 0): ?>
                        <table border="1">
                            <thead>
                                <tr>
                                    <th>Elective Position</th>
                                    <th>Winner</th>
                                    <th>Total Votes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($winners_by_position as $posName => $position_winners): ?>
                                    <?php foreach($position_winners as $winner): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($winner['posName']) ?></td>
                                            <td><?= htmlspecialchars($winner['candidateName']) ?></td>
                                            <td><?= $winner['vote_count'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No winners calculated yet. Click "Calculate Winners" to compute results.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>

