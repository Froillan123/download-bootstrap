<?php
require_once 'config/database.php';
session_start();

if($_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$pdo = $GLOBALS['pdo'];

// Report: Total participants per event per college with overall totals
// Using JOINs to get event, department, and count athletes
$report = $pdo->query("
    SELECT 
        e.eventName,
        d.deptName,
        COUNT(ap.IDnum) as participantCount
    FROM event e
    LEFT JOIN athlete_profile ap ON e.EventID = ap.eventID
    LEFT JOIN department d ON ap.deptID = d.deptId
    GROUP BY e.EventID, e.eventName, d.deptId, d.deptName
    ORDER BY e.eventName, d.deptName
")->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$eventTotals = [];
$deptTotals = [];
$grandTotal = 0;

foreach($report as $row) {
    $eventName = $row['eventName'];
    $deptName = $row['deptName'] ?? 'No Department';
    $count = $row['participantCount'];
    
    $eventTotals[$eventName] = ($eventTotals[$eventName] ?? 0) + $count;
    $deptTotals[$deptName] = ($deptTotals[$deptName] ?? 0) + $count;
    $grandTotal += $count;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Report</title>
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
                <li><a href="schedule.php">Schedule</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Admin Report - Participants per Event per College</h1>
            </div>
            
            <h2>Participants by Event and Department</h2>
            <div class="table-container">
                <table>
                    <tr>
                        <th>Event</th>
                        <th>College/Department</th>
                        <th>Participants</th>
                    </tr>
                    <?php 
                    $currentEvent = '';
                    foreach($report as $row): 
                        $eventName = $row['eventName'];
                        $deptName = $row['deptName'] ?? 'No Department';
                        $count = $row['participantCount'];
                        
                        if($currentEvent != $eventName && $currentEvent != ''):
                    ?>
                        <tr style="background-color: #f0f0f0;">
                            <td><strong>Total for <?php echo htmlspecialchars($currentEvent); ?></strong></td>
                            <td></td>
                            <td><strong><?php echo $eventTotals[$currentEvent]; ?></strong></td>
                        </tr>
                    <?php 
                        endif;
                        $currentEvent = $eventName;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($eventName); ?></td>
                            <td><?php echo htmlspecialchars($deptName); ?></td>
                            <td><?php echo $count; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if($currentEvent != ''): ?>
                        <tr style="background-color: #f0f0f0;">
                            <td><strong>Total for <?php echo htmlspecialchars($currentEvent); ?></strong></td>
                            <td></td>
                            <td><strong><?php echo $eventTotals[$currentEvent]; ?></strong></td>
                        </tr>
                    <?php endif; ?>
                    <tr style="background-color: #e0e0e0;">
                        <td><strong>GRAND TOTAL</strong></td>
                        <td></td>
                        <td><strong><?php echo $grandTotal; ?></strong></td>
                    </tr>
                </table>
            </div>
            
            <h2>Summary by Department</h2>
            <div class="table-container">
                <table>
                    <tr>
                        <th>Department</th>
                        <th>Total Participants</th>
                    </tr>
                    <?php foreach($deptTotals as $dept => $total): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($dept); ?></td>
                            <td><strong><?php echo $total; ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
