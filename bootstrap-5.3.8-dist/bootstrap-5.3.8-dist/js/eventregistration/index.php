<?php
/**
 * SKILLS TEST — Events Registration System
 * Single-file implementation with auto database creation
 * Includes PRG pattern, soft deletes, and hard deletes
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Manila');

define('DB_HOST', 'localhost');
define('DB_NAME', 'event_registration');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    createEventRegistrationTables($pdo);
} catch(PDOException $e) {
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        $root = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
        $root->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $root->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        createEventRegistrationTables($pdo);
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}

function createEventRegistrationTables($pdo) {
    $tables = [
        "CREATE TABLE IF NOT EXISTS participants (
            partID INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            partFName VARCHAR(100) NOT NULL,
            partLName VARCHAR(100) NOT NULL,
            partAddress TEXT DEFAULT NULL,
            partContact VARCHAR(50) DEFAULT NULL,
            partDRate DECIMAL(10,2) DEFAULT 0.00,
            partStatus ENUM('active','inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS events (
            evCode INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            evName VARCHAR(150) NOT NULL,
            evDate DATE NOT NULL,
            evVenue VARCHAR(150) DEFAULT NULL,
            evRFee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            evStatus ENUM('active','inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS registration (
            regCode INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            partID INT(11) NOT NULL,
            evCode INT(11) NOT NULL,
            regDate DATE NOT NULL,
            regRFee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            regPayMode ENUM('Cash','Card') NOT NULL DEFAULT 'Cash',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (partID) REFERENCES participants(partID) ON DELETE CASCADE,
            FOREIGN KEY (evCode) REFERENCES events(evCode) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    ];

    foreach($tables as $sql) {
        try {
            $pdo->exec($sql);
        } catch(PDOException $e) {
            // ignore if exists
        }
    }
}

function redirect($url) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    if (strpos($url, 'http') !== 0) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script = $_SERVER['SCRIPT_NAME'];
        $base = rtrim(dirname($script), '/\\');
        if ($base === '.' || $base === '/') {
            $base = '';
        }
        $url = $protocol . '://' . $host . $base . '/' . ltrim($url, '/');
    }
    header("Location: $url", true, 303);
    exit;
}

function getFullName($fname, $lname) {
    return trim($fname . ' ' . $lname);
}

$page = $_GET['page'] ?? 'dashboard';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$eventFilter = isset($_GET['event_filter']) ? (int)$_GET['event_filter'] : 0;

$message = $_SESSION['message'] ?? '';
$messageType = $_SESSION['messageType'] ?? '';
unset($_SESSION['message'], $_SESSION['messageType']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    switch($page) {
        case 'events':
            handleEventAction($pdo, $action);
            break;
        case 'participants':
            handleParticipantAction($pdo, $action);
            break;
        case 'registration':
            handleRegistrationAction($pdo, $action);
            break;
        case 'monitoring':
            handleMonitoringAction();
            break;
    }
}

function handleEventAction($pdo, $action) {
    if ($action === 'add') {
        $name = trim($_POST['evName'] ?? '');
        $date = $_POST['evDate'] ?? '';
        $venue = trim($_POST['evVenue'] ?? '');
        $fee = isset($_POST['evRFee']) ? (float)$_POST['evRFee'] : 0;

        if (empty($name) || empty($date)) {
            setMessage('Event name and date are required.', 'danger');
            redirect("?page=events");
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO events (evName, evDate, evVenue, evRFee, evStatus) VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([$name, $date, $venue, $fee]);
            setMessage('Event added successfully.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=events");
    } elseif ($action === 'edit') {
        $id = (int)($_POST['evCode'] ?? 0);
        $name = trim($_POST['evName'] ?? '');
        $date = $_POST['evDate'] ?? '';
        $venue = trim($_POST['evVenue'] ?? '');
        $fee = isset($_POST['evRFee']) ? (float)$_POST['evRFee'] : 0;

        try {
            $stmt = $pdo->prepare("UPDATE events SET evName = ?, evDate = ?, evVenue = ?, evRFee = ? WHERE evCode = ?");
            $stmt->execute([$name, $date, $venue, $fee, $id]);
            setMessage('Event updated successfully.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=events");
    } elseif ($action === 'delete') {
        $id = (int)($_POST['evCode'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM events WHERE evCode = ?");
            $stmt->execute([$id]);
            setMessage('Event deleted permanently.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=events");
    } elseif ($action === 'deactivate') {
        $id = (int)($_POST['evCode'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE events SET evStatus = 'inactive' WHERE evCode = ?");
            $stmt->execute([$id]);
            setMessage('Event deactivated.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=events");
    } elseif ($action === 'activate') {
        $id = (int)($_POST['evCode'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE events SET evStatus = 'active' WHERE evCode = ?");
            $stmt->execute([$id]);
            setMessage('Event activated.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=events");
    }
}

function handleParticipantAction($pdo, $action) {
    if ($action === 'add') {
        $fname = trim($_POST['partFName'] ?? '');
        $lname = trim($_POST['partLName'] ?? '');
        $address = trim($_POST['partAddress'] ?? '');
        $contact = trim($_POST['partContact'] ?? '');
        $discount = isset($_POST['partDRate']) ? (float)$_POST['partDRate'] : 0;

        if (empty($fname) || empty($lname)) {
            setMessage('First name and last name are required.', 'danger');
            redirect("?page=participants");
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO participants (partFName, partLName, partAddress, partContact, partDRate, partStatus) VALUES (?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$fname, $lname, $address, $contact, $discount]);
            setMessage('Participant added successfully.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=participants");
    } elseif ($action === 'edit') {
        $id = (int)($_POST['partID'] ?? 0);
        $fname = trim($_POST['partFName'] ?? '');
        $lname = trim($_POST['partLName'] ?? '');
        $address = trim($_POST['partAddress'] ?? '');
        $contact = trim($_POST['partContact'] ?? '');
        $discount = isset($_POST['partDRate']) ? (float)$_POST['partDRate'] : 0;

        try {
            $stmt = $pdo->prepare("UPDATE participants SET partFName = ?, partLName = ?, partAddress = ?, partContact = ?, partDRate = ? WHERE partID = ?");
            $stmt->execute([$fname, $lname, $address, $contact, $discount, $id]);
            setMessage('Participant updated successfully.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=participants");
    } elseif ($action === 'delete') {
        $id = (int)($_POST['partID'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM participants WHERE partID = ?");
            $stmt->execute([$id]);
            setMessage('Participant deleted permanently.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=participants");
    } elseif ($action === 'deactivate') {
        $id = (int)($_POST['partID'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE participants SET partStatus = 'inactive' WHERE partID = ?");
            $stmt->execute([$id]);
            setMessage('Participant deactivated.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=participants");
    } elseif ($action === 'activate') {
        $id = (int)($_POST['partID'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE participants SET partStatus = 'active' WHERE partID = ?");
            $stmt->execute([$id]);
            setMessage('Participant activated.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=participants");
    }
}

function handleRegistrationAction($pdo, $action) {
    if ($action === 'add') {
        $partID = (int)($_POST['partID'] ?? 0);
        $evCode = (int)($_POST['evCode'] ?? 0);
        $regDate = $_POST['regDate'] ?? date('Y-m-d');
        $payMode = $_POST['regPayMode'] ?? 'Cash';

        if (!$partID || !$evCode) {
            setMessage('Participant and Event are required.', 'danger');
            redirect("?page=registration");
        }

        try {
            $participant = fetchParticipant($pdo, $partID);
            $event = fetchEvent($pdo, $evCode);

            if (!$participant || !$event) {
                setMessage('Invalid participant or event.', 'danger');
                redirect("?page=registration");
            }

            $regFee = max($event['evRFee'] - $participant['partDRate'], 0);
            $stmt = $pdo->prepare("INSERT INTO registration (partID, evCode, regDate, regRFee, regPayMode) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$partID, $evCode, $regDate, $regFee, $payMode]);
            setMessage('Participant registered successfully.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=registration");
    } elseif ($action === 'delete') {
        $id = (int)($_POST['regCode'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM registration WHERE regCode = ?");
            $stmt->execute([$id]);
            setMessage('Registration deleted permanently.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=registration");
    }
}

function handleMonitoringAction() {
    redirect("?page=monitoring");
}

function fetchParticipant($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM participants WHERE partID = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function fetchEvent($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE evCode = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function setMessage($text, $type = 'success') {
    $_SESSION['message'] = $text;
    $_SESSION['messageType'] = $type;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Registration System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f4f4f4; }
        header { background: #0a2a43; color: #fff; padding: 15px; }
        nav a { color: #fff; margin-right: 15px; text-decoration: none; }
        .container { padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; background: #fff; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #e5f1fb; }
        button { padding: 6px 12px; margin: 2px; }
        form.inline { display: inline; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d1e7dd; color: #0f5132; }
        .danger { background: #f8d7da; color: #842029; }
        .card-grid { display: flex; gap: 10px; flex-wrap: wrap; }
        .card { background: #fff; padding: 15px; flex: 1 1 200px; border: 1px solid #ccc; border-radius: 4px; }
        .search-bar { margin: 10px 0; }
        .module-header { display:flex; justify-content:space-between; align-items:center; }
    </style>
</head>
<body>
    <header>
        <h2>Events Registration System</h2>
        <nav>
            <a href="?page=dashboard">Dashboard</a>
            <a href="?page=events">Events</a>
            <a href="?page=participants">Participants</a>
            <a href="?page=registration">Registration</a>
            <a href="?page=monitoring">Monitoring</a>
        </nav>
    </header>
    <div class="container">
        <?php if ($message): ?>
            <div class="message <?= $messageType === 'success' ? 'success' : 'danger' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($page === 'dashboard'): ?>
            <?php
                $stats = [
                    'events' => $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn(),
                    'participants' => $pdo->query("SELECT COUNT(*) FROM participants")->fetchColumn(),
                    'registrations' => $pdo->query("SELECT COUNT(*) FROM registration")->fetchColumn()
                ];
            ?>
            <div class="card-grid">
                <div class="card">
                    <strong>Total Events</strong>
                    <h2><?= $stats['events'] ?></h2>
                </div>
                <div class="card">
                    <strong>Total Participants</strong>
                    <h2><?= $stats['participants'] ?></h2>
                </div>
                <div class="card">
                    <strong>Total Registrations</strong>
                    <h2><?= $stats['registrations'] ?></h2>
                </div>
            </div>

        <?php elseif ($page === 'events'): ?>
            <?php
                if ($search) {
                    $stmt = $pdo->prepare("SELECT * FROM events WHERE evName LIKE ? OR evVenue LIKE ? ORDER BY evDate DESC");
                    $stmt->execute(["%$search%", "%$search%"]);
                    $events = $stmt->fetchAll();
                } else {
                    $events = $pdo->query("SELECT * FROM events ORDER BY evDate DESC")->fetchAll();
                }
            ?>
            <div class="module-header">
                <h3>Events Management</h3>
                <button onclick="showEventForm('add')">Add Event</button>
            </div>
            <form method="get" class="search-bar">
                <input type="hidden" name="page" value="events">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search events...">
                <button type="submit">Search</button>
                <?php if ($search): ?><a href="?page=events">Clear</a><?php endif; ?>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Venue</th>
                        <th>Fee</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($events as $event): ?>
                        <tr>
                            <td><?= $event['evCode'] ?></td>
                            <td><?= htmlspecialchars($event['evName']) ?></td>
                            <td><?= htmlspecialchars($event['evDate']) ?></td>
                            <td><?= htmlspecialchars($event['evVenue'] ?? '') ?></td>
                            <td><?= number_format($event['evRFee'], 2) ?></td>
                            <td><?= $event['evStatus'] ?></td>
                            <td>
                                <button onclick="editEvent(<?= $event['evCode'] ?>, '<?= htmlspecialchars($event['evName'], ENT_QUOTES) ?>', '<?= $event['evDate'] ?>', '<?= htmlspecialchars($event['evVenue'] ?? '', ENT_QUOTES) ?>', <?= $event['evRFee'] ?>)">Edit</button>
                                <?php if ($event['evStatus'] === 'active'): ?>
                                    <form method="post" class="inline" onsubmit="return confirm('Deactivate this event?')">
                                        <input type="hidden" name="action" value="deactivate">
                                        <input type="hidden" name="evCode" value="<?= $event['evCode'] ?>">
                                        <button type="submit">Deactivate</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" class="inline">
                                        <input type="hidden" name="action" value="activate">
                                        <input type="hidden" name="evCode" value="<?= $event['evCode'] ?>">
                                        <button type="submit">Activate</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" class="inline" onsubmit="return confirm('Hard delete this event?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="evCode" value="<?= $event['evCode'] ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div id="eventForm" style="display:none; background:#fff; padding:15px; border:1px solid #ccc;">
                <h4 id="eventFormTitle">Add Event</h4>
                <form method="post">
                    <input type="hidden" name="action" id="event_action" value="add">
                    <input type="hidden" name="evCode" id="evCode">
                    <div>
                        <label>Name</label>
                        <input type="text" name="evName" id="evName" required>
                    </div>
                    <div>
                        <label>Date</label>
                        <input type="date" name="evDate" id="evDate" required>
                    </div>
                    <div>
                        <label>Venue</label>
                        <input type="text" name="evVenue" id="evVenue">
                    </div>
                    <div>
                        <label>Registration Fee</label>
                        <input type="number" step="0.01" name="evRFee" id="evRFee" required>
                    </div>
                    <div>
                        <button type="button" onclick="hideEventForm()">Cancel</button>
                        <button type="submit">Save</button>
                    </div>
                </form>
            </div>

        <?php elseif ($page === 'participants'): ?>
            <?php
                if ($search) {
                    $stmt = $pdo->prepare("SELECT * FROM participants WHERE partFName LIKE ? OR partLName LIKE ? OR partContact LIKE ? ORDER BY partLName");
                    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
                    $participants = $stmt->fetchAll();
                } else {
                    $participants = $pdo->query("SELECT * FROM participants ORDER BY partLName")->fetchAll();
                }
            ?>
            <div class="module-header">
                <h3>Participants Management</h3>
                <button onclick="showParticipantForm('add')">Add Participant</button>
            </div>
            <form method="get" class="search-bar">
                <input type="hidden" name="page" value="participants">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search participants...">
                <button type="submit">Search</button>
                <?php if ($search): ?><a href="?page=participants">Clear</a><?php endif; ?>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Discount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($participants as $p): ?>
                        <tr>
                            <td><?= $p['partID'] ?></td>
                            <td><?= htmlspecialchars(getFullName($p['partFName'], $p['partLName'])) ?></td>
                            <td><?= htmlspecialchars($p['partContact'] ?? '') ?></td>
                            <td><?= htmlspecialchars($p['partAddress'] ?? '') ?></td>
                            <td><?= number_format($p['partDRate'], 2) ?></td>
                            <td><?= $p['partStatus'] ?></td>
                            <td>
                                <button onclick="editParticipant(<?= $p['partID'] ?>, '<?= htmlspecialchars($p['partFName'], ENT_QUOTES) ?>', '<?= htmlspecialchars($p['partLName'], ENT_QUOTES) ?>', '<?= htmlspecialchars($p['partAddress'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($p['partContact'] ?? '', ENT_QUOTES) ?>', <?= $p['partDRate'] ?>)">Edit</button>
                                <?php if ($p['partStatus'] === 'active'): ?>
                                    <form method="post" class="inline" onsubmit="return confirm('Deactivate participant?')">
                                        <input type="hidden" name="action" value="deactivate">
                                        <input type="hidden" name="partID" value="<?= $p['partID'] ?>">
                                        <button type="submit">Deactivate</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" class="inline">
                                        <input type="hidden" name="action" value="activate">
                                        <input type="hidden" name="partID" value="<?= $p['partID'] ?>">
                                        <button type="submit">Activate</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" class="inline" onsubmit="return confirm('Hard delete participant?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="partID" value="<?= $p['partID'] ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div id="participantForm" style="display:none; background:#fff; padding:15px; border:1px solid #ccc;">
                <h4 id="participantFormTitle">Add Participant</h4>
                <form method="post">
                    <input type="hidden" name="action" id="participant_action" value="add">
                    <input type="hidden" name="partID" id="partID">
                    <div>
                        <label>First Name</label>
                        <input type="text" name="partFName" id="partFName" required>
                    </div>
                    <div>
                        <label>Last Name</label>
                        <input type="text" name="partLName" id="partLName" required>
                    </div>
                    <div>
                        <label>Address</label>
                        <input type="text" name="partAddress" id="partAddress">
                    </div>
                    <div>
                        <label>Contact</label>
                        <input type="text" name="partContact" id="partContact">
                    </div>
                    <div>
                        <label>Discount Rate</label>
                        <input type="number" step="0.01" name="partDRate" id="partDRate" value="0">
                    </div>
                    <div>
                        <button type="button" onclick="hideParticipantForm()">Cancel</button>
                        <button type="submit">Save</button>
                    </div>
                </form>
            </div>

        <?php elseif ($page === 'registration'): ?>
            <?php
                $participantsActive = $pdo->query("SELECT * FROM participants WHERE partStatus = 'active' ORDER BY partLName")->fetchAll();
                $eventsActive = $pdo->query("SELECT * FROM events WHERE evStatus = 'active' ORDER BY evDate DESC")->fetchAll();
                $registrations = $pdo->query("
                    SELECT r.*, p.partFName, p.partLName, e.evName, e.evDate, e.evRFee
                    FROM registration r
                    LEFT JOIN participants p ON r.partID = p.partID
                    LEFT JOIN events e ON r.evCode = e.evCode
                    ORDER BY r.regDate DESC, r.created_at DESC
                ")->fetchAll();
            ?>
            <div class="module-header">
                <h3>Participant Registration</h3>
            </div>
            <div style="background:#fff; padding:15px; border:1px solid #ccc;">
                <form method="post">
                    <input type="hidden" name="action" value="add">
                    <div>
                        <label>Participant</label>
                        <select name="partID" required>
                            <option value="">Select Participant</option>
                            <?php foreach($participantsActive as $p): ?>
                                <option value="<?= $p['partID'] ?>"><?= htmlspecialchars(getFullName($p['partFName'], $p['partLName'])) ?> (Disc: <?= number_format($p['partDRate'],2) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Event</label>
                        <select name="evCode" required>
                            <option value="">Select Event</option>
                            <?php foreach($eventsActive as $event): ?>
                                <option value="<?= $event['evCode'] ?>"><?= htmlspecialchars($event['evName']) ?> (Fee: <?= number_format($event['evRFee'],2) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Registration Date</label>
                        <input type="date" name="regDate" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div>
                        <label>Payment Mode</label>
                        <select name="regPayMode">
                            <option value="Cash">Cash</option>
                            <option value="Card">Card</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit">Register</button>
                    </div>
                </form>
            </div>
            <h4>Registration Records</h4>
            <table>
                <thead>
                    <tr>
                        <th>Reg Code</th>
                        <th>Event</th>
                        <th>Participant</th>
                        <th>Reg Date</th>
                        <th>Fee Paid</th>
                        <th>Payment Mode</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($registrations as $reg): ?>
                        <tr>
                            <td><?= $reg['regCode'] ?></td>
                            <td><?= htmlspecialchars($reg['evName'] ?? '') ?></td>
                            <td><?= htmlspecialchars(getFullName($reg['partFName'] ?? '', $reg['partLName'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($reg['regDate']) ?></td>
                            <td><?= number_format($reg['regRFee'], 2) ?></td>
                            <td><?= $reg['regPayMode'] ?></td>
                            <td>
                                <form method="post" class="inline" onsubmit="return confirm('Hard delete this registration?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="regCode" value="<?= $reg['regCode'] ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($page === 'monitoring'): ?>
            <?php
                $eventsList = $pdo->query("SELECT evCode, evName FROM events ORDER BY evName")->fetchAll();
                $monitorQuery = "
                    SELECT r.*, e.evName, e.evRFee, p.partFName, p.partLName
                    FROM registration r
                    LEFT JOIN events e ON r.evCode = e.evCode
                    LEFT JOIN participants p ON r.partID = p.partID
                ";
                $params = [];
                if ($eventFilter) {
                    $monitorQuery .= " WHERE r.evCode = ? ";
                    $params[] = $eventFilter;
                }
                $monitorQuery .= " ORDER BY r.regDate DESC, r.created_at DESC";
                $stmt = $pdo->prepare($monitorQuery);
                $stmt->execute($params);
                $records = $stmt->fetchAll();

                $count = count($records);
                $totalPaid = array_sum(array_column($records, 'regRFee'));
                if ($eventFilter && $count > 0) {
                    $eventBaseFee = $records[0]['evRFee'];
                    $totalDiscount = ($count * $eventBaseFee) - $totalPaid;
                } else {
                    $totalDiscount = 0;
                    foreach($records as $rec) {
                        $totalDiscount += ($rec['evRFee'] - $rec['regRFee']);
                    }
                }
            ?>
            <div class="module-header">
                <h3>Events Registration Monitoring</h3>
            </div>
            <form method="get" class="search-bar">
                <input type="hidden" name="page" value="monitoring">
                <label>Filter by Event:</label>
                <select name="event_filter" onchange="this.form.submit()">
                    <option value="0">All Events</option>
                    <?php foreach($eventsList as $e): ?>
                        <option value="<?= $e['evCode'] ?>" <?= $eventFilter == $e['evCode'] ? 'selected' : '' ?>><?= htmlspecialchars($e['evName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Participant Name</th>
                        <th>Registration Date</th>
                        <th>Registration Fee Paid</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($records as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['evName'] ?? '') ?></td>
                            <td><?= htmlspecialchars(getFullName($row['partFName'] ?? '', $row['partLName'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($row['regDate']) ?></td>
                            <td><?= number_format($row['regRFee'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="card-grid">
                <div class="card">
                    <strong>count()</strong>
                    <h3><?= $count ?></h3>
                </div>
                <div class="card">
                    <strong>sum(regRFee)</strong>
                    <h3><?= number_format($totalPaid, 2) ?></h3>
                </div>
                <div class="card">
                    <strong>a*evRFee − b (Total Discounts)</strong>
                    <p>a = <?= $count ?>, b = <?= number_format($totalPaid, 2) ?></p>
                    <h3><?= number_format(max($totalDiscount, 0), 2) ?></h3>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function showEventForm(mode) {
            document.getElementById('event_action').value = mode;
            document.getElementById('eventFormTitle').textContent = mode === 'add' ? 'Add Event' : 'Edit Event';
            document.getElementById('eventForm').style.display = 'block';
        }
        function hideEventForm() {
            document.getElementById('eventForm').style.display = 'hidden';
            document.getElementById('eventForm').style.display = 'none';
        }
        function editEvent(id, name, date, venue, fee) {
            showEventForm('edit');
            document.getElementById('evCode').value = id;
            document.getElementById('evName').value = name;
            document.getElementById('evDate').value = date;
            document.getElementById('evVenue').value = venue;
            document.getElementById('evRFee').value = fee;
        }

        function showParticipantForm(mode) {
            document.getElementById('participant_action').value = mode;
            document.getElementById('participantFormTitle').textContent = mode === 'add' ? 'Add Participant' : 'Edit Participant';
            document.getElementById('participantForm').style.display = 'block';
        }
        function hideParticipantForm() {
            document.getElementById('participantForm').style.display = 'none';
        }
        function editParticipant(id, fname, lname, address, contact, discount) {
            showParticipantForm('edit');
            document.getElementById('partID').value = id;
            document.getElementById('partFName').value = fname;
            document.getElementById('partLName').value = lname;
            document.getElementById('partAddress').value = address;
            document.getElementById('partContact').value = contact;
            document.getElementById('partDRate').value = discount;
        }
    </script>
</body>
</html>
<?php
ob_end_flush();
?>

