<?php
require_once 'config/database.php';
session_start();

if($_SESSION['role'] != 'coach') {
    header("Location: index.php");
    exit;
}

$pdo = $GLOBALS['pdo'];
$msg = '';
$userName = $_SESSION['user'];

// UPDATE Profile
if(isset($_POST['update'])) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $mobile = $_POST['mobile'];
    $deptID = $_POST['deptID'];
    
    try {
        // Check if profile exists
        $stmt = $pdo->prepare("SELECT * FROM coach WHERE userName = ?");
        $stmt->execute([$userName]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($exists) {
            // UPDATE
            $stmt = $pdo->prepare("UPDATE coach SET fname = ?, lname = ?, mobile = ?, deptID = ? WHERE userName = ?");
            $stmt->execute([$fname, $lname, $mobile, $deptID, $userName]);
            $msg = "Profile updated successfully!";
        } else {
            // CREATE (if somehow missing)
            $stmt = $pdo->prepare("INSERT INTO coach (userName, fname, lname, mobile, deptID) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userName, $fname, $lname, $mobile, $deptID]);
            $msg = "Profile created successfully!";
        }
    } catch(PDOException $e) {
        $msg = "Error: " . $e->getMessage();
    }
}

// Get profile data
$stmt = $pdo->prepare("
    SELECT c.*, d.deptName 
    FROM coach c
    LEFT JOIN department d ON c.deptID = d.deptId
    WHERE c.userName = ?
");
$stmt->execute([$userName]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Get departments for dropdown
$depts = $pdo->query("SELECT * FROM department ORDER BY deptName")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile - Coach</title>
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
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>My Profile - Coach</h1>
            </div>

            <?php if($msg): ?>
                <div class="alert alert-<?php echo strpos($msg, 'Error') !== false ? 'danger' : 'success'; ?>">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($userName); ?>" disabled>
                    <small>Username cannot be changed</small>
                </div>

                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="fname" class="form-control" value="<?php echo htmlspecialchars($profile['fname'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="lname" class="form-control" value="<?php echo htmlspecialchars($profile['lname'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Mobile *</label>
                    <input type="text" name="mobile" class="form-control" value="<?php echo htmlspecialchars($profile['mobile'] ?? ''); ?>" required>
                </div>

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

                <button type="submit" name="update" class="btn btn-primary">Update Profile</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>

            <?php if($profile): ?>
                <div class="mt-3">
                    <h3>Current Profile Information</h3>
                    <div class="table-container">
                        <table>
                            <tr>
                                <th>Field</th>
                                <th>Value</th>
                            </tr>
                            <tr>
                                <td><strong>Username</strong></td>
                                <td><?php echo htmlspecialchars($userName); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Name</strong></td>
                                <td><?php echo htmlspecialchars($profile['fname'] . ' ' . $profile['lname']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Mobile</strong></td>
                                <td><?php echo htmlspecialchars($profile['mobile']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Department</strong></td>
                                <td><?php echo htmlspecialchars($profile['deptName'] ?? 'N/A'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

