<?php
require_once 'config/database.php';
session_start();

if($_SESSION['role'] != 'dean') {
    header("Location: index.php");
    exit;
}

$pdo = $GLOBALS['pdo'];
$userName = $_SESSION['user'];

// Get dean's department
$stmt = $pdo->prepare("SELECT deptID FROM dean WHERE userName = ?");
$stmt->execute([$userName]);
$dean = $stmt->fetch(PDO::FETCH_ASSOC);
$deptID = $dean['deptID'];

// Search
$search = $_GET['search'] ?? '';
$results = [];

if($search) {
    // Search athletes and coaches in dean's department with JOINs
    $sql = "
        SELECT 'athlete' as type, ap.IDnum, ap.firstname, ap.lastname, e.eventName, c.fname as coachFname, c.lname as coachLname
        FROM athlete_profile ap
        JOIN event e ON ap.eventID = e.EventID
        JOIN coach c ON ap.coachID = c.userName
        WHERE ap.deptID = ? AND (ap.firstname LIKE ? OR ap.lastname LIKE ? OR ap.IDnum LIKE ?)
        
        UNION
        
        SELECT 'coach' as type, c.userName as IDnum, c.fname as firstname, c.lname as lastname, '' as eventName, '' as coachFname, '' as coachLname
        FROM coach c
        WHERE c.deptID = ? AND (c.fname LIKE ? OR c.lname LIKE ? OR c.userName LIKE ?)
    ";
    $searchTerm = "%$search%";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$deptID, $searchTerm, $searchTerm, $searchTerm, $deptID, $searchTerm, $searchTerm, $searchTerm]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Report - All athletes and coaches in department
$reportSql = "
    SELECT 'athlete' as type, ap.IDnum, ap.firstname, ap.lastname, e.eventName, c.fname as coachFname, c.lname as coachLname
    FROM athlete_profile ap
    JOIN event e ON ap.eventID = e.EventID
    JOIN coach c ON ap.coachID = c.userName
    WHERE ap.deptID = ?
    
    UNION
    
    SELECT 'coach' as type, c.userName as IDnum, c.fname as firstname, c.lname as lastname, '' as eventName, '' as coachFname, '' as coachLname
    FROM coach c
    WHERE c.deptID = ?
";
$stmt = $pdo->prepare($reportSql);
$stmt->execute([$deptID, $deptID]);
$report = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Search & Report</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="navbar-brand">UC Intramurals</a>
            <ul class="navbar-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="athlete_approve.php">Approve Athletes</a></li>
                <li><a href="dean_profile.php">My Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Search & Report</h1>
            </div>
            
            <h2>Search</h2>
            <form method="GET">
                <div class="form-group">
                    <input type="text" name="search" class="form-control" placeholder="Search athlete or coach" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
            
            <?php if($search && $results): ?>
                <h3>Search Results</h3>
                <div class="table-container">
                    <table>
                        <tr>
                            <th>Type</th>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Event</th>
                            <th>Coach</th>
                        </tr>
                        <?php foreach($results as $result): ?>
                            <tr>
                                <td><span class="badge badge-info"><?php echo ucfirst(htmlspecialchars($result['type'])); ?></span></td>
                                <td><?php echo htmlspecialchars($result['IDnum']); ?></td>
                                <td><?php echo htmlspecialchars($result['firstname'] . ' ' . $result['lastname']); ?></td>
                                <td><?php echo htmlspecialchars($result['eventName']); ?></td>
                                <td><?php echo htmlspecialchars($result['coachFname'] . ' ' . $result['coachLname']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php elseif($search && empty($results)): ?>
                <div class="alert alert-info">No results found.</div>
            <?php endif; ?>
            
            <h2>Report - All Athletes and Coaches</h2>
            <div class="table-container">
                <table>
                    <tr>
                        <th>Type</th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Event</th>
                        <th>Coach</th>
                    </tr>
                    <?php foreach($report as $row): ?>
                        <tr>
                            <td><span class="badge badge-info"><?php echo ucfirst(htmlspecialchars($row['type'])); ?></span></td>
                            <td><?php echo htmlspecialchars($row['IDnum']); ?></td>
                            <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                            <td><?php echo htmlspecialchars($row['eventName']); ?></td>
                            <td><?php echo htmlspecialchars($row['coachFname'] . ' ' . $row['coachLname']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
