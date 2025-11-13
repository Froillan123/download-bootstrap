<?php
require_once 'config/database.php';
session_start();

if($_SESSION['role'] != 'athlete') {
    header("Location: index.php");
    exit;
}

$pdo = $GLOBALS['pdo'];
$msg = '';
$userName = $_SESSION['user'];

// CREATE/UPDATE Profile
if($_POST) {
    $IDnum = $userName;
    $eventID = $_POST['eventID'];
    $deptID = $_POST['deptID'];
    $lastname = $_POST['lastname'];
    $firstname = $_POST['firstname'];
    $middleInit = $_POST['middleInit'];
    $course = $_POST['course'];
    $year = $_POST['year'];
    $civilStatus = $_POST['civilStatus'];
    $gender = $_POST['gender'];
    $birthdate = $_POST['birthdate'];
    $contactNo = $_POST['contactNo'];
    $address = $_POST['address'];
    $coachID = $_POST['coachID'];
    $deanID = $_POST['deanID'];
    
    // Check if profile exists
    $stmt = $pdo->prepare("SELECT * FROM athlete_profile WHERE IDnum = ?");
    $stmt->execute([$IDnum]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($exists) {
        // UPDATE
        $stmt = $pdo->prepare("UPDATE athlete_profile SET eventID=?, deptID=?, lastname=?, firstname=?, middleInit=?, course=?, year=?, civilStatus=?, gender=?, birthdate=?, contactNo=?, address=?, coachID=?, deanID=? WHERE IDnum=?");
        $stmt->execute([$eventID, $deptID, $lastname, $firstname, $middleInit, $course, $year, $civilStatus, $gender, $birthdate, $contactNo, $address, $coachID, $deanID]);
        $msg = "Profile updated!";
    } else {
        // CREATE
        $stmt = $pdo->prepare("INSERT INTO athlete_profile (IDnum, eventID, deptID, lastname, firstname, middleInit, course, year, civilStatus, gender, birthdate, contactNo, address, coachID, deanID) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$IDnum, $eventID, $deptID, $lastname, $firstname, $middleInit, $course, $year, $civilStatus, $gender, $birthdate, $contactNo, $address, $coachID, $deanID]);
        $msg = "Profile created!";
    }
}

// Get profile data with JOINs
$stmt = $pdo->prepare("
    SELECT ap.*, e.eventName, d.deptName, c.fname as coachFname, c.lname as coachLname, 
           de.fname as deanFname, de.lname as deanLname
    FROM athlete_profile ap
    LEFT JOIN event e ON ap.eventID = e.EventID
    LEFT JOIN department d ON ap.deptID = d.deptId
    LEFT JOIN coach c ON ap.coachID = c.userName
    LEFT JOIN dean de ON ap.deanID = de.userName
    WHERE ap.IDnum = ?
");
$stmt->execute([$userName]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Get dropdowns
$events = $pdo->query("SELECT * FROM event ORDER BY eventName")->fetchAll(PDO::FETCH_ASSOC);
$depts = $pdo->query("SELECT * FROM department ORDER BY deptName")->fetchAll(PDO::FETCH_ASSOC);
$coaches = $pdo->query("SELECT * FROM coach ORDER BY lname, fname")->fetchAll(PDO::FETCH_ASSOC);
$deans = $pdo->query("SELECT * FROM dean ORDER BY lname, fname")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Athlete Profile</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="navbar-brand">UC Intramurals</a>
            <ul class="navbar-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>My Athlete Profile</h1>
            </div>

            <?php if($msg): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <h3>Personal Information</h3>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="lastname" class="form-control" placeholder="Last Name" value="<?php echo htmlspecialchars($profile['lastname'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="firstname" class="form-control" placeholder="First Name" value="<?php echo htmlspecialchars($profile['firstname'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Middle Initial</label>
                    <input type="text" name="middleInit" class="form-control" placeholder="Middle Initial" value="<?php echo htmlspecialchars($profile['middleInit'] ?? ''); ?>" maxlength="10">
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label>Course *</label>
                            <input type="text" name="course" class="form-control" placeholder="Course" value="<?php echo htmlspecialchars($profile['course'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label>Year *</label>
                            <input type="number" name="year" class="form-control" placeholder="Year" value="<?php echo htmlspecialchars($profile['year'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label>Civil Status *</label>
                            <input type="text" name="civilStatus" class="form-control" placeholder="Civil Status" value="<?php echo htmlspecialchars($profile['civilStatus'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label>Gender *</label>
                            <select name="gender" class="form-control" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ($profile['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($profile['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($profile['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label>Birthdate *</label>
                            <input type="date" name="birthdate" class="form-control" value="<?php echo htmlspecialchars($profile['birthdate'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label>Contact No *</label>
                            <input type="text" name="contactNo" class="form-control" placeholder="Contact No" value="<?php echo htmlspecialchars($profile['contactNo'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Address *</label>
                    <textarea name="address" class="form-control" placeholder="Address" required><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                </div>
                
                <h3>Event & Department</h3>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label>Event *</label>
                            <select name="eventID" class="form-control" required>
                                <option value="">Select Event</option>
                                <?php foreach($events as $event): ?>
                                    <option value="<?php echo $event['EventID']; ?>" <?php echo ($profile['eventID'] ?? '') == $event['EventID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($event['eventName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label>Department *</label>
                            <select name="deptID" class="form-control" required>
                                <option value="">Select Department</option>
                                <?php foreach($depts as $dept): ?>
                                    <option value="<?php echo $dept['deptId']; ?>" <?php echo ($profile['deptID'] ?? '') == $dept['deptId'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['deptName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <h3>Coach & Dean</h3>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label>Coach *</label>
                            <select name="coachID" class="form-control" required>
                                <option value="">Select Coach</option>
                                <?php foreach($coaches as $coach): ?>
                                    <option value="<?php echo $coach['userName']; ?>" <?php echo ($profile['coachID'] ?? '') == $coach['userName'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($coach['fname'] . ' ' . $coach['lname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label>Dean *</label>
                            <select name="deanID" class="form-control" required>
                                <option value="">Select Dean</option>
                                <?php foreach($deans as $dean): ?>
                                    <option value="<?php echo $dean['userName']; ?>" <?php echo ($profile['deanID'] ?? '') == $dean['userName'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dean['fname'] . ' ' . $dean['lname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Submit Profile</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
            
            <?php if($profile): ?>
                <div class="mt-3">
                    <h3>Current Profile</h3>
                    <div class="table-container">
                        <table>
                            <tr>
                                <th>Field</th>
                                <th>Value</th>
                            </tr>
                            <tr>
                                <td><strong>Event</strong></td>
                                <td><?php echo htmlspecialchars($profile['eventName'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Department</strong></td>
                                <td><?php echo htmlspecialchars($profile['deptName'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Coach</strong></td>
                                <td><?php echo htmlspecialchars($profile['coachFname'] . ' ' . $profile['coachLname']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Dean</strong></td>
                                <td><?php echo htmlspecialchars($profile['deanFname'] . ' ' . $profile['deanLname']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
