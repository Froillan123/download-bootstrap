<?php
session_start();
include 'includes/functions.php';
if (!is_admin()) header("Location: index.php");

include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $start = $_POST['start'];
    $end = $_POST['end'];
    $status = $_POST['status'];
    
    // Validate dates
    if (strtotime($end) <= strtotime($start)) {
        $_SESSION['error_message'] = "End time must be after start time!";
        header("Location: settings.php");
        exit();
    }
    
    // Convert to MySQL datetime format
    $start_datetime = date('Y-m-d H:i:s', strtotime($start));
    $end_datetime = date('Y-m-d H:i:s', strtotime($end));
    
    mysqli_query($conn, "UPDATE settings SET setting_value='$start_datetime' WHERE setting_key='voting_start'");
    mysqli_query($conn, "UPDATE settings SET setting_value='$end_datetime' WHERE setting_key='voting_end'");
    mysqli_query($conn, "UPDATE settings SET setting_value='$status' WHERE setting_key='voting_status'");
    
    $_SESSION['success_message'] = "Settings updated successfully!";
    header("Location: admin_dashboard.php");
    exit();
}

$res = mysqli_query($conn, "SELECT * FROM settings");
$settings = [];
while ($row = mysqli_fetch_assoc($res)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Convert database datetime to HTML5 datetime-local format
$start_value = isset($settings['voting_start']) ? date('Y-m-d\TH:i', strtotime($settings['voting_start'])) : '';
$end_value = isset($settings['voting_end']) ? date('Y-m-d\TH:i', strtotime($settings['voting_end'])) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container">
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Voting Settings</h4>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="start" class="form-label">Voting Start Time</label>
                        <input type="datetime-local" class="form-control" id="start" name="start" value="<?= $start_value ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="end" class="form-label">Voting End Time</label>
                        <input type="datetime-local" class="form-control" id="end" name="end" value="<?= $end_value ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Voting Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active" <?= (isset($settings['voting_status']) && $settings['voting_status'] === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= (isset($settings['voting_status']) && $settings['voting_status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                    <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>