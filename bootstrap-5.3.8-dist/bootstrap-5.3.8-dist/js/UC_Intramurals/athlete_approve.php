<?php
require_once 'config/database.php';
session_start();

$role = $_SESSION['role'] ?? '';
if(!in_array($role, ['admin', 'coach', 'dean'])) {
    header("Location: index.php");
    exit;
}

$pdo = $GLOBALS['pdo'];
$msg = '';
$userName = $_SESSION['user'];

// Approve/Disapprove
if(isset($_POST['approve'])) {
    $IDnum = $_POST['IDnum'];
    $action = $_POST['action']; // 'approve' or 'disapprove'
    $timestamp = date('Y-m-d H:i:s');
    $approvalStatus = $action == 'approve' ? 'approved' : 'disapproved';
    
    try {
        // Determine which approval column to update based on role
        $approvalColumn = $role . '_approved';
        $timestampColumn = $role . '_approved_at';
        
        // Update athlete_profile approval status
        $stmt = $pdo->prepare("UPDATE athlete_profile SET $approvalColumn = ?, $timestampColumn = ? WHERE IDnum = ?");
        $stmt->execute([$approvalStatus, $timestamp, $IDnum]);
        
        // Insert into approval_log
        $stmt = $pdo->prepare("INSERT INTO approval_log (athleteID, approver_role, approver_username, action, timestamp) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$IDnum, $role, $userName, $approvalStatus, $timestamp]);
        
        $msg = "Athlete " . htmlspecialchars($IDnum) . " has been " . $approvalStatus . " successfully!";
    } catch(PDOException $e) {
        $msg = "Error: " . $e->getMessage();
    }
}

// READ - Get athletes with JOINs
// Coach sees only their athletes, Dean sees only their department, Admin sees all
$sql = "
    SELECT ap.*, e.eventName, d.deptName, c.fname as coachFname, c.lname as coachLname,
           de.fname as deanFname, de.lname as deanLname, r.userName
    FROM athlete_profile ap
    JOIN event e ON ap.eventID = e.EventID
    JOIN department d ON ap.deptID = d.deptId
    JOIN coach c ON ap.coachID = c.userName
    JOIN dean de ON ap.deanID = de.userName
    JOIN registrations r ON ap.IDnum = r.userName
";

if($role == 'coach') {
    $sql .= " WHERE ap.coachID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userName]);
} elseif($role == 'dean') {
    // Get dean's department first
    $stmt = $pdo->prepare("SELECT deptID FROM dean WHERE userName = ?");
    $stmt->execute([$userName]);
    $dean = $stmt->fetch(PDO::FETCH_ASSOC);
    $sql .= " WHERE ap.deptID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dean['deptID']]);
} else {
    $stmt = $pdo->query($sql);
}

$athletes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get approval log for an athlete
function getApprovalLog($pdo, $athleteID) {
    $stmt = $pdo->prepare("SELECT * FROM approval_log WHERE athleteID = ? ORDER BY timestamp DESC");
    $stmt->execute([$athleteID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get approval status badge class
function getStatusBadge($status) {
    if($status == 'approved') return 'badge-success';
    if($status == 'disapproved') return 'badge-danger';
    return 'badge-warning';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Approve Athletes</title>
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
                    <li><a href="event.php">Events</a></li>
                    <li><a href="schedule.php">Schedule</a></li>
                    <li><a href="report.php">Reports</a></li>
                <?php elseif($role == 'coach'): ?>
                    <li><a href="coach_profile.php">My Profile</a></li>
                <?php elseif($role == 'dean'): ?>
                    <li><a href="dean_profile.php">My Profile</a></li>
                    <li><a href="search.php">Search & Report</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Approve Athletes (<?php echo ucfirst($role); ?>)</h1>
            </div>

            <?php if($msg): ?>
                <div class="alert alert-<?php echo strpos($msg, 'Error') !== false ? 'danger' : 'success'; ?>">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Event</th>
                        <th>Department</th>
                        <th>Coach</th>
                        <th>Dean</th>
                        <th>Coach Status</th>
                        <th>Dean Status</th>
                        <th>Admin Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach($athletes as $athlete): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($athlete['IDnum']); ?></td>
                            <td><?php echo htmlspecialchars($athlete['firstname'] . ' ' . $athlete['lastname']); ?></td>
                            <td><?php echo htmlspecialchars($athlete['eventName']); ?></td>
                            <td><?php echo htmlspecialchars($athlete['deptName']); ?></td>
                            <td><?php echo htmlspecialchars($athlete['coachFname'] . ' ' . $athlete['coachLname']); ?></td>
                            <td><?php echo htmlspecialchars($athlete['deanFname'] . ' ' . $athlete['deanLname']); ?></td>
                            <td>
                                <?php 
                                $coachStatus = $athlete['coach_approved'] ?? 'pending';
                                echo '<span class="badge ' . getStatusBadge($coachStatus) . '">' . ucfirst($coachStatus) . '</span>';
                                ?>
                            </td>
                            <td>
                                <?php 
                                $deanStatus = $athlete['dean_approved'] ?? 'pending';
                                echo '<span class="badge ' . getStatusBadge($deanStatus) . '">' . ucfirst($deanStatus) . '</span>';
                                ?>
                            </td>
                            <td>
                                <?php 
                                $adminStatus = $athlete['admin_approved'] ?? 'pending';
                                echo '<span class="badge ' . getStatusBadge($adminStatus) . '">' . ucfirst($adminStatus) . '</span>';
                                ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="IDnum" value="<?php echo htmlspecialchars($athlete['IDnum']); ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" name="approve" class="btn btn-success btn-sm">Approve</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="IDnum" value="<?php echo htmlspecialchars($athlete['IDnum']); ?>">
                                        <input type="hidden" name="action" value="disapprove">
                                        <button type="submit" name="approve" class="btn btn-danger btn-sm">Disapprove</button>
                                    </form>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleLog('<?php echo htmlspecialchars($athlete['IDnum']); ?>')">View Log</button>
                                </div>
                            </td>
                        </tr>
                        <tr id="log-<?php echo htmlspecialchars($athlete['IDnum']); ?>" style="display:none;">
                            <td colspan="10">
                                <div class="card" style="margin: 10px 0;">
                                    <h4>Approval History for <?php echo htmlspecialchars($athlete['firstname'] . ' ' . $athlete['lastname']); ?></h4>
                                    <?php 
                                    $logs = getApprovalLog($pdo, $athlete['IDnum']);
                                    if(empty($logs)): 
                                    ?>
                                        <p>No approval history yet.</p>
                                    <?php else: ?>
                                        <div class="table-container">
                                            <table>
                                                <tr>
                                                    <th>Timestamp</th>
                                                    <th>Approver Role</th>
                                                    <th>Approver Username</th>
                                                    <th>Action</th>
                                                </tr>
                                                <?php foreach($logs as $log): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                                                        <td><?php echo ucfirst(htmlspecialchars($log['approver_role'])); ?></td>
                                                        <td><?php echo htmlspecialchars($log['approver_username']); ?></td>
                                                        <td>
                                                            <span class="badge <?php echo getStatusBadge($log['action']); ?>">
                                                                <?php echo ucfirst(htmlspecialchars($log['action'])); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleLog(athleteID) {
            var logRow = document.getElementById('log-' + athleteID);
            if(logRow.style.display === 'none') {
                logRow.style.display = 'table-row';
            } else {
                logRow.style.display = 'none';
            }
        }
    </script>
</body>
</html>
