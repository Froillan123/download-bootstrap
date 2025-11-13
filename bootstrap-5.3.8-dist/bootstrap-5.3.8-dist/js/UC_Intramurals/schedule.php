<?php
require_once 'config/database.php';
session_start();

if($_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$pdo = $GLOBALS['pdo'];
$msg = '';

// CREATE
if(isset($_POST['create'])) {
    $day = $_POST['day'];
    $timeStart = $_POST['timeStart'];
    $timeEnd = $_POST['timeEnd'];
    $eventID = $_POST['eventID'];
    $venue = $_POST['venue'];
    $inCharge = $_POST['inCharge'];
    $sro_nocp = $_POST['sro_nocp'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO schedule (day, timeStart, timeEnd, eventID, venue, inCharge, sro_nocp) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$day, $timeStart, $timeEnd, $eventID, $venue, $inCharge, $sro_nocp]);
        $msg = "Schedule created!";
    } catch(PDOException $e) {
        $msg = "Error: " . $e->getMessage();
    }
}

// UPDATE
if(isset($_POST['update'])) {
    $scheduleID = $_POST['scheduleID'];
    $day = $_POST['day'];
    $timeStart = $_POST['timeStart'];
    $timeEnd = $_POST['timeEnd'];
    $eventID = $_POST['eventID'];
    $venue = $_POST['venue'];
    $inCharge = $_POST['inCharge'];
    $sro_nocp = $_POST['sro_nocp'];
    
    try {
        $stmt = $pdo->prepare("UPDATE schedule SET day=?, timeStart=?, timeEnd=?, eventID=?, venue=?, inCharge=?, sro_nocp=? WHERE scheduleID=?");
        $stmt->execute([$day, $timeStart, $timeEnd, $eventID, $venue, $inCharge, $sro_nocp, $scheduleID]);
        $msg = "Schedule updated!";
    } catch(PDOException $e) {
        $msg = "Error: " . $e->getMessage();
    }
}

// DELETE
if(isset($_GET['delete'])) {
    $scheduleID = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM schedule WHERE scheduleID = ?");
        $stmt->execute([$scheduleID]);
        $msg = "Schedule deleted!";
    } catch(PDOException $e) {
        $msg = "Error: " . $e->getMessage();
    }
}

// READ - Get schedules with JOIN to event
try {
    $schedules = $pdo->query("
        SELECT s.*, e.eventName 
        FROM schedule s 
        JOIN event e ON s.eventID = e.EventID
        ORDER BY s.day, s.timeStart
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $schedules = [];
    if(empty($msg)) {
        $msg = "Schedule table not created yet. Create it first.";
    }
}

$events = $pdo->query("SELECT * FROM event ORDER BY eventName")->fetchAll(PDO::FETCH_ASSOC);

// Get schedule for edit
$editSchedule = null;
if(isset($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM schedule WHERE scheduleID = ?");
        $stmt->execute([$_GET['edit']]);
        $editSchedule = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $msg = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Schedule Management</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="navbar-brand">UC Intramurals</a>
            <ul class="navbar-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="department.php">Departments</a></li>
                <li><a href="event.php">Events</a></li>
                <li><a href="report.php">Reports</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Schedule Management</h1>
            </div>

            <?php if($msg): ?>
                <div class="alert alert-<?php echo strpos($msg, 'Error') !== false ? 'danger' : 'success'; ?>">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-6">
                    <h2>Create Schedule</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Day *</label>
                            <input type="date" name="day" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Time Start *</label>
                            <input type="time" name="timeStart" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Time End *</label>
                            <input type="time" name="timeEnd" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Event *</label>
                            <select name="eventID" class="form-control" required>
                                <option value="">Select Event</option>
                                <?php foreach($events as $event): ?>
                                    <option value="<?php echo $event['EventID']; ?>"><?php echo htmlspecialchars($event['eventName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Venue *</label>
                            <input type="text" name="venue" class="form-control" placeholder="Venue" required>
                        </div>
                        <div class="form-group">
                            <label>In Charge *</label>
                            <input type="text" name="inCharge" class="form-control" placeholder="In Charge" required>
                        </div>
                        <div class="form-group">
                            <label>SRO/NOCP</label>
                            <input type="text" name="sro_nocp" class="form-control" placeholder="SRO/NOCP">
                        </div>
                        <button type="submit" name="create" class="btn btn-primary">Create</button>
                    </form>
                </div>

                <div class="col-6">
                    <?php if($editSchedule): ?>
                        <h2>Update Schedule</h2>
                        <form method="POST">
                            <input type="hidden" name="scheduleID" value="<?php echo $editSchedule['scheduleID']; ?>">
                            <div class="form-group">
                                <label>Day *</label>
                                <input type="date" name="day" class="form-control" value="<?php echo $editSchedule['day']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Time Start *</label>
                                <input type="time" name="timeStart" class="form-control" value="<?php echo $editSchedule['timeStart']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Time End *</label>
                                <input type="time" name="timeEnd" class="form-control" value="<?php echo $editSchedule['timeEnd']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Event *</label>
                                <select name="eventID" class="form-control" required>
                                    <?php foreach($events as $event): ?>
                                        <option value="<?php echo $event['EventID']; ?>" <?php echo $editSchedule['eventID'] == $event['EventID'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($event['eventName']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Venue *</label>
                                <input type="text" name="venue" class="form-control" value="<?php echo htmlspecialchars($editSchedule['venue']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>In Charge *</label>
                                <input type="text" name="inCharge" class="form-control" value="<?php echo htmlspecialchars($editSchedule['inCharge']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>SRO/NOCP</label>
                                <input type="text" name="sro_nocp" class="form-control" value="<?php echo htmlspecialchars($editSchedule['sro_nocp'] ?? ''); ?>">
                            </div>
                            <button type="submit" name="update" class="btn btn-success">Update</button>
                            <a href="schedule.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <h2>Schedule List</h2>
            <div class="table-container">
                <table>
                    <tr>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Event</th>
                        <th>Venue</th>
                        <th>In Charge</th>
                        <th>SRO/NOCP</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach($schedules as $sched): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sched['day']); ?></td>
                            <td><?php echo htmlspecialchars($sched['timeStart'] . ' - ' . $sched['timeEnd']); ?></td>
                            <td><?php echo htmlspecialchars($sched['eventName']); ?></td>
                            <td><?php echo htmlspecialchars($sched['venue']); ?></td>
                            <td><?php echo htmlspecialchars($sched['inCharge']); ?></td>
                            <td><?php echo htmlspecialchars($sched['sro_nocp'] ?? ''); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="?edit=<?php echo $sched['scheduleID']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <a href="?delete=<?php echo $sched['scheduleID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this schedule?')">Delete</a>
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
