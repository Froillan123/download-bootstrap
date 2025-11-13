<?php
session_start();
include 'includes/functions.php';
include 'includes/db.php';

if (!is_admin()) header("Location: index.php");

// Handle reset voting session
if (isset($_POST['reset_voting'])) {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete winners first (due to FK constraint referencing candidates)
        mysqli_query($conn, "DELETE FROM winners");
        
        // Delete votes (must come before candidates due to FK constraint)
        mysqli_query($conn, "DELETE FROM votes");
        
        // Delete candidates
        mysqli_query($conn, "DELETE FROM candidates");
        
        // Reset AUTO_INCREMENT for candidates table
        mysqli_query($conn, "ALTER TABLE candidates AUTO_INCREMENT = 1");
        
        // Reset AUTO_INCREMENT for winners table
        mysqli_query($conn, "ALTER TABLE winners AUTO_INCREMENT = 1");
        
        // Reset voter status
        mysqli_query($conn, "UPDATE users SET has_voted = 0 WHERE role = 'voter'");
        
        // Reset voting status
        mysqli_query($conn, "UPDATE settings SET setting_value = 'active' WHERE setting_key = 'voting_status'");
        
        // Commit transaction
        mysqli_commit($conn);
        
        $_SESSION['success_message'] = "Voting session has been reset successfully! All candidates, votes, and winners have been cleared.";
        header("Location: admin_dashboard.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction if error occurs
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Error resetting voting session: " . $e->getMessage();
        header("Location: admin_dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .danger-zone {
            border-left: 4px solid #dc3545;
            background-color: #fff8f8;
        }
        .reset-btn {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .reset-btn:hover {
            background-color: #bb2d3b;
            border-color: #bb2d3b;
        }
        .reset-modal .modal-header {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <h2 class="mb-4">Admin Dashboard</h2>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Manage Positions</h5>
                        <p class="card-text">Add, edit, or remove voting positions</p>
                        <a href="add_position.php" class="btn btn-primary">Go to Positions</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Manage Candidates</h5>
                        <p class="card-text">Add or remove candidates for positions</p>
                        <a href="add_candidate.php" class="btn btn-primary">Go to Candidates</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Manage Voters</h5>
                        <p class="card-text">Add or remove voter accounts</p>
                        <a href="add_voter.php" class="btn btn-primary">Go to Voters</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">View Results</h5>
                        <p class="card-text">See current voting results</p>
                        <a href="results.php" class="btn btn-primary">View Results</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">View Winners</h5>
                        <p class="card-text">See the winning candidates</p>
                        <a href="winners.php" class="btn btn-primary">View Winners</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Voting Settings</h5>
                        <p class="card-text">Configure voting period and status</p>
                        <a href="settings.php" class="btn btn-primary">Go to Settings</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Danger Zone - Reset Voting Session -->
        <div class="card danger-zone mt-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Danger Zone</h5>
            </div>
            <div class="card-body">
                <h5 class="card-title">Reset Voting Session</h5>
                <p class="card-text text-danger">
                    <strong>Warning:</strong> This will permanently delete all votes and candidates, and reset voter statuses. 
                    This action cannot be undone!
                </p>
                <button type="button" class="btn reset-btn" data-bs-toggle="modal" data-bs-target="#resetModal">
                    Reset Voting Session
                </button>
            </div>
        </div>
    </div>

    <!-- Reset Confirmation Modal -->
    <div class="modal fade reset-modal" id="resetModal" tabindex="-1" aria-labelledby="resetModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetModalLabel">Confirm Reset</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reset the voting session?</p>
                    <p class="text-danger"><strong>This will:</strong></p>
                    <ul class="text-danger">
                        <li>Delete all votes</li>
                        <li>Remove all candidates</li>
                        <li>Reset voter voting statuses</li>
                        <li>Clear all winners</li>
                    </ul>
                    <p>Only voting positions will remain.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="post">
                        <button type="submit" name="reset_voting" class="btn btn-danger">Confirm Reset</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>