<?php
require_once 'config/database.php';
session_start();

$role = $_SESSION['role'] ?? '';
if(!in_array($role, ['admin', 'tournament_manager'])) {
    header("Location: index.php");
    exit;
}

$pdo = $GLOBALS['pdo'];
$msg = '';
$userName = $_SESSION['user'];

// CREATE
if(isset($_POST['create'])) {
    $category = $_POST['category'];
    $eventName = $_POST['eventName'];
    $noOfParticipants = $_POST['noOfParticipants'];
    // Admin can select tournament manager, TM uses their own username
    $tournamentManager = ($role == 'admin' && isset($_POST['tournamentmanager'])) ? $_POST['tournamentmanager'] : $userName;
    $stmt = $pdo->prepare("INSERT INTO event (category, eventName, noOfParticipants, tournamentmanager) VALUES (?, ?, ?, ?)");
    $stmt->execute([$category, $eventName, $noOfParticipants, $tournamentManager]);
    $msg = "Event created!";
}

// UPDATE
if(isset($_POST['update'])) {
    $eventID = $_POST['eventID'];
    $category = $_POST['category'];
    $eventName = $_POST['eventName'];
    $noOfParticipants = $_POST['noOfParticipants'];
    // Admin can change tournament manager, TM cannot
    if($role == 'admin' && isset($_POST['tournamentmanager'])) {
        $tournamentManager = $_POST['tournamentmanager'];
        $stmt = $pdo->prepare("UPDATE event SET category = ?, eventName = ?, noOfParticipants = ?, tournamentmanager = ? WHERE EventID = ?");
        $stmt->execute([$category, $eventName, $noOfParticipants, $tournamentManager, $eventID]);
    } else {
        $stmt = $pdo->prepare("UPDATE event SET category = ?, eventName = ?, noOfParticipants = ? WHERE EventID = ?");
        $stmt->execute([$category, $eventName, $noOfParticipants, $eventID]);
    }
    $msg = "Event updated!";
}

// DELETE
if(isset($_GET['delete'])) {
    $eventID = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM event WHERE EventID = ?");
    $stmt->execute([$eventID]);
    $msg = "Event deleted!";
}

// READ - Get events with JOIN to tournamentmanager
// Admin sees all events, Tournament Manager sees only their own
if($role == 'admin') {
    $events = $pdo->query("
        SELECT e.*, t.fname, t.lname 
        FROM event e 
        JOIN tournamentmanager t ON e.tournamentmanager = t.userName
        ORDER BY e.EventID
    ")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("
        SELECT e.*, t.fname, t.lname 
        FROM event e 
        JOIN tournamentmanager t ON e.tournamentmanager = t.userName
        WHERE e.tournamentmanager = ?
        ORDER BY e.EventID
    ");
    $stmt->execute([$userName]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get tournament managers for admin dropdown
$tournamentManagers = [];
if($role == 'admin') {
    $tournamentManagers = $pdo->query("SELECT userName, fname, lname FROM tournamentmanager ORDER BY lname, fname")->fetchAll(PDO::FETCH_ASSOC);
}

// Get event for edit
$editEvent = null;
if(isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM event WHERE EventID = ?");
    $stmt->execute([$_GET['edit']]);
    $editEvent = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Event Management</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="navbar-brand">UC Intramurals</a>
            <ul class="navbar-menu">
                <li><a href="index.php">Home</a></li>
                <?php if($role == 'admin'): ?>
                    <li><a href="department.php">Departments</a></li>
                    <li><a href="schedule.php">Schedule</a></li>
                    <li><a href="report.php">Reports</a></li>
                <?php else: ?>
                    <li><a href="tournament_manager_profile.php">My Profile</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Event Management</h1>
            </div>

            <?php if($msg): ?>
                <div class="alert alert-<?php echo strpos($msg, 'Error') !== false ? 'danger' : 'success'; ?>">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-6">
                    <h2>Create Event</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Category *</label>
                            <input type="text" name="category" class="form-control" placeholder="Category (Sports/Cultural/Academic)" required>
                        </div>
                        <div class="form-group">
                            <label>Event Name *</label>
                            <input type="text" name="eventName" class="form-control" placeholder="Event Name" required>
                        </div>
                        <div class="form-group">
                            <label>Number of Participants *</label>
                            <input type="number" name="noOfParticipants" class="form-control" placeholder="Number of Participants" required>
                        </div>
                        <?php if($role == 'admin'): ?>
                            <div class="form-group">
                                <label>Tournament Manager *</label>
                                <select name="tournamentmanager" class="form-control" required>
                                    <option value="">Select Tournament Manager</option>
                                    <?php foreach($tournamentManagers as $tm): ?>
                                        <option value="<?php echo htmlspecialchars($tm['userName']); ?>">
                                            <?php echo htmlspecialchars($tm['fname'] . ' ' . $tm['lname']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <button type="submit" name="create" class="btn btn-primary">Create</button>
                    </form>
                </div>

                <div class="col-6">
                    <?php if($editEvent): ?>
                        <h2>Update Event</h2>
                        <form method="POST">
                            <input type="hidden" name="eventID" value="<?php echo $editEvent['EventID']; ?>">
                            <div class="form-group">
                                <label>Category *</label>
                                <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($editEvent['category']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Event Name *</label>
                                <input type="text" name="eventName" class="form-control" value="<?php echo htmlspecialchars($editEvent['eventName']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Number of Participants *</label>
                                <input type="number" name="noOfParticipants" class="form-control" value="<?php echo htmlspecialchars($editEvent['noOfParticipants']); ?>" required>
                            </div>
                            <?php if($role == 'admin'): ?>
                                <div class="form-group">
                                    <label>Tournament Manager *</label>
                                    <select name="tournamentmanager" class="form-control" required>
                                        <?php foreach($tournamentManagers as $tm): ?>
                                            <option value="<?php echo htmlspecialchars($tm['userName']); ?>" <?php echo $editEvent['tournamentmanager'] == $tm['userName'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tm['fname'] . ' ' . $tm['lname']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <button type="submit" name="update" class="btn btn-success">Update</button>
                            <a href="event.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <h2>Event List</h2>
            <div class="table-container">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Category</th>
                        <th>Event Name</th>
                        <th>Participants</th>
                        <th>Tournament Manager</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach($events as $event): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event['EventID']); ?></td>
                            <td><?php echo htmlspecialchars($event['category']); ?></td>
                            <td><?php echo htmlspecialchars($event['eventName']); ?></td>
                            <td><?php echo htmlspecialchars($event['noOfParticipants']); ?></td>
                            <td><?php echo htmlspecialchars($event['fname'] . ' ' . $event['lname']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="?edit=<?php echo $event['EventID']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <a href="?delete=<?php echo $event['EventID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this event?')">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

