<?php
/**
 * Single-File Voting System
 * Auto-creates database and tables on first access
 * No login/authentication required
 * 
 * ============================================================================
 * MIGRATION GUIDE - How to adapt this for other modules (Pet Owner, etc.)
 * ============================================================================
 * 
 * To migrate to a different module (e.g., Pet Owner System):
 * 
 * 1. DATABASE CONFIGURATION (Line 15-18):
 *    - Change DB_NAME to your new database name
 *    - Keep DB_HOST, DB_USER, DB_PASS as needed
 * 
 * 2. TABLE STRUCTURE (Line 58-134):
 *    - Rename function: createElectionTables() -> createPetOwnerTables()
 *    - Change table names:
 *      * positions -> pet_categories (or categories)
 *      * candidates -> pets (or items)
 *      * users -> owners (or users - keep if same)
 *      * votes -> adoptions (or transactions)
 *      * winners -> featured_pets (or top_pets)
 *    - Change column names to match your domain:
 *      * position_name -> category_name
 *      * candidate_id -> pet_id
 *      * full_name -> pet_name
 *      * max_winners -> max_adoptions
 *      * has_voted -> has_adopted
 * 
 * 3. FUNCTION NAMES (Throughout file):
 *    - handlePositionAction() -> handleCategoryAction()
 *    - handleCandidateAction() -> handlePetAction()
 *    - handleVoterAction() -> handleOwnerAction()
 *    - handleVoteSubmission() -> handleAdoptionSubmission()
 *    - calculateWinners() -> calculateFeaturedPets()
 *    - resetVotingSession() -> resetAdoptionSession()
 * 
 * 4. ROUTING (Line 158-187):
 *    - Change page names:
 *      * 'positions' -> 'categories'
 *      * 'candidates' -> 'pets'
 *      * 'voters' -> 'owners'
 *      * 'vote' -> 'adopt'
 *      * 'winners' -> 'featured'
 * 
 * 5. HTML LABELS & TEXT (Line 533+):
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
 * 8. SEARCH FUNCTIONALITY (Line 225, 624-631, and in each page):
 *    - Search variable: $search (already added)
 *    - Search bar: Already in navigation (Line 624-631)
 *    - For each page, add WHERE clause with LIKE:
 *      * Positions: WHERE position_name LIKE ?
 *      * Candidates: WHERE full_name LIKE ? OR position_name LIKE ?
 *      * Voters: WHERE full_name LIKE ? OR email LIKE ?
 *      * Results: WHERE candidate_name LIKE ? OR position_name LIKE ?
 *    - Update pagination links to preserve search: &search=<?= urlencode($search) ?>
 * 
 * ============================================================================
 * QUICK EXAMPLES - Common Module Adaptations
 * ============================================================================
 * 
 * EXAMPLE 1: Pet Owner System
 * - positions -> pet_categories (Dogs, Cats, Birds)
 * - candidates -> pets (individual pets)
 * - users -> owners (pet owners)
 * - votes -> adoptions (adoption records)
 * - winners -> featured_pets (most adopted)
 * 
 * EXAMPLE 2: Product Inventory System
 * - positions -> categories (Electronics, Clothing, Food)
 * - candidates -> products (individual products)
 * - users -> suppliers (or keep as users)
 * - votes -> orders (or purchases)
 * - winners -> best_sellers (top products)
 * 
 * EXAMPLE 3: Event Management System
 * - positions -> event_types (Concert, Conference, Workshop)
 * - candidates -> events (individual events)
 * - users -> attendees (or participants)
 * - votes -> registrations (or bookings)
 * - winners -> popular_events (most registered)
 * 
 * EXAMPLE 4: Restaurant Menu System
 * - positions -> menu_categories (Appetizers, Main Course, Desserts)
 * - candidates -> menu_items (individual dishes)
 * - users -> customers (or diners)
 * - votes -> orders (or favorites)
 * - winners -> popular_items (most ordered)
 * 
 * EXAMPLE 5: Course/Class System
 * - positions -> course_categories (Programming, Design, Business)
 * - candidates -> courses (individual courses)
 * - users -> students (or learners)
 * - votes -> enrollments (or selections)
 * - winners -> popular_courses (most enrolled)
 * 
 * ============================================================================
 * HOW TO ADD NEW TABLES/COLUMNS
 * ============================================================================
 * 
 * To add new tables:
 * 1. Add CREATE TABLE query in createElectionTables() function (Line 134)
 * 2. Add CRUD handler function (similar to handlePositionAction)
 * 3. Add routing case in switch statement (Line 242)
 * 4. Add HTML page section (similar to Positions page)
 * 5. Add navigation link in nav section (Line 621)
 * 
 * To add new columns to existing tables:
 * 1. Add column in CREATE TABLE query (Line 136-188)
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

// Function to create all election tables
function createElectionTables($pdo) {
    $tables = [
        "CREATE TABLE IF NOT EXISTS positions (
            position_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            position_name VARCHAR(100) NOT NULL UNIQUE,
            max_winners INT(11) DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS candidates (
            candidate_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            position_id INT(11) NOT NULL,
            photo VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (position_id) REFERENCES positions(position_id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS users (
            user_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'voter') DEFAULT 'voter',
            has_voted TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS votes (
            vote_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            voter_id INT(11) NOT NULL,
            candidate_id INT(11) NOT NULL,
            position_id INT(11) NOT NULL,
            voted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (voter_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (candidate_id) REFERENCES candidates(candidate_id) ON DELETE CASCADE,
            FOREIGN KEY (position_id) REFERENCES positions(position_id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(50) NOT NULL PRIMARY KEY,
            setting_value TEXT DEFAULT NULL
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS winners (
            winner_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            position_id INT(11) NOT NULL,
            position_name VARCHAR(255) NOT NULL,
            candidate_id INT(11) NOT NULL,
            candidate_name VARCHAR(255) NOT NULL,
            vote_count INT(11) DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (position_id) REFERENCES positions(position_id),
            FOREIGN KEY (candidate_id) REFERENCES candidates(candidate_id)
        ) ENGINE=InnoDB"
    ];
    
    foreach($tables as $sql) {
        try {
            $pdo->exec($sql);
        } catch(PDOException $e) {
            // Ignore errors for existing tables
        }
    }
    
    // OPTIONAL: Initialize default settings if they don't exist
    // You can remove this entire block if you don't need default settings
    // The code will still work - users can set settings manually via Settings page
    $defaultSettings = [
        'voting_start' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'voting_end' => date('Y-m-d H:i:s', strtotime('+2 days')),
        'voting_status' => 'inactive'
    ];
    
    foreach($defaultSettings as $key => $value) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function redirect($url) {
    header("Location: $url");
    exit;
}

function getSettings($pdo) {
    $stmt = $pdo->query("SELECT * FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

// ============================================================================
// ROUTING SYSTEM
// ============================================================================

$page = isset($_GET['page']) ? $_GET['page'] : 'admin';
$action = isset($_POST['action']) ? $_POST['action'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$message = '';
$messageType = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    switch($page) {
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
        case 'settings':
            handleSettingsUpdate($pdo);
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
// HANDLERS FOR CRUD OPERATIONS
// ============================================================================

function handlePositionAction($pdo, $action) {
    global $message, $messageType;
    
    if ($action == 'add') {
        $name = trim($_POST['position_name'] ?? '');
        $max_winners = (int)($_POST['max_winners'] ?? 1);
        
        if (empty($name)) {
            $message = "Position name is required.";
            $messageType = 'danger';
            return;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO positions (position_name, max_winners) VALUES (?, ?)");
            $stmt->execute([$name, $max_winners]);
            $message = "Position added successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action == 'edit') {
        $id = (int)($_POST['position_id'] ?? 0);
        $name = trim($_POST['position_name'] ?? '');
        $max_winners = (int)($_POST['max_winners'] ?? 1);
        
        try {
            $stmt = $pdo->prepare("UPDATE positions SET position_name = ?, max_winners = ? WHERE position_id = ?");
            $stmt->execute([$name, $max_winners, $id]);
            $message = "Position updated successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action == 'delete') {
        $id = (int)($_POST['position_id'] ?? 0);
        
        try {
            // Check for associated candidates
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE position_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                $message = "Cannot delete position with associated candidates.";
                $messageType = 'danger';
                return;
            }
            
            $stmt = $pdo->prepare("DELETE FROM positions WHERE position_id = ?");
            $stmt->execute([$id]);
            $message = "Position deleted successfully!";
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
        $name = trim($_POST['full_name'] ?? '');
        $position_id = (int)($_POST['position_id'] ?? 0);
        
        if (empty($name) || $position_id == 0) {
            $message = "All fields are required.";
            $messageType = 'danger';
            return;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO candidates (full_name, position_id) VALUES (?, ?)");
            $stmt->execute([$name, $position_id]);
            $message = "Candidate added successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action == 'edit') {
        $id = (int)($_POST['candidate_id'] ?? 0);
        $name = trim($_POST['full_name'] ?? '');
        $position_id = (int)($_POST['position_id'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE candidates SET full_name = ?, position_id = ? WHERE candidate_id = ?");
            $stmt->execute([$name, $position_id, $id]);
            $message = "Candidate updated successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action == 'delete') {
        $id = (int)($_POST['candidate_id'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("DELETE FROM candidates WHERE candidate_id = ?");
            $stmt->execute([$id]);
            $message = "Candidate deleted successfully!";
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
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($name) || empty($email)) {
            $message = "All fields are required.";
            $messageType = 'danger';
            return;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'voter')");
            $stmt->execute([$name, $email, password_hash('password123', PASSWORD_DEFAULT)]);
            $message = "Voter added successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action == 'edit') {
        $id = (int)($_POST['user_id'] ?? 0);
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ? AND role = 'voter'");
            $stmt->execute([$name, $email, $id]);
            $message = "Voter updated successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action == 'delete') {
        $id = (int)($_POST['user_id'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'voter'");
            $stmt->execute([$id]);
            $message = "Voter deleted successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

function handleVoteSubmission($pdo) {
    global $message, $messageType;
    
    if (empty($_POST['voter_id']) || empty($_POST['votes'])) {
        $message = "Please select at least one candidate.";
        $messageType = 'danger';
        return;
    }
    
    $voter_id = (int)$_POST['voter_id'];
    
    // Check if voter already voted
    $stmt = $pdo->prepare("SELECT has_voted FROM users WHERE user_id = ?");
    $stmt->execute([$voter_id]);
    $user = $stmt->fetch();
    
    if ($user && $user['has_voted']) {
        $message = "You have already voted.";
        $messageType = 'danger';
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Clear previous votes for this voter
        $stmt = $pdo->prepare("DELETE FROM votes WHERE voter_id = ?");
        $stmt->execute([$voter_id]);
        
        // Process votes
        foreach ($_POST['votes'] as $position_id => $selected) {
            $position_id = (int)$position_id;
            $candidate_ids = is_array($selected) ? array_map('intval', $selected) : [(int)$selected];
            
            // Get position info
            $stmt = $pdo->prepare("SELECT max_winners FROM positions WHERE position_id = ?");
            $stmt->execute([$position_id]);
            $position = $stmt->fetch();
            
            if (!$position || count($candidate_ids) > $position['max_winners']) {
                throw new Exception("Invalid selection for position");
            }
            
            // Insert votes
            foreach ($candidate_ids as $candidate_id) {
                $stmt = $pdo->prepare("INSERT INTO votes (voter_id, candidate_id, position_id) VALUES (?, ?, ?)");
                $stmt->execute([$voter_id, $candidate_id, $position_id]);
            }
        }
        
        // Mark voter as voted
        $stmt = $pdo->prepare("UPDATE users SET has_voted = 1 WHERE user_id = ?");
        $stmt->execute([$voter_id]);
        
        $pdo->commit();
        $message = "Vote submitted successfully!";
        $messageType = 'success';
    } catch(Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
        $messageType = 'danger';
    }
}

function handleSettingsUpdate($pdo) {
    global $message, $messageType;
    
    $start = $_POST['start'] ?? '';
    $end = $_POST['end'] ?? '';
    $status = $_POST['status'] ?? 'inactive';
    
    if (strtotime($end) <= strtotime($start)) {
        $message = "End time must be after start time!";
        $messageType = 'danger';
        return;
    }
    
    try {
        $start_datetime = date('Y-m-d H:i:s', strtotime($start));
        $end_datetime = date('Y-m-d H:i:s', strtotime($end));
        
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$start_datetime, 'voting_start']);
        $stmt->execute([$end_datetime, 'voting_end']);
        $stmt->execute([$status, 'voting_status']);
        
        $message = "Settings updated successfully!";
        $messageType = 'success';
    } catch(PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'danger';
    }
}

function calculateWinners($pdo) {
    global $message, $messageType;
    
    try {
        $pdo->beginTransaction();
        
        // Clear existing winners
        $pdo->exec("DELETE FROM winners");
        
        // Get all positions
        $stmt = $pdo->query("SELECT * FROM positions ORDER BY position_id");
        $positions = $stmt->fetchAll();
        
        foreach ($positions as $position) {
            $pos_id = $position['position_id'];
            $max_winners = $position['max_winners'];
            
            // Get top candidates with vote counts using JOIN
            $stmt = $pdo->prepare("
                SELECT c.candidate_id, c.full_name, COUNT(v.vote_id) as vote_count 
                FROM candidates c 
                LEFT JOIN votes v ON c.candidate_id = v.candidate_id 
                WHERE c.position_id = ? 
                GROUP BY c.candidate_id 
                ORDER BY vote_count DESC 
                LIMIT ?
            ");
            $stmt->execute([$pos_id, $max_winners]);
            $winners = $stmt->fetchAll();
            
            // Insert winners
            foreach ($winners as $winner) {
                $stmt = $pdo->prepare("
                    INSERT INTO winners (position_id, position_name, candidate_id, candidate_name, vote_count) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $pos_id,
                    $position['position_name'],
                    $winner['candidate_id'],
                    $winner['full_name'],
                    $winner['vote_count']
                ]);
            }
        }
        
        $pdo->commit();
        $message = "Winners calculated successfully!";
        $messageType = 'success';
    } catch(PDOException $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
        $messageType = 'danger';
    }
}

function resetVotingSession($pdo) {
    global $message, $messageType;
    
    try {
        $pdo->beginTransaction();
        
        $pdo->exec("DELETE FROM winners");
        $pdo->exec("DELETE FROM votes");
        $pdo->exec("DELETE FROM candidates");
        $pdo->exec("ALTER TABLE candidates AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE winners AUTO_INCREMENT = 1");
        $pdo->exec("UPDATE users SET has_voted = 0 WHERE role = 'voter'");
        $pdo->exec("UPDATE settings SET setting_value = 'active' WHERE setting_key = 'voting_status'");
        
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
    <title>Voting System</title>
</head>
<body>
    <div>
        <!-- Navigation -->
        <nav>
            <a href="?page=admin">Voting System</a> |
            <a href="?page=admin">Dashboard</a> |
            <a href="?page=positions">Positions</a> |
            <a href="?page=candidates">Candidates</a> |
            <a href="?page=voters">Voters</a> |
            <a href="?page=vote">Vote</a> |
            <a href="?page=results">Results</a> |
            <a href="?page=winners">Winners</a> |
            <a href="?page=settings">Settings</a>
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
        switch($page) {
            case 'admin':
                includePage('admin_dashboard', $pdo);
                break;
            case 'positions':
                includePage('positions', $pdo);
                break;
            case 'candidates':
                includePage('candidates', $pdo);
                break;
            case 'voters':
                includePage('voters', $pdo);
                break;
            case 'vote':
                includePage('vote', $pdo);
                break;
            case 'results':
                includePage('results', $pdo);
                break;
            case 'winners':
                includePage('winners', $pdo);
                break;
            case 'settings':
                includePage('settings', $pdo);
                break;
            default:
                includePage('admin_dashboard', $pdo);
        }

        function includePage($pageName, $pdo) {
            // This will be handled inline below
            // For now, we'll output the content directly
        }
        ?>

        <?php
        // Admin Dashboard
        if ($page == 'admin' || $page == ''): 
            $settings = getSettings($pdo);
            $stats = [
                'positions' => $pdo->query("SELECT COUNT(*) FROM positions")->fetchColumn(),
                'candidates' => $pdo->query("SELECT COUNT(*) FROM candidates")->fetchColumn(),
                'voters' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'voter'")->fetchColumn(),
                'votes' => $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn()
            ];
        ?>
            <div>
                <div>
                    <div>
                        <h5>Positions</h5>
                        <h2><?= $stats['positions'] ?></h2>
                    </div>
                </div>
                <div>
                    <div>
                        <h5>Candidates</h5>
                        <h2><?= $stats['candidates'] ?></h2>
                    </div>
                </div>
                <div>
                    <div>
                        <h5>Voters</h5>
                        <h2><?= $stats['voters'] ?></h2>
                    </div>
                </div>
                <div>
                    <div>
                        <h5>Total Votes</h5>
                        <h2><?= $stats['votes'] ?></h2>
                    </div>
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
        <?php endif; ?>

        <?php
        // Positions Page
        if ($page == 'positions'):
            if ($search) {
                $stmt = $pdo->prepare("SELECT * FROM positions WHERE position_name LIKE ? ORDER BY position_id");
                $stmt->execute(["%$search%"]);
                $positions = $stmt->fetchAll();
            } else {
                $positions = $pdo->query("SELECT * FROM positions ORDER BY position_id")->fetchAll();
            }
        ?>
            <div>
                <h5>Manage Positions <button onclick="showPositionForm('add')">Add Position</button></h5>
                <table border="1">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Position Name</th>
                                <th>Max Winners</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($positions as $pos): ?>
                                <tr>
                                    <td><?= $pos['position_id'] ?></td>
                                    <td><?= htmlspecialchars($pos['position_name']) ?></td>
                                    <td><?= $pos['max_winners'] ?></td>
                                    <td>
                                        <button onclick="editPosition(<?= $pos['position_id'] ?>, '<?= htmlspecialchars($pos['position_name']) ?>', <?= $pos['max_winners'] ?>)">Edit</button>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this position?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="position_id" value="<?= $pos['position_id'] ?>">
                                            <button type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Position Form -->
            <div id="positionForm" style="display:none; border:1px solid #000; padding:10px; margin:10px 0;">
                <h5 id="positionFormTitle">Add Position</h5>
                <form method="post">
                    <input type="hidden" name="action" id="position_action" value="add">
                    <input type="hidden" name="position_id" id="position_id">
                    <div>
                        <label>Position Name</label>
                        <input type="text" name="position_name" id="position_name" required>
                    </div>
                    <div>
                        <label>Max Winners</label>
                        <input type="number" name="max_winners" id="max_winners" value="1" min="1" required>
                    </div>
                    <div>
                        <button type="button" onclick="document.getElementById('positionForm').style.display='none'">Cancel</button>
                        <button type="submit">Save</button>
                    </div>
                </form>
            </div>

            <script>
            function showPositionForm(mode, id, name, max) {
                document.getElementById('position_action').value = mode;
                document.getElementById('position_id').value = id || '';
                document.getElementById('position_name').value = name || '';
                document.getElementById('max_winners').value = max || 1;
                document.getElementById('positionFormTitle').textContent = mode == 'add' ? 'Add Position' : 'Edit Position';
                document.getElementById('positionForm').style.display = 'block';
            }
            function editPosition(id, name, max) {
                showPositionForm('edit', id, name, max);
            }
            </script>
        <?php endif; ?>

        <?php
        // Candidates Page
        if ($page == 'candidates'):
            if ($search) {
                $stmt = $pdo->prepare("
                    SELECT c.*, p.position_name 
                    FROM candidates c 
                    JOIN positions p ON c.position_id = p.position_id 
                    WHERE c.full_name LIKE ? OR p.position_name LIKE ?
                    ORDER BY p.position_id, c.full_name
                ");
                $stmt->execute(["%$search%", "%$search%"]);
                $candidates = $stmt->fetchAll();
            } else {
                $candidates = $pdo->query("
                    SELECT c.*, p.position_name 
                    FROM candidates c 
                    JOIN positions p ON c.position_id = p.position_id 
                    ORDER BY p.position_id, c.full_name
                ")->fetchAll();
            }
            $positions = $pdo->query("SELECT * FROM positions ORDER BY position_id")->fetchAll();
        ?>
            <div>
                <h5>Manage Candidates <button onclick="showCandidateForm('add')">Add Candidate</button></h5>
                <table border="1">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($candidates as $cand): ?>
                                <tr>
                                    <td><?= $cand['candidate_id'] ?></td>
                                    <td><?= htmlspecialchars($cand['full_name']) ?></td>
                                    <td><?= htmlspecialchars($cand['position_name']) ?></td>
                                    <td>
                                        <button onclick="editCandidate(<?= $cand['candidate_id'] ?>, '<?= htmlspecialchars($cand['full_name']) ?>', <?= $cand['position_id'] ?>)">Edit</button>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this candidate?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="candidate_id" value="<?= $cand['candidate_id'] ?>">
                                            <button type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Candidate Form -->
            <div id="candidateForm" style="display:none; border:1px solid #000; padding:10px; margin:10px 0;">
                <h5 id="candidateFormTitle">Add Candidate</h5>
                <form method="post">
                    <input type="hidden" name="action" id="candidate_action" value="add">
                    <input type="hidden" name="candidate_id" id="candidate_id">
                    <div>
                        <label>Full Name</label>
                        <input type="text" name="full_name" id="candidate_name" required>
                    </div>
                    <div>
                        <label>Position</label>
                        <select name="position_id" id="candidate_position" required>
                            <option value="">Select Position</option>
                            <?php foreach($positions as $pos): ?>
                                <option value="<?= $pos['position_id'] ?>"><?= htmlspecialchars($pos['position_name']) ?></option>
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
            function showCandidateForm(mode, id, name, position) {
                document.getElementById('candidate_action').value = mode;
                document.getElementById('candidate_id').value = id || '';
                document.getElementById('candidate_name').value = name || '';
                document.getElementById('candidate_position').value = position || '';
                document.getElementById('candidateFormTitle').textContent = mode == 'add' ? 'Add Candidate' : 'Edit Candidate';
                document.getElementById('candidateForm').style.display = 'block';
            }
            function editCandidate(id, name, position) {
                showCandidateForm('edit', id, name, position);
            }
            </script>
        <?php endif; ?>

        <?php
        // Voters Page
        if ($page == 'voters'):
            $per_page = 10;
            $current_page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
            $offset = ($current_page - 1) * $per_page;
            
            if ($search) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'voter' AND (full_name LIKE ? OR email LIKE ?)");
                $stmt->execute(["%$search%", "%$search%"]);
                $total = $stmt->fetchColumn();
                $total_pages = ceil($total / $per_page);
                
                $voters = $pdo->prepare("SELECT * FROM users WHERE role = 'voter' AND (full_name LIKE ? OR email LIKE ?) ORDER BY full_name LIMIT ? OFFSET ?");
                $voters->bindValue(1, "%$search%", PDO::PARAM_STR);
                $voters->bindValue(2, "%$search%", PDO::PARAM_STR);
                $voters->bindValue(3, $per_page, PDO::PARAM_INT);
                $voters->bindValue(4, $offset, PDO::PARAM_INT);
                $voters->execute();
                $voters = $voters->fetchAll();
            } else {
                $total = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'voter'")->fetchColumn();
                $total_pages = ceil($total / $per_page);
                
                $voters = $pdo->prepare("SELECT * FROM users WHERE role = 'voter' ORDER BY full_name LIMIT ? OFFSET ?");
                $voters->bindValue(1, $per_page, PDO::PARAM_INT);
                $voters->bindValue(2, $offset, PDO::PARAM_INT);
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
                                <th>Email</th>
                                <th>Has Voted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($voters as $voter): ?>
                                <tr>
                                    <td><?= $voter['user_id'] ?></td>
                                    <td><?= htmlspecialchars($voter['full_name']) ?></td>
                                    <td><?= htmlspecialchars($voter['email']) ?></td>
                                    <td><?= $voter['has_voted'] ? 'Yes' : 'No' ?></td>
                                    <td>
                                        <button onclick="editVoter(<?= $voter['user_id'] ?>, '<?= htmlspecialchars($voter['full_name']) ?>', '<?= htmlspecialchars($voter['email']) ?>')">Edit</button>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this voter?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?= $voter['user_id'] ?>">
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
                                    <a href="?page=voters&p=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Voter Form -->
            <div id="voterForm" style="display:none; border:1px solid #000; padding:10px; margin:10px 0;">
                <h5 id="voterFormTitle">Add Voter</h5>
                <form method="post">
                    <input type="hidden" name="action" id="voter_action" value="add">
                    <input type="hidden" name="user_id" id="voter_id">
                    <div>
                        <label>Full Name</label>
                        <input type="text" name="full_name" id="voter_name" required>
                    </div>
                    <div>
                        <label>Email</label>
                        <input type="email" name="email" id="voter_email" required>
                    </div>
                    <div>
                        <button type="button" onclick="document.getElementById('voterForm').style.display='none'">Cancel</button>
                        <button type="submit">Save</button>
                    </div>
                </form>
            </div>

            <script>
            function showVoterForm(mode, id, name, email) {
                document.getElementById('voter_action').value = mode;
                document.getElementById('voter_id').value = id || '';
                document.getElementById('voter_name').value = name || '';
                document.getElementById('voter_email').value = email || '';
                document.getElementById('voterFormTitle').textContent = mode == 'add' ? 'Add Voter' : 'Edit Voter';
                document.getElementById('voterForm').style.display = 'block';
            }
            function editVoter(id, name, email) {
                showVoterForm('edit', id, name, email);
            }
            </script>
        <?php endif; ?>

        <?php
        // Vote Page
        if ($page == 'vote'):
            $settings = getSettings($pdo);
            $current_time = time();
            $voting_start = strtotime($settings['voting_start'] ?? '');
            $voting_end = strtotime($settings['voting_end'] ?? '');
            $voting_status = $settings['voting_status'] ?? 'inactive';
            
            $voters = $pdo->query("SELECT * FROM users WHERE role = 'voter' ORDER BY full_name")->fetchAll();
            
            $positions = $pdo->query("SELECT * FROM positions ORDER BY position_id")->fetchAll();
            foreach($positions as &$pos) {
                $stmt = $pdo->prepare("SELECT * FROM candidates WHERE position_id = ?");
                $stmt->execute([$pos['position_id']]);
                $pos['candidates'] = $stmt->fetchAll();
            }
        ?>
            <div>
                <h5>Cast Your Vote</h5>
                <form method="post">
                    <input type="hidden" name="page" value="vote">
                    <div>
                        <label>Select Voter</label>
                        <select name="voter_id" required>
                                <option value="">Select Voter</option>
                                <?php foreach($voters as $voter): ?>
                                    <option value="<?= $voter['user_id'] ?>" <?= $voter['has_voted'] ? 'disabled' : '' ?>>
                                        <?= htmlspecialchars($voter['full_name']) ?> (<?= htmlspecialchars($voter['email']) ?>) 
                                        <?= $voter['has_voted'] ? '(Already Voted)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <?php foreach($positions as $position): ?>
                            <?php if (count($position['candidates']) > 0): ?>
                                <div style="border:1px solid #000; padding:10px; margin:10px 0;">
                                    <strong><?= htmlspecialchars($position['position_name']) ?></strong>
                                    <small>(Select up to <?= $position['max_winners'] ?>)</small>
                                    <div>
                                        <?php if ($position['max_winners'] == 1): ?>
                                            <select name="votes[<?= $position['position_id'] ?>]" required>
                                                <option value="">Select Candidate</option>
                                                <?php foreach($position['candidates'] as $cand): ?>
                                                    <option value="<?= $cand['candidate_id'] ?>"><?= htmlspecialchars($cand['full_name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <?php foreach($position['candidates'] as $cand): ?>
                                                <div>
                                                    <input type="checkbox" 
                                                           name="votes[<?= $position['position_id'] ?>][]" 
                                                           value="<?= $cand['candidate_id'] ?>"
                                                           data-max="<?= $position['max_winners'] ?>"
                                                           onchange="checkMax(this, <?= $position['position_id'] ?>, <?= $position['max_winners'] ?>)">
                                                    <label><?= htmlspecialchars($cand['full_name']) ?></label>
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
            </div>

            <script>
            function checkMax(checkbox, positionId, max) {
                const checkboxes = document.querySelectorAll(`input[name="votes[${positionId}][]"]:checked`);
                if (checkboxes.length > max) {
                    checkbox.checked = false;
                    alert(`You can only select up to ${max} candidate(s) for this position.`);
                }
            }
            </script>
        <?php endif; ?>

        <?php
        // Results Page
        if ($page == 'results'):
            $per_page = 10;
            $current_page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
            $offset = ($current_page - 1) * $per_page;
            
            if ($search) {
                $stmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT c.candidate_id) 
                    FROM votes v 
                    JOIN candidates c ON v.candidate_id = c.candidate_id
                    JOIN positions p ON v.position_id = p.position_id
                    WHERE c.full_name LIKE ? OR p.position_name LIKE ?
                ");
                $stmt->execute(["%$search%", "%$search%"]);
                $total = $stmt->fetchColumn();
                $total_pages = ceil($total / $per_page);
                
                $results = $pdo->prepare("
                    SELECT c.full_name, p.position_name, COUNT(*) AS votes 
                    FROM votes v 
                    JOIN candidates c ON v.candidate_id = c.candidate_id
                    JOIN positions p ON v.position_id = p.position_id
                    WHERE c.full_name LIKE ? OR p.position_name LIKE ?
                    GROUP BY c.candidate_id 
                    ORDER BY p.position_name, votes DESC
                    LIMIT ? OFFSET ?
                ");
                $results->bindValue(1, "%$search%", PDO::PARAM_STR);
                $results->bindValue(2, "%$search%", PDO::PARAM_STR);
                $results->bindValue(3, $per_page, PDO::PARAM_INT);
                $results->bindValue(4, $offset, PDO::PARAM_INT);
                $results->execute();
                $results = $results->fetchAll();
            } else {
                $total = $pdo->query("SELECT COUNT(DISTINCT c.candidate_id) FROM votes v JOIN candidates c ON v.candidate_id = c.candidate_id")->fetchColumn();
                $total_pages = ceil($total / $per_page);
                
                $results = $pdo->prepare("
                    SELECT c.full_name, p.position_name, COUNT(*) AS votes 
                    FROM votes v 
                    JOIN candidates c ON v.candidate_id = c.candidate_id
                    JOIN positions p ON v.position_id = p.position_id
                    GROUP BY c.candidate_id 
                    ORDER BY p.position_name, votes DESC
                    LIMIT ? OFFSET ?
                ");
                $results->bindValue(1, $per_page, PDO::PARAM_INT);
                $results->bindValue(2, $offset, PDO::PARAM_INT);
                $results->execute();
                $results = $results->fetchAll();
            }
        ?>
            <div>
                <h5>Voting Results</h5>
                <table border="1">
                        <thead>
                            <tr>
                                <th>Candidate</th>
                                <th>Position</th>
                                <th>Total Votes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($results as $result): ?>
                                <tr>
                                    <td><?= htmlspecialchars($result['full_name']) ?></td>
                                    <td><?= htmlspecialchars($result['position_name']) ?></td>
                                    <td><?= $result['votes'] ?></td>
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
            </div>
        <?php endif; ?>

        <?php
        // Winners Page
        if ($page == 'winners'):
            $winners = $pdo->query("
                SELECT w.*, c.full_name as candidate_full_name, p.position_name as position_full_name
                FROM winners w
                JOIN candidates c ON w.candidate_id = c.candidate_id
                JOIN positions p ON w.position_id = p.position_id
                ORDER BY w.position_id, w.vote_count DESC
            ")->fetchAll();
        ?>
            <div>
                <h5>Winners 
                    <form method="post" style="display:inline;" onsubmit="return confirm('Calculate winners now?')">
                        <input type="hidden" name="page" value="calculate_winners">
                        <button type="submit">Calculate Winners</button>
                    </form>
                </h5>
                <div>
                    <?php if (count($winners) > 0): ?>
                        <?php
                        $current_position = '';
                        foreach($winners as $winner):
                            if ($current_position != $winner['position_name']):
                                if ($current_position != '') {
                                    echo '</tbody></table></div>';
                                }
                                $current_position = $winner['position_name'];
                                echo '<div style="margin:20px 0;">';
                                echo '<h6>' . htmlspecialchars($current_position) . '</h6>';
                                echo '<table border="1">';
                                echo '<thead><tr><th>Candidate</th><th>Votes</th></tr></thead>';
                                echo '<tbody>';
                            endif;
                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($winner['candidate_name']) ?></td>
                                            <td><?= $winner['vote_count'] ?></td>
                                        </tr>
                        <?php endforeach; 
                        if ($current_position != '') {
                            echo '</tbody></table></div>';
                        }
                        ?>
                    <?php else: ?>
                        <p>No winners calculated yet. Click "Calculate Winners" to compute results.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php
        // Settings Page
        if ($page == 'settings'):
            $settings = getSettings($pdo);
            $start_value = isset($settings['voting_start']) ? date('Y-m-d\TH:i', strtotime($settings['voting_start'])) : '';
            $end_value = isset($settings['voting_end']) ? date('Y-m-d\TH:i', strtotime($settings['voting_end'])) : '';
        ?>
            <div>
                <h5>Voting Settings</h5>
                <form method="post">
                    <input type="hidden" name="page" value="settings">
                    <div>
                        <label>Voting Start Time</label>
                        <input type="datetime-local" name="start" value="<?= $start_value ?>" required>
                    </div>
                    <div>
                        <label>Voting End Time</label>
                        <input type="datetime-local" name="end" value="<?= $end_value ?>" required>
                    </div>
                    <div>
                        <label>Voting Status</label>
                        <select name="status" required>
                            <option value="active" <?= ($settings['voting_status'] ?? '') == 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($settings['voting_status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <button type="submit">Save Settings</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>

