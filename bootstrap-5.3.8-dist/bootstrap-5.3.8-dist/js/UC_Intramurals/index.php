<?php
require_once 'config/database.php';
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>UC Intramurals</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="navbar-brand">UC Intramurals System</a>
            <ul class="navbar-menu">
                <?php if(isset($_SESSION['user'])): ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <?php if(isset($_SESSION['user'])): ?>
                <div class="card-header">
                    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?></h1>
                    <p>Role: <span class="badge badge-info"><?php echo ucfirst(htmlspecialchars($_SESSION['role'])); ?></span></p>
                </div>

                <?php if($_SESSION['role'] == 'admin'): ?>
                    <h2>Admin Menu</h2>
                    <div class="row">
                        <div class="col-6">
                            <a href="register.php" class="btn btn-primary" style="width:100%; margin-bottom:10px;">Register User</a>
                            <a href="department.php" class="btn btn-primary" style="width:100%; margin-bottom:10px;">Department Management</a>
                            <a href="event.php" class="btn btn-primary" style="width:100%; margin-bottom:10px;">Event Management</a>
                        </div>
                        <div class="col-6">
                            <a href="athlete_approve.php" class="btn btn-primary" style="width:100%; margin-bottom:10px;">Approve Athletes</a>
                            <a href="schedule.php" class="btn btn-primary" style="width:100%; margin-bottom:10px;">Schedule Management</a>
                            <a href="report.php" class="btn btn-primary" style="width:100%; margin-bottom:10px;">Reports</a>
                        </div>
                    </div>
                <?php elseif($_SESSION['role'] == 'tournament_manager'): ?>
                    <h2>Tournament Manager Menu</h2>
                    <div class="row">
                        <div class="col-6">
                            <a href="event.php" class="btn btn-primary" style="width:100%; margin-bottom:10px;">Event Management</a>
                            <a href="tournament_manager_profile.php" class="btn btn-primary" style="width:100%; margin-bottom:10px;">My Profile</a>
                        </div>
                    </div>
                <?php elseif($_SESSION['role'] == 'coach'): ?>
                    <h2>Coach Menu</h2>
                    <div class="row">
                        <div class="col-6">
                            <a href="athlete_approve.php" class="btn btn-primary" style="width:100%; margin-bottom:10px;">Approve Athletes</a>
                            <a href="coach_profile.php" class="btn btn-primary" style="width:100%; margin-bottom:10px;">My Profile</a>
                        </div>
                    </div>
                <?php elseif($_SESSION['role'] == 'dean'): ?>
                    <h2>Dean Menu</h2>
                    <div class="row">
                        <div class="col-6">
                            <a href="athlete_approve.php" class="btn btn-primary" style="width:100%; margin-bottom:10px;">Approve Athletes</a>
                            <a href="search.php" class="btn btn-primary" style="width:100%; margin-bottom:10px;">Search & Report</a>
                            <a href="dean_profile.php" class="btn btn-primary" style="width:100%; margin-bottom:10px;">My Profile</a>
                        </div>
                    </div>
                <?php elseif($_SESSION['role'] == 'athlete'): ?>
                    <h2>Athlete Menu</h2>
                    <div class="row">
                        <div class="col-6">
                            <a href="athlete_profile.php" class="btn btn-primary" style="width:100%; margin-bottom:10px;">My Profile</a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="card-header">
                    <h1>UC Intramurals System</h1>
                    <p>Welcome to the UC Intramurals Management System</p>
                </div>
                <div class="text-center">
                    <a href="register.php" class="btn btn-primary btn-lg">Register</a>
                    <a href="login.php" class="btn btn-success btn-lg">Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
