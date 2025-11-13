<?php
require_once 'config/database.php';
session_start();

$pdo = $GLOBALS['pdo'];
$msg = '';

if($_POST) {
    $userName = $_POST['userName'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM registrations WHERE userName = ?");
    $stmt->execute([$userName]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $userName;
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
        exit;
    } else {
        $msg = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="navbar-brand">UC Intramurals</a>
            <ul class="navbar-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </div>
    </nav>

    <div class="auth-container">
        <div class="auth-card">
            <h1>Login</h1>
            
            <?php if($msg): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="userName" class="form-control" placeholder="Username" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width:100%;">Login</button>
            </form>
            
            <div class="text-center mt-2">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</body>
</html>
