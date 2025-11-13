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
    $deptName = $_POST['deptName'];
    $stmt = $pdo->prepare("INSERT INTO department (deptName) VALUES (?)");
    $stmt->execute([$deptName]);
    $msg = "Department created!";
}

// UPDATE
if(isset($_POST['update'])) {
    $deptId = $_POST['deptId'];
    $deptName = $_POST['deptName'];
    $stmt = $pdo->prepare("UPDATE department SET deptName = ? WHERE deptId = ?");
    $stmt->execute([$deptName, $deptId]);
    $msg = "Department updated!";
}

// DELETE
if(isset($_GET['delete'])) {
    $deptId = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM department WHERE deptId = ?");
    $stmt->execute([$deptId]);
    $msg = "Department deleted!";
}

// READ - Get all departments
$depts = $pdo->query("SELECT * FROM department ORDER BY deptName")->fetchAll(PDO::FETCH_ASSOC);

// Get department for edit
$editDept = null;
if(isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM department WHERE deptId = ?");
    $stmt->execute([$_GET['edit']]);
    $editDept = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Department Management</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="navbar-brand">UC Intramurals</a>
            <ul class="navbar-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="event.php">Events</a></li>
                <li><a href="schedule.php">Schedule</a></li>
                <li><a href="report.php">Reports</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Department Management</h1>
            </div>

            <?php if($msg): ?>
                <div class="alert alert-<?php echo strpos($msg, 'Error') !== false ? 'danger' : 'success'; ?>">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-6">
                    <h2>Create Department</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Department Name *</label>
                            <input type="text" name="deptName" class="form-control" placeholder="Department Name" required>
                        </div>
                        <button type="submit" name="create" class="btn btn-primary">Create</button>
                    </form>
                </div>

                <div class="col-6">
                    <?php if($editDept): ?>
                        <h2>Update Department</h2>
                        <form method="POST">
                            <input type="hidden" name="deptId" value="<?php echo $editDept['deptId']; ?>">
                            <div class="form-group">
                                <label>Department Name *</label>
                                <input type="text" name="deptName" class="form-control" value="<?php echo htmlspecialchars($editDept['deptName']); ?>" required>
                            </div>
                            <button type="submit" name="update" class="btn btn-success">Update</button>
                            <a href="department.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <h2>Department List</h2>
            <div class="table-container">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Department Name</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach($depts as $dept): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($dept['deptId']); ?></td>
                            <td><?php echo htmlspecialchars($dept['deptName']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="?edit=<?php echo $dept['deptId']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <a href="?delete=<?php echo $dept['deptId']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this department?')">Delete</a>
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
