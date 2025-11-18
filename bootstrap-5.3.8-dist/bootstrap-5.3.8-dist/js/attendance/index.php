<?php
/**
 * Employee Attendance Recording System
 * Single-file implementation with auto DB creation
 */

ob_start();
session_start();
date_default_timezone_set('Asia/Manila');

define('DB_HOST', 'localhost');
define('DB_NAME', 'attendance_db');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    createAttendanceTables($pdo);
} catch(PDOException $e) {
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        $root = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
        $root->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $root->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        createAttendanceTables($pdo);
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}

function createAttendanceTables($pdo) {
    $tables = [
        "CREATE TABLE IF NOT EXISTS departments (
            depCode INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            depFName VARCHAR(100) NOT NULL,
            depLName VARCHAR(100) DEFAULT NULL,
            depTelNo VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS employees (
            empID INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            depCode INT(11) NOT NULL,
            empFName VARCHAR(100) NOT NULL,
            empMName VARCHAR(100) DEFAULT NULL,
            empLName VARCHAR(100) NOT NULL,
            empRPH DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (depCode) REFERENCES departments(depCode) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS attendance (
            attRN INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            empID INT(11) NOT NULL,
            attDate DATE NOT NULL,
            attTimeIn DATETIME NOT NULL,
            attTimeOut DATETIME NOT NULL,
            attStat ENUM('active','cancelled') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (empID) REFERENCES employees(empID) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    ];

    foreach($tables as $sql) {
        try {
            $pdo->exec($sql);
        } catch(PDOException $e) {
            // ignore
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

function getFullName($fname, $mname, $lname) {
    $name = trim($fname);
    if (!empty($mname)) {
        $name .= ' ' . trim($mname);
    }
    $name .= ' ' . trim($lname);
    return $name;
}

$page = $_GET['page'] ?? 'dashboard';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$message = $_SESSION['message'] ?? '';
$messageType = $_SESSION['messageType'] ?? '';
unset($_SESSION['message'], $_SESSION['messageType']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    switch($page) {
        case 'departments':
            handleDepartmentAction($pdo, $action);
            break;
        case 'employees':
            handleEmployeeAction($pdo, $action);
            break;
        case 'attendance':
            handleAttendanceAction($pdo, $action);
            break;
    }
}

function setMessage($text, $type = 'success') {
    $_SESSION['message'] = $text;
    $_SESSION['messageType'] = $type;
}

function handleDepartmentAction($pdo, $action) {
    if ($action === 'add') {
        $fname = trim($_POST['depFName'] ?? '');
        $lname = trim($_POST['depLName'] ?? '');
        $tel = trim($_POST['depTelNo'] ?? '');

        if (empty($fname)) {
            setMessage('Department name (First Name field) is required.', 'danger');
            redirect("?page=departments");
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO departments (depFName, depLName, depTelNo) VALUES (?, ?, ?)");
            $stmt->execute([$fname, $lname, $tel]);
            setMessage('Department added successfully.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=departments");
    } elseif ($action === 'edit') {
        $code = (int)($_POST['depCode'] ?? 0);
        $fname = trim($_POST['depFName'] ?? '');
        $lname = trim($_POST['depLName'] ?? '');
        $tel = trim($_POST['depTelNo'] ?? '');
        try {
            $stmt = $pdo->prepare("UPDATE departments SET depFName = ?, depLName = ?, depTelNo = ? WHERE depCode = ?");
            $stmt->execute([$fname, $lname, $tel, $code]);
            setMessage('Department updated successfully.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=departments");
    } elseif ($action === 'delete') {
        $code = (int)($_POST['depCode'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM departments WHERE depCode = ?");
            $stmt->execute([$code]);
            setMessage('Department deleted permanently.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=departments");
    }
}

function handleEmployeeAction($pdo, $action) {
    if ($action === 'add') {
        $depCode = (int)($_POST['depCode'] ?? 0);
        $fname = trim($_POST['empFName'] ?? '');
        $mname = trim($_POST['empMName'] ?? '');
        $lname = trim($_POST['empLName'] ?? '');
        $rate = isset($_POST['empRPH']) ? (float)$_POST['empRPH'] : 0;

        if (empty($fname) || empty($lname) || $depCode === 0) {
            setMessage('Employee name and department are required.', 'danger');
            redirect("?page=employees");
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO employees (depCode, empFName, empMName, empLName, empRPH) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$depCode, $fname, $mname, $lname, $rate]);
            setMessage('Employee added successfully.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=employees");
    } elseif ($action === 'edit') {
        $id = (int)($_POST['empID'] ?? 0);
        $depCode = (int)($_POST['depCode'] ?? 0);
        $fname = trim($_POST['empFName'] ?? '');
        $mname = trim($_POST['empMName'] ?? '');
        $lname = trim($_POST['empLName'] ?? '');
        $rate = isset($_POST['empRPH']) ? (float)$_POST['empRPH'] : 0;

        try {
            $stmt = $pdo->prepare("UPDATE employees SET depCode = ?, empFName = ?, empMName = ?, empLName = ?, empRPH = ? WHERE empID = ?");
            $stmt->execute([$depCode, $fname, $mname, $lname, $rate, $id]);
            setMessage('Employee updated successfully.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=employees");
    } elseif ($action === 'delete') {
        $id = (int)($_POST['empID'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM employees WHERE empID = ?");
            $stmt->execute([$id]);
            setMessage('Employee deleted permanently.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=employees");
    }
}

function handleAttendanceAction($pdo, $action) {
    if ($action === 'add') {
        $empID = (int)($_POST['empID'] ?? 0);
        $date = $_POST['attDate'] ?? date('Y-m-d');
        $timeIn = $_POST['attTimeIn'] ?? '';
        $timeOut = $_POST['attTimeOut'] ?? '';

        if (!$empID || empty($timeIn) || empty($timeOut)) {
            setMessage('Employee, time-in, and time-out are required.', 'danger');
            redirect("?page=attendance");
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO attendance (empID, attDate, attTimeIn, attTimeOut, attStat) VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([$empID, $date, $timeIn, $timeOut]);
            setMessage('Attendance recorded successfully.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=attendance");
    } elseif ($action === 'edit') {
        $attRN = (int)($_POST['attRN'] ?? 0);
        $empID = (int)($_POST['empID'] ?? 0);
        $date = $_POST['attDate'] ?? date('Y-m-d');
        $timeIn = $_POST['attTimeIn'] ?? '';
        $timeOut = $_POST['attTimeOut'] ?? '';

        try {
            $stmt = $pdo->prepare("UPDATE attendance SET empID = ?, attDate = ?, attTimeIn = ?, attTimeOut = ? WHERE attRN = ?");
            $stmt->execute([$empID, $date, $timeIn, $timeOut, $attRN]);
            setMessage('Attendance updated successfully.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=attendance");
    } elseif ($action === 'delete') {
        $attRN = (int)($_POST['attRN'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM attendance WHERE attRN = ?");
            $stmt->execute([$attRN]);
            setMessage('Attendance record deleted permanently.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=attendance");
    } elseif ($action === 'cancel') {
        $attRN = (int)($_POST['attRN'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE attendance SET attStat = 'cancelled' WHERE attRN = ?");
            $stmt->execute([$attRN]);
            setMessage('Attendance marked as cancelled.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=attendance");
    } elseif ($action === 'activate') {
        $attRN = (int)($_POST['attRN'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE attendance SET attStat = 'active' WHERE attRN = ?");
            $stmt->execute([$attRN]);
            setMessage('Attendance reactivated.');
        } catch(PDOException $e) {
            setMessage("Error: " . $e->getMessage(), 'danger');
        }
        redirect("?page=attendance");
    }
}

function calculateHours($timeIn, $timeOut) {
    $start = strtotime($timeIn);
    $end = strtotime($timeOut);
    if ($start === false || $end === false || $end <= $start) {
        return 0;
    }
    return round(($end - $start) / 3600, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Attendance Recording System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f4f4f4; }
        header { background: #0a2a43; color: #fff; padding: 15px; }
        nav a { color: #fff; margin-right: 15px; text-decoration: none; font-weight: 600; }
        nav a:hover { text-decoration: underline; }
        .container { padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; background: #fff; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #e5f1fb; }
        button { padding: 6px 12px; margin: 2px; cursor: pointer; }
        form.inline, form[style*="display:inline"] { display: inline; }
        .card-grid { display: flex; gap: 12px; flex-wrap: wrap; }
        .card { background: #fff; padding: 15px; flex: 1 1 200px; border: 1px solid #ccc; border-radius: 4px; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .message.success { background: #d1e7dd; color: #0f5132; }
        .message.danger { background: #f8d7da; color: #842029; }
        form input[type="text"], form input[type="number"], form input[type="date"], form input[type="datetime-local"], form select {
            width: 100%; padding: 6px; margin: 4px 0 10px 0; box-sizing: border-box;
        }
        .section-box { background:#fff; padding:15px; border:1px solid #ccc; border-radius:4px; margin-bottom:20px; }
        .section-box h4, .section-box h5 { margin-top:0; }
    </style>
</head>
<body>
    <header>
        <h2>Employee Attendance Recording System</h2>
        <nav>
            <a href="?page=dashboard">Dashboard</a>
            <a href="?page=departments">Departments</a>
            <a href="?page=employees">Employees</a>
            <a href="?page=attendance">Attendance Recording</a>
            <a href="?page=employee_summary">By Employee</a>
            <a href="?page=date_summary">By Date Range</a>
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
                    'departments' => $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn(),
                    'employees' => $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn(),
                    'attendance' => $pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn()
                ];
            ?>
            <div class="card-grid">
                <div class="card">
                    <strong>Departments</strong>
                    <h2><?= $stats['departments'] ?></h2>
                </div>
                <div class="card">
                    <strong>Employees</strong>
                    <h2><?= $stats['employees'] ?></h2>
                </div>
                <div class="card">
                    <strong>Attendance Records</strong>
                    <h2><?= $stats['attendance'] ?></h2>
                </div>
            </div>

        <?php elseif ($page === 'departments'): ?>
            <?php
                if ($search) {
                    $stmt = $pdo->prepare("SELECT * FROM departments WHERE depFName LIKE ? OR depLName LIKE ? OR depTelNo LIKE ? ORDER BY depFName");
                    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
                    $departments = $stmt->fetchAll();
                } else {
                    $departments = $pdo->query("SELECT * FROM departments ORDER BY depFName")->fetchAll();
                }
            ?>
            <div class="section-box">
                <h4>Departments Management <button onclick="showDepartmentForm('add')">Add Department</button></h4>
                <form method="get">
                    <input type="hidden" name="page" value="departments">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search departments...">
                    <button type="submit">Search</button>
                    <?php if ($search): ?><a href="?page=departments">Clear</a><?php endif; ?>
                </form>
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name (First/Last)</th>
                            <th>Telephone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($departments as $dept): ?>
                            <tr>
                                <td><?= $dept['depCode'] ?></td>
                                <td><?= htmlspecialchars(trim($dept['depFName'] . ' ' . $dept['depLName'])) ?></td>
                                <td><?= htmlspecialchars($dept['depTelNo'] ?? '') ?></td>
                                <td>
                                    <button onclick="editDepartment(<?= $dept['depCode'] ?>, '<?= htmlspecialchars($dept['depFName'], ENT_QUOTES) ?>', '<?= htmlspecialchars($dept['depLName'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($dept['depTelNo'] ?? '', ENT_QUOTES) ?>')">Edit</button>
                                    <form method="post" class="inline" onsubmit="return confirm('Delete this department?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="depCode" value="<?= $dept['depCode'] ?>">
                                        <button type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="departmentForm" style="display:none;" class="section-box">
                <h5 id="departmentFormTitle">Add Department</h5>
                <form method="post">
                    <input type="hidden" name="action" id="department_action" value="add">
                    <input type="hidden" name="depCode" id="depCode">
                    <label>Department First Name</label>
                    <input type="text" name="depFName" id="depFName" required>
                    <label>Department Last Name</label>
                    <input type="text" name="depLName" id="depLName">
                    <label>Telephone Number</label>
                    <input type="text" name="depTelNo" id="depTelNo">
                    <div>
                        <button type="button" onclick="document.getElementById('departmentForm').style.display='none'">Cancel</button>
                        <button type="submit">Save</button>
                    </div>
                </form>
            </div>

        <?php elseif ($page === 'employees'): ?>
            <?php
                $departments = $pdo->query("SELECT * FROM departments ORDER BY depFName")->fetchAll();
                if ($search) {
                    $stmt = $pdo->prepare("
                        SELECT e.*, d.depFName, d.depLName
                        FROM employees e
                        LEFT JOIN departments d ON e.depCode = d.depCode
                        WHERE CONCAT(e.empFName, ' ', COALESCE(e.empLName, '')) LIKE ?
                           OR d.depFName LIKE ?
                           OR d.depLName LIKE ?
                        ORDER BY e.empLName, e.empFName
                    ");
                    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
                    $employees = $stmt->fetchAll();
                } else {
                    $employees = $pdo->query("
                        SELECT e.*, d.depFName, d.depLName
                        FROM employees e
                        LEFT JOIN departments d ON e.depCode = d.depCode
                        ORDER BY e.empLName, e.empFName
                    ")->fetchAll();
                }
            ?>
            <div class="section-box">
                <h4>Employees Management <button onclick="showEmployeeForm('add')">Add Employee</button></h4>
                <form method="get">
                    <input type="hidden" name="page" value="employees">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search employees...">
                    <button type="submit">Search</button>
                    <?php if ($search): ?><a href="?page=employees">Clear</a><?php endif; ?>
                </form>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Rate/Hour</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($employees as $emp): ?>
                            <tr>
                                <td><?= $emp['empID'] ?></td>
                                <td><?= htmlspecialchars(getFullName($emp['empFName'], $emp['empMName'], $emp['empLName'])) ?></td>
                                <td><?= htmlspecialchars(trim(($emp['depFName'] ?? '') . ' ' . ($emp['depLName'] ?? ''))) ?></td>
                                <td><?= number_format($emp['empRPH'], 2) ?></td>
                                <td>
                                    <button onclick="editEmployee(
                                        <?= $emp['empID'] ?>,
                                        <?= $emp['depCode'] ?>,
                                        '<?= htmlspecialchars($emp['empFName'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($emp['empMName'] ?? '', ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($emp['empLName'], ENT_QUOTES) ?>',
                                        <?= $emp['empRPH'] ?>
                                    )">Edit</button>
                                    <form method="post" class="inline" onsubmit="return confirm('Delete this employee?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="empID" value="<?= $emp['empID'] ?>">
                                        <button type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="employeeForm" style="display:none;" class="section-box">
                <h5 id="employeeFormTitle">Add Employee</h5>
                <form method="post">
                    <input type="hidden" name="action" id="employee_action" value="add">
                    <input type="hidden" name="empID" id="empID">
                    <label>Department</label>
                    <select name="depCode" id="depCode" required>
                        <option value="">Select Department</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?= $dept['depCode'] ?>"><?= htmlspecialchars(trim($dept['depFName'] . ' ' . $dept['depLName'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>First Name</label>
                    <input type="text" name="empFName" id="empFName" required>
                    <label>Middle Name</label>
                    <input type="text" name="empMName" id="empMName">
                    <label>Last Name</label>
                    <input type="text" name="empLName" id="empLName" required>
                    <label>Rate Per Hour</label>
                    <input type="number" step="0.01" name="empRPH" id="empRPH" required>
                    <div>
                        <button type="button" onclick="document.getElementById('employeeForm').style.display='none'">Cancel</button>
                        <button type="submit">Save</button>
                    </div>
                </form>
            </div>

        <?php elseif ($page === 'attendance'): ?>
            <?php
                $employeesList = $pdo->query("
                    SELECT e.*, d.depFName, d.depLName
                    FROM employees e
                    LEFT JOIN departments d ON e.depCode = d.depCode
                    ORDER BY e.empLName, e.empFName
                ")->fetchAll();

                $attendanceRecords = [];
                if ($search) {
                    $stmt = $pdo->prepare("
                        SELECT a.*, e.empFName, e.empMName, e.empLName
                        FROM attendance a
                        LEFT JOIN employees e ON a.empID = e.empID
                        WHERE e.empFName LIKE ? OR e.empLName LIKE ? OR a.attDate LIKE ?
                        ORDER BY a.attDate DESC, a.attTimeIn DESC
                    ");
                    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
                    $attendanceRecords = $stmt->fetchAll();
                } else {
                    $attendanceRecords = $pdo->query("
                        SELECT a.*, e.empFName, e.empMName, e.empLName
                        FROM attendance a
                        LEFT JOIN employees e ON a.empID = e.empID
                        ORDER BY a.attDate DESC, a.attTimeIn DESC
                    ")->fetchAll();
                }
            ?>
            <div class="section-box">
                <h4>Attendance Recording</h4>
                <form method="post">
                    <input type="hidden" name="action" value="add">
                    <label>Employee</label>
                    <select name="empID" required>
                        <option value="">Select Employee</option>
                        <?php foreach($employeesList as $emp): ?>
                            <option value="<?= $emp['empID'] ?>"><?= htmlspecialchars(getFullName($emp['empFName'], $emp['empMName'], $emp['empLName'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Date</label>
                    <input type="date" name="attDate" value="<?= date('Y-m-d') ?>" required>
                    <label>Time In</label>
                    <input type="datetime-local" name="attTimeIn" required>
                    <label>Time Out</label>
                    <input type="datetime-local" name="attTimeOut" required>
                    <button type="submit">Record Attendance</button>
                </form>
            </div>

            <div class="section-box">
                <h4>Attendance Logs</h4>
                <form method="get">
                    <input type="hidden" name="page" value="attendance">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search attendance...">
                    <button type="submit">Search</button>
                    <?php if ($search): ?><a href="?page=attendance">Clear</a><?php endif; ?>
                </form>
                <table>
                    <thead>
                        <tr>
                            <th>Record #</th>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Status</th>
                            <th>Hours</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($attendanceRecords as $log): ?>
                            <?php $hours = calculateHours($log['attTimeIn'], $log['attTimeOut']); ?>
                            <tr>
                                <td><?= $log['attRN'] ?></td>
                                <td><?= htmlspecialchars(getFullName($log['empFName'] ?? '', $log['empMName'] ?? '', $log['empLName'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($log['attDate']) ?></td>
                                <td><?= date('M d, Y h:i A', strtotime($log['attTimeIn'])) ?></td>
                                <td><?= date('M d, Y h:i A', strtotime($log['attTimeOut'])) ?></td>
                                <td><?= ucfirst($log['attStat']) ?></td>
                                <td><?= number_format($hours, 2) ?></td>
                                <td>
                                    <button onclick="editAttendance(
                                        <?= $log['attRN'] ?>,
                                        <?= $log['empID'] ?>,
                                        '<?= $log['attDate'] ?>',
                                        '<?= date('Y-m-d\TH:i', strtotime($log['attTimeIn'])) ?>',
                                        '<?= date('Y-m-d\TH:i', strtotime($log['attTimeOut'])) ?>'
                                    )">Edit</button>
                                    <?php if ($log['attStat'] === 'active'): ?>
                                        <form method="post" class="inline" onsubmit="return confirm('Cancel this attendance?')">
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="attRN" value="<?= $log['attRN'] ?>">
                                            <button type="submit">Cancel</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" class="inline">
                                            <input type="hidden" name="action" value="activate">
                                            <input type="hidden" name="attRN" value="<?= $log['attRN'] ?>">
                                            <button type="submit">Activate</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" class="inline" onsubmit="return confirm('Delete this record?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="attRN" value="<?= $log['attRN'] ?>">
                                        <button type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="attendanceForm" style="display:none;" class="section-box">
                <h5 id="attendanceFormTitle">Edit Attendance</h5>
                <form method="post">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="attRN" id="attRN">
                    <label>Employee</label>
                    <select name="empID" id="attendance_empID" required>
                        <?php foreach($employeesList as $emp): ?>
                            <option value="<?= $emp['empID'] ?>"><?= htmlspecialchars(getFullName($emp['empFName'], $emp['empMName'], $emp['empLName'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Date</label>
                    <input type="date" name="attDate" id="attendance_date" required>
                    <label>Time In</label>
                    <input type="datetime-local" name="attTimeIn" id="attendance_timeIn" required>
                    <label>Time Out</label>
                    <input type="datetime-local" name="attTimeOut" id="attendance_timeOut" required>
                    <div>
                        <button type="button" onclick="document.getElementById('attendanceForm').style.display='none'">Cancel</button>
                        <button type="submit">Save</button>
                    </div>
                </form>
            </div>

        <?php elseif ($page === 'employee_summary'): ?>
            <?php
                $employeesList = $pdo->query("
                    SELECT e.*, d.depFName, d.depLName
                    FROM employees e
                    LEFT JOIN departments d ON e.depCode = d.depCode
                    ORDER BY e.empLName, e.empFName
                ")->fetchAll();
                $selectedEmp = isset($_GET['empID']) ? (int)$_GET['empID'] : 0;
                $summaryData = [];
                $employeeInfo = null;
                $totalHours = 0;
                $salary = 0;

                if ($selectedEmp) {
                    $stmt = $pdo->prepare("
                        SELECT e.*, d.depFName, d.depLName
                        FROM employees e
                        LEFT JOIN departments d ON e.depCode = d.depCode
                        WHERE e.empID = ?
                    ");
                    $stmt->execute([$selectedEmp]);
                    $employeeInfo = $stmt->fetch();

                    $stmt = $pdo->prepare("
                        SELECT * FROM attendance
                        WHERE empID = ? AND attStat = 'active'
                        ORDER BY attDate DESC, attTimeIn DESC
                    ");
                    $stmt->execute([$selectedEmp]);
                    $summaryData = $stmt->fetchAll();

                    foreach($summaryData as $row) {
                        $hours = calculateHours($row['attTimeIn'], $row['attTimeOut']);
                        $totalHours += $hours;
                    }
                    if ($employeeInfo) {
                        $salary = $totalHours * $employeeInfo['empRPH'];
                    }
                }
            ?>
            <div class="section-box">
                <h4>Attendance Monitoring (By Employee)</h4>
                <form method="get">
                    <input type="hidden" name="page" value="employee_summary">
                    <label>Select Employee</label>
                    <select name="empID" onchange="this.form.submit()">
                        <option value="0">-- Choose Employee --</option>
                        <?php foreach($employeesList as $emp): ?>
                            <option value="<?= $emp['empID'] ?>" <?= $selectedEmp == $emp['empID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars(getFullName($emp['empFName'], $emp['empMName'], $emp['empLName'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <?php if ($employeeInfo): ?>
                    <h5>Employee Info</h5>
                    <p><strong>Name:</strong> <?= htmlspecialchars(getFullName($employeeInfo['empFName'], $employeeInfo['empMName'], $employeeInfo['empLName'])) ?></p>
                    <p><strong>Department:</strong> <?= htmlspecialchars(trim(($employeeInfo['depFName'] ?? '') . ' ' . ($employeeInfo['depLName'] ?? ''))) ?></p>
                    <p><strong>Rate Per Hour:</strong> <?= number_format($employeeInfo['empRPH'], 2) ?></p>
                    <p><strong>Total Hours:</strong> <?= number_format($totalHours, 2) ?></p>
                    <p><strong>Salary:</strong> <?= number_format($salary, 2) ?></p>
                    <p><strong>Date Generated:</strong> <?= date('M d, Y h:i A') ?></p>

                    <h5>Attendance Records</h5>
                    <table>
                        <thead>
                            <tr>
                                <th>Record #</th>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Total Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($summaryData as $row): ?>
                                <?php $hours = calculateHours($row['attTimeIn'], $row['attTimeOut']); ?>
                                <tr>
                                    <td><?= $row['attRN'] ?></td>
                                    <td><?= htmlspecialchars($row['attDate']) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($row['attTimeIn'])) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($row['attTimeOut'])) ?></td>
                                    <td><?= number_format($hours, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif ($selectedEmp): ?>
                    <p>No attendance records found.</p>
                <?php endif; ?>
            </div>

        <?php elseif ($page === 'date_summary'): ?>
            <?php
                $dateFrom = $_GET['date_from'] ?? '';
                $dateTo = $_GET['date_to'] ?? '';
                $rangeRecords = [];
                $rangeHours = 0;

                if ($dateFrom && $dateTo) {
                    $stmt = $pdo->prepare("
                        SELECT a.*, e.empFName, e.empMName, e.empLName
                        FROM attendance a
                        LEFT JOIN employees e ON a.empID = e.empID
                        WHERE a.attDate BETWEEN ? AND ? AND a.attStat = 'active'
                        ORDER BY a.attDate, a.attTimeIn
                    ");
                    $stmt->execute([$dateFrom, $dateTo]);
                    $rangeRecords = $stmt->fetchAll();
                    foreach($rangeRecords as $row) {
                        $rangeHours += calculateHours($row['attTimeIn'], $row['attTimeOut']);
                    }
                }
            ?>
            <div class="section-box">
                <h4>Attendance Monitoring (By Date Range)</h4>
                <form method="get">
                    <input type="hidden" name="page" value="date_summary">
                    <label>Date From</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" required>
                    <label>Date To</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" required>
                    <button type="submit">Generate</button>
                </form>

                <?php if ($dateFrom && $dateTo): ?>
                    <p><strong>Total Hours:</strong> <?= number_format($rangeHours, 2) ?></p>
                    <p><strong>Date Generated:</strong> <?= date('M d, Y h:i A') ?></p>
                    <table>
                        <thead>
                            <tr>
                                <th>Record #</th>
                                <th>Employee</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Total Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rangeRecords as $row): ?>
                                <?php $hours = calculateHours($row['attTimeIn'], $row['attTimeOut']); ?>
                                <tr>
                                    <td><?= $row['attRN'] ?></td>
                                    <td><?= htmlspecialchars(getFullName($row['empFName'] ?? '', $row['empMName'] ?? '', $row['empLName'] ?? '')) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($row['attTimeIn'])) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($row['attTimeOut'])) ?></td>
                                    <td><?= number_format($hours, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function showDepartmentForm(mode) {
            document.getElementById('department_action').value = mode;
            document.getElementById('depCode').value = '';
            document.getElementById('depFName').value = '';
            document.getElementById('depLName').value = '';
            document.getElementById('depTelNo').value = '';
            document.getElementById('departmentFormTitle').textContent = mode === 'add' ? 'Add Department' : 'Edit Department';
            document.getElementById('departmentForm').style.display = 'block';
        }

        function editDepartment(code, fname, lname, tel) {
            showDepartmentForm('edit');
            document.getElementById('depCode').value = code;
            document.getElementById('depFName').value = fname;
            document.getElementById('depLName').value = lname;
            document.getElementById('depTelNo').value = tel;
        }

        function showEmployeeForm(mode) {
            document.getElementById('employee_action').value = mode;
            document.getElementById('empID').value = '';
            document.getElementById('depCode').value = '';
            document.getElementById('empFName').value = '';
            document.getElementById('empMName').value = '';
            document.getElementById('empLName').value = '';
            document.getElementById('empRPH').value = '';
            document.getElementById('employeeFormTitle').textContent = mode === 'add' ? 'Add Employee' : 'Edit Employee';
            document.getElementById('employeeForm').style.display = 'block';
        }

        function editEmployee(id, depCode, fname, mname, lname, rate) {
            showEmployeeForm('edit');
            document.getElementById('empID').value = id;
            document.getElementById('depCode').value = depCode;
            document.getElementById('empFName').value = fname;
            document.getElementById('empMName').value = mname;
            document.getElementById('empLName').value = lname;
            document.getElementById('empRPH').value = rate;
        }

        function editAttendance(attRN, empID, date, timeIn, timeOut) {
            document.getElementById('attRN').value = attRN;
            document.getElementById('attendance_empID').value = empID;
            document.getElementById('attendance_date').value = date;
            document.getElementById('attendance_timeIn').value = timeIn;
            document.getElementById('attendance_timeOut').value = timeOut;
            document.getElementById('attendanceForm').style.display = 'block';
        }
    </script>
</body>
</html>
<?php
ob_end_flush();
?>

