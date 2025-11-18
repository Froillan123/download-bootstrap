# Voting System - Code Structure & Migration Guide

Complete guide on how the code works and how to migrate it to other modules.

## 📋 Table of Contents

1. [Code Structure Overview](#code-structure-overview)
2. [Database Auto-Creation](#database-auto-creation)
3. [Routing System](#routing-system)
4. [CRUD Handler Pattern](#crud-handler-pattern)
5. [Session Management](#session-management)
6. [Form Handling & Redirects](#form-handling--redirects)
7. [Search Functionality](#search-functionality)
8. [Step-by-Step Migration Guide](#step-by-step-migration-guide)

---

## Code Structure Overview

The entire application is in **ONE FILE** (`index.php`) with this structure:

```
index.php
├── Migration Guide Comments (Lines 1-100)
├── Session & Config (Lines 102-149)
│   ├── session_start()
│   ├── Database Constants
│   └── Auto-DB Creation
├── Table Creation Function (Lines 151-202)
├── Helper Functions (Lines 204-278)
├── Routing System (Lines 280-318)
├── CRUD Handlers (Lines 320-680)
│   ├── handleLogin()
│   ├── handlePositionAction()
│   ├── handleCandidateAction()
│   ├── handleVoterAction()
│   ├── handleVoteSubmission()
│   └── calculateWinners()
└── HTML Output (Lines 682-1388)
    ├── Navigation
    ├── Search Bar
    ├── Messages
    └── Page Content (switch statement)
```

---

## Database Auto-Creation

### How It Works

**Location:** Lines 114-149

```php
// Step 1: Try to connect to existing database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    createElectionTables($pdo);
} catch(PDOException $e) {
    // Step 2: If database doesn't exist, create it
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        // Connect without database name
        $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        // Reconnect to new database
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        // Create tables
        createElectionTables($pdo);
    }
}
```

**Key Points:**
- Uses `try-catch` to detect missing database
- Checks error message for "Unknown database"
- Creates database if missing, then creates tables
- Uses `CREATE TABLE IF NOT EXISTS` so it's safe to run multiple times

**To Migrate:**
1. Change `DB_NAME` constant (Line 110)
2. Update `createElectionTables()` function name and table definitions (Line 152)

---

## Routing System

### How It Works

**Location:** Lines 280-318

```php
// Get current page from URL
$page = isset($_GET['page']) ? $_GET['page'] : 'login';
$action = isset($_POST['action']) ? $_POST['action'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle POST requests (form submissions)
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
        // ... more cases
    }
}
```

**Key Points:**
- `$page` = Current page from URL (`?page=positions`)
- `$action` = Form action (`add`, `edit`, `delete`, etc.)
- `$search` = Search query from URL
- POST requests go to handler functions
- GET requests display HTML pages

**To Migrate:**
1. Change page names in switch cases
2. Update handler function names
3. Update navigation links (Line 697-712)

---

## CRUD Handler Pattern

### Standard CRUD Handler Structure

**Location:** Lines 325-453 (example: `handlePositionAction`)

```php
function handlePositionAction($pdo, $action) {
    global $message, $messageType;
    
    if ($action == 'add') {
        // 1. Get form data
        $name = trim($_POST['posName'] ?? '');
        $numOfPositions = (int)($_POST['numOfPositions'] ?? 1);
        
        // 2. Validate
        if (empty($name)) {
            $message = "Position name is required.";
            $messageType = 'danger';
            return;
        }
        
        // 3. Execute SQL
        try {
            $stmt = $pdo->prepare("INSERT INTO positions (posName, numOfPositions, posStat) VALUES (?, ?, 'active')");
            $stmt->execute([$name, $numOfPositions]);
            $message = "Position added successfully!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    } 
    elseif ($action == 'edit') {
        // Similar pattern: Get data → Validate → Execute SQL
    }
    elseif ($action == 'delete') {
        // Similar pattern: Get ID → Execute DELETE SQL
    }
    elseif ($action == 'deactivate') {
        // Soft delete: UPDATE status = 'inactive'
    }
    elseif ($action == 'activate') {
        // Reactivate: UPDATE status = 'active'
    }
}
```

**Pattern Breakdown:**
1. **Get Data:** `$_POST['field_name'] ?? ''` (with null coalescing)
2. **Validate:** Check required fields
3. **Execute:** Use prepared statements (`$pdo->prepare()`)
4. **Set Message:** Success or error message
5. **Return:** Function ends, message displayed on page

**To Migrate:**
1. Change function name: `handlePositionAction` → `handleCategoryAction`
2. Change table name: `positions` → `categories`
3. Change column names: `posName` → `category_name`
4. Update SQL queries
5. Update form field names in HTML

---

## Session Management

### How Sessions Work

**Location:** Lines 102, 280-319, 306-308

```php
// Start session at top of file
session_start();

// Store data in session (login example)
$_SESSION['voterID'] = $voter['voterID'];
$_SESSION['voterName'] = getFullName($voter['voterFName'], $voter['voterMName'], $voter['voterLName']);
$_SESSION['voted'] = $voter['voted'];

// Check session (routing example)
if (isset($_SESSION['voterID'])) {
    // Show voter navigation
} else {
    // Show admin navigation
}

// Clear session (logout)
session_destroy();
```

**Key Points:**
- `session_start()` must be called before any output
- Store user data in `$_SESSION` array
- Check with `isset($_SESSION['key'])`
- Clear with `session_destroy()` or `unset($_SESSION['key'])`

**To Migrate:**
- Change session keys: `voterID` → `ownerID`, `voterName` → `ownerName`
- Update all `$_SESSION` references

---

## Form Handling & Redirects

### POST-Redirect-GET Pattern

**Location:** Lines 268-275 (redirect function), Lines 339-346 (example usage)

```php
// Redirect function
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
        $base = str_replace('/index.php', '', $base);
        $url = $protocol . '://' . $host . $base . '/' . ltrim($url, '/');
    }
    header("Location: $url", true, 303); // 303 See Other - forces GET request
    exit;
}

// Usage in handler
try {
    $stmt = $pdo->prepare("INSERT INTO positions (posName, numOfPositions, posStat) VALUES (?, ?, 'active')");
    $stmt->execute([$name, $numOfPositions]);
    $_SESSION['message'] = "Position added successfully!";
    $_SESSION['messageType'] = 'success';
} catch(PDOException $e) {
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['messageType'] = 'danger';
}
redirect("?page=positions"); // Redirect after POST
```

**Key Points:**
- Store messages in `$_SESSION` (not `$message` global)
- Always redirect after POST to prevent resubmission
- HTTP 303 status forces GET request
- Messages read from session and displayed on next page

**Why This Pattern?**
- Prevents form resubmission on refresh
- Clean URLs (GET requests)
- Messages persist across redirect

**To Migrate:**
- Keep redirect function as-is
- Update redirect URLs: `?page=positions` → `?page=categories`

---

## Search Functionality

### How Search Works

**Location:** Lines 280, 717-724, 823-829 (example)

```php
// Get search query from URL
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Search form in HTML
<form method="get" style="margin:10px 0;">
    <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">
    <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
    <?php if ($search): ?>
        <a href="?page=<?= htmlspecialchars($page) ?>">Clear</a>
    <?php endif; ?>
</form>

// Search in SQL query
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM positions WHERE posName LIKE ? ORDER BY posID");
    $stmt->execute(["%$search%"]);
    $positions = $stmt->fetchAll();
} else {
    $positions = $pdo->query("SELECT * FROM positions ORDER BY posID")->fetchAll();
}
```

**Key Points:**
- Search uses `LIKE` with `%search%` pattern
- Preserve search in pagination links: `&search=<?= urlencode($search) ?>`
- Use prepared statements to prevent SQL injection

**To Migrate:**
- Update column names in LIKE clause
- For multiple columns: `WHERE col1 LIKE ? OR col2 LIKE ?`

---

## Step-by-Step Migration Guide

### Example: Migrate to Pet Owner System

#### Step 1: Database Configuration (Lines 109-112)

**Before:**
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'election');
define('DB_USER', 'root');
define('DB_PASS', '');
```

**After:**
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'pet_owner');  // Changed
define('DB_USER', 'root');
define('DB_PASS', '');
```

---

#### Step 2: Table Structure (Lines 152-198)

**Before:**
```php
function createElectionTables($pdo) {
    $tables = [
        "CREATE TABLE IF NOT EXISTS positions (
            posID INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            posName VARCHAR(100) NOT NULL UNIQUE,
            numOfPositions INT(11) DEFAULT 1,
            posStat ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        // ... more tables
    ];
}
```

**After:**
```php
function createPetOwnerTables($pdo) {  // Changed function name
    $tables = [
        "CREATE TABLE IF NOT EXISTS categories (  // Changed table name
            categoryID INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,  // Changed column
            categoryName VARCHAR(100) NOT NULL UNIQUE,  // Changed column
            maxPets INT(11) DEFAULT 1,  // Changed column
            categoryStat ENUM('active', 'inactive') DEFAULT 'active',  // Changed column
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        // ... more tables
    ];
}
```

**Mapping:**
- `positions` → `categories`
- `posID` → `categoryID`
- `posName` → `categoryName`
- `numOfPositions` → `maxPets`
- `posStat` → `categoryStat`

---

#### Step 3: Handler Functions (Lines 325-453)

**Before:**
```php
function handlePositionAction($pdo, $action) {
    if ($action == 'add') {
        $name = trim($_POST['posName'] ?? '');
        $numOfPositions = (int)($_POST['numOfPositions'] ?? 1);
        $stmt = $pdo->prepare("INSERT INTO positions (posName, numOfPositions, posStat) VALUES (?, ?, 'active')");
        $stmt->execute([$name, $numOfPositions]);
    }
}
```

**After:**
```php
function handleCategoryAction($pdo, $action) {  // Changed function name
    if ($action == 'add') {
        $name = trim($_POST['categoryName'] ?? '');  // Changed field
        $maxPets = (int)($_POST['maxPets'] ?? 1);  // Changed field
        $stmt = $pdo->prepare("INSERT INTO categories (categoryName, maxPets, categoryStat) VALUES (?, ?, 'active')");  // Changed table & columns
        $stmt->execute([$name, $maxPets]);  // Changed variable
    }
}
```

**Pattern:**
1. Change function name
2. Change `$_POST` field names
3. Change SQL table and column names
4. Update variable names

---

#### Step 4: Routing (Lines 294-318)

**Before:**
```php
switch($page) {
    case 'positions':
        handlePositionAction($pdo, $action);
        break;
    case 'candidates':
        handleCandidateAction($pdo, $action);
        break;
}
```

**After:**
```php
switch($page) {
    case 'categories':  // Changed
        handleCategoryAction($pdo, $action);  // Changed
        break;
    case 'pets':  // Changed
        handlePetAction($pdo, $action);  // Changed
        break;
}
```

---

#### Step 5: HTML Pages (Lines 823-972)

**Before:**
```php
if ($page == 'positions'):
    if ($search) {
        $stmt = $pdo->prepare("SELECT * FROM positions WHERE posName LIKE ? ORDER BY posID");
        $stmt->execute(["%$search%"]);
        $positions = $stmt->fetchAll();
    } else {
        $positions = $pdo->query("SELECT * FROM positions ORDER BY posID")->fetchAll();
    }
?>
    <h5>Manage Positions</h5>
    <table>
        <tr>
            <th>ID</th>
            <th>Position Name</th>
            <th>Max Winners</th>
        </tr>
        <?php foreach($positions as $pos): ?>
            <tr>
                <td><?= $pos['posID'] ?></td>
                <td><?= htmlspecialchars($pos['posName']) ?></td>
                <td><?= $pos['numOfPositions'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
```

**After:**
```php
if ($page == 'categories'):  // Changed
    if ($search) {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE categoryName LIKE ? ORDER BY categoryID");  // Changed
        $stmt->execute(["%$search%"]);
        $categories = $stmt->fetchAll();  // Changed variable
    } else {
        $categories = $pdo->query("SELECT * FROM categories ORDER BY categoryID")->fetchAll();  // Changed
    }
?>
    <h5>Manage Categories</h5>  // Changed text
    <table>
        <tr>
            <th>ID</th>
            <th>Category Name</th>  // Changed
            <th>Max Pets</th>  // Changed
        </tr>
        <?php foreach($categories as $cat): ?>  // Changed variable
            <tr>
                <td><?= $cat['categoryID'] ?></td>  // Changed
                <td><?= htmlspecialchars($cat['categoryName']) ?></td>  // Changed
                <td><?= $cat['maxPets'] ?></td>  // Changed
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
```

**Pattern:**
1. Change page condition: `$page == 'positions'` → `$page == 'categories'`
2. Update SQL queries
3. Change variable names: `$positions` → `$categories`, `$pos` → `$cat`
4. Update column references: `$pos['posID']` → `$cat['categoryID']`
5. Update display text

---

#### Step 6: Navigation Links (Lines 697-712)

**Before:**
```php
<a href="?page=positions">Positions</a> |
<a href="?page=candidates">Candidates</a> |
<a href="?page=voters">Voters</a>
```

**After:**
```php
<a href="?page=categories">Categories</a> |  // Changed
<a href="?page=pets">Pets</a> |  // Changed
<a href="?page=owners">Owners</a>  // Changed
```

---

## Complete Migration Checklist

### Database Layer
- [ ] Change `DB_NAME` constant
- [ ] Rename `createElectionTables()` function
- [ ] Update all table names in CREATE TABLE statements
- [ ] Update all column names
- [ ] Update foreign key references

### PHP Functions
- [ ] Rename all handler functions
- [ ] Update SQL queries in handlers
- [ ] Update `$_POST` field names
- [ ] Update variable names
- [ ] Update helper functions (if any)

### Routing
- [ ] Update switch cases
- [ ] Update page names
- [ ] Update handler function calls

### HTML/UI
- [ ] Update navigation links
- [ ] Update page conditions (`if ($page == '...')`)
- [ ] Update form field names
- [ ] Update table column headers
- [ ] Update display text/labels
- [ ] Update variable names in loops
- [ ] Update column references in display

### Search
- [ ] Update LIKE clauses
- [ ] Update column names in search queries

### Forms
- [ ] Update input `name` attributes
- [ ] Update hidden input values
- [ ] Update form action URLs

---

## Common Patterns Reference

### Pattern 1: Add New Record

```php
if ($action == 'add') {
    // 1. Get data
    $field1 = trim($_POST['field1'] ?? '');
    $field2 = (int)($_POST['field2'] ?? 0);
    
    // 2. Validate
    if (empty($field1)) {
        $message = "Field1 is required.";
        return;
    }
    
    // 3. Insert
    $stmt = $pdo->prepare("INSERT INTO table_name (field1, field2) VALUES (?, ?)");
    $stmt->execute([$field1, $field2]);
    $message = "Record added successfully!";
}
```

### Pattern 2: Update Record

```php
if ($action == 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $field1 = trim($_POST['field1'] ?? '');
    
    $stmt = $pdo->prepare("UPDATE table_name SET field1 = ? WHERE id = ?");
    $stmt->execute([$field1, $id]);
    $message = "Record updated successfully!";
}
```

### Pattern 3: Delete Record

```php
if ($action == 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    
    $stmt = $pdo->prepare("DELETE FROM table_name WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Record deleted successfully!";
}
```

### Pattern 4: Soft Delete (Deactivate)

```php
if ($action == 'deactivate') {
    $id = (int)($_POST['id'] ?? 0);
    
    $stmt = $pdo->prepare("UPDATE table_name SET status = 'inactive' WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Record deactivated successfully!";
}
```

### Pattern 5: Display List with Search

```php
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM table_name WHERE field1 LIKE ? ORDER BY id");
    $stmt->execute(["%$search%"]);
    $items = $stmt->fetchAll();
} else {
    $items = $pdo->query("SELECT * FROM table_name ORDER BY id")->fetchAll();
}

foreach($items as $item):
    echo $item['field1'];
endforeach;
```

---

## Tips for Easy Migration

1. **Use Find & Replace:**
   - Find: `positions` → Replace: `categories`
   - Find: `posID` → Replace: `categoryID`
   - Find: `posName` → Replace: `categoryName`

2. **Work Section by Section:**
   - Start with database schema
   - Then handlers
   - Then HTML pages
   - Finally navigation

3. **Test After Each Section:**
   - Test database creation
   - Test CRUD operations
   - Test display pages

4. **Keep Backup:**
   - Copy original file before migration
   - Test in separate folder first

---

## Quick Reference: File Structure

```
Line 102-112:   Session & Database Config
Line 114-149:   Database Auto-Creation
Line 152-202:   Table Creation Function
Line 204-278:   Helper Functions
Line 280-318:   Routing System
Line 320-680:   CRUD Handlers
Line 682-1388:  HTML Output
```

---

## Need Help?

If you get stuck:
1. Check the error message
2. Verify table/column names match
3. Check SQL syntax
4. Verify form field names match `$_POST` keys
5. Check that all functions are called correctly

**Remember:** The structure stays the same, just rename everything! 🚀

