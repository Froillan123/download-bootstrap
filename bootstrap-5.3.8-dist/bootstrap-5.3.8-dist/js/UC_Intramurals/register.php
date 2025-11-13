<?php
require_once 'config/database.php';
session_start();

$pdo = $GLOBALS['pdo'];
$msg = '';

if($_POST) {
    $userName = $_POST['userName'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $fname = $_POST['fname'] ?? '';
    $lname = $_POST['lname'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $deptID = $_POST['deptID'] ?? '';
    
    try {
        // Insert into registrations
        $stmt = $pdo->prepare("INSERT INTO registrations (userName, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$userName, $password, $role]);
        
        // Insert into role-specific table (only for roles that need it)
        if($role == 'tournament_manager') {
            if(empty($deptID)) {
                throw new Exception("Department is required for Tournament Manager");
            }
            $stmt = $pdo->prepare("INSERT INTO tournamentmanager (userName, fname, lname, mobile, deptID) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userName, $fname, $lname, $mobile, $deptID]);
        } elseif($role == 'coach') {
            if(empty($deptID)) {
                throw new Exception("Department is required for Coach");
            }
            $stmt = $pdo->prepare("INSERT INTO coach (userName, fname, lname, mobile, deptID) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userName, $fname, $lname, $mobile, $deptID]);
        } elseif($role == 'dean') {
            if(empty($deptID)) {
                throw new Exception("Department is required for Dean");
            }
            $stmt = $pdo->prepare("INSERT INTO dean (userName, fname, lname, mobile, deptID) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userName, $fname, $lname, $mobile, $deptID]);
        }
        // Admin and Athlete don't need role-specific table during registration
        
        $msg = "Registration successful! <a href='login.php'>Login here</a>";
    } catch(PDOException $e) {
        $msg = "Error: " . $e->getMessage();
    } catch(Exception $e) {
        $msg = "Error: " . $e->getMessage();
    }
}

// Get departments from database
$depts = $pdo->query("SELECT * FROM department")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="navbar-brand">UC Intramurals</a>
            <ul class="navbar-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Register</h1>
            </div>

            <?php if($msg): ?>
                <div class="alert alert-<?php echo strpos($msg, 'Error') !== false ? 'danger' : 'success'; ?>">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="regForm">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="userName" class="form-control" placeholder="Username" required>
                </div>

                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>

                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" id="roleSelect" class="form-control" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="tournament_manager">Tournament Manager</option>
                        <option value="coach">Coach</option>
                        <option value="dean">Dean</option>
                        <option value="athlete">Athlete</option>
                    </select>
                </div>

                <div id="nameFields">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="fname" id="fname" class="form-control" placeholder="First Name">
                    </div>

                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="lname" id="lname" class="form-control" placeholder="Last Name">
                    </div>

                    <div class="form-group">
                        <label>Mobile *</label>
                        <input type="text" name="mobile" id="mobile" class="form-control" placeholder="Mobile">
                    </div>
                </div>

                <div id="deptField" class="form-group">
                    <label>Department *</label>
                    <select name="deptID" id="deptID" class="form-control">
                        <option value="">Select Department</option>
                        <?php foreach($depts as $dept): ?>
                            <option value="<?php echo $dept['deptId']; ?>"><?php echo htmlspecialchars($dept['deptName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">Register</button>
            </form>
        </div>
    </div>
    
    <script>
        document.getElementById('roleSelect').addEventListener('change', function() {
            var role = this.value;
            var nameFields = document.getElementById('nameFields');
            var deptField = document.getElementById('deptField');
            var fname = document.getElementById('fname');
            var lname = document.getElementById('lname');
            var mobile = document.getElementById('mobile');
            var deptID = document.getElementById('deptID');
            
            // Admin and Athlete don't need name/mobile/dept
            if(role == 'admin' || role == 'athlete') {
                nameFields.style.display = 'none';
                deptField.style.display = 'none';
                fname.removeAttribute('required');
                lname.removeAttribute('required');
                mobile.removeAttribute('required');
                deptID.removeAttribute('required');
            } else {
                // Tournament Manager, Coach, Dean need name/mobile/dept
                nameFields.style.display = 'block';
                deptField.style.display = 'block';
                fname.setAttribute('required', 'required');
                lname.setAttribute('required', 'required');
                mobile.setAttribute('required', 'required');
                deptID.setAttribute('required', 'required');
            }
        });
    </script>
</body>
</html>
