<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'includes/db.php';
include 'includes/functions.php';

// Set timezone
date_default_timezone_set('Asia/Manila');

// Get voting period settings
$settings = [];
$res = mysqli_query($conn, "SELECT * FROM settings");
while ($row = mysqli_fetch_assoc($res)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$current_time = time();
$voting_end = strtotime($settings['voting_end'] ?? '');
$voting_status = $settings['voting_status'] ?? 'inactive';

// Check if voting has ended but winners haven't been calculated
$winners_exist = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM winners LIMIT 1")) > 0;

if ($current_time > $voting_end && !$winners_exist && $voting_status !== 'results_calculated') {
    // Automatically calculate winners
    include 'calculate_winners.php';
    exit();
}

// Only admin can see results before voting ends
if (!is_admin() && $current_time < $voting_end) {
    $_SESSION['error_message'] = "Results are not available until voting ends!";
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .winner-badge {
            background-color: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            margin-left: 10px;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .position-header {
            background-color: #f8f9fa;
            padding: 10px;
            margin-top: 20px;
            border-radius: 5px;
        }
        .winner-row {
            background-color: #e8f5e9;
        }
    </style>
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
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Voting Results</h4>
            </div>
            <div class="card-body">
                <?php
                // Check again in case we just calculated
                $winners_exist = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM winners LIMIT 1")) > 0;
                
                if ($winners_exist) {
                    // Display winners by position
                    $positions = mysqli_query($conn, "SELECT DISTINCT position_id, position_name FROM winners ORDER BY position_id");
                    while ($position = mysqli_fetch_assoc($positions)) {
                        echo '<div class="position-header">';
                        echo '<h5>' . htmlspecialchars($position['position_name']) . '</h5>';
                        echo '</div>';
                        
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-striped table-hover">';
                        echo '<thead class="table-dark">';
                        echo '<tr><th>Rank</th><th>Candidate</th><th>Votes</th><th>Status</th></tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        
                        // Get all candidates for this position with their vote counts
                        $candidates = mysqli_query($conn, 
                            "SELECT c.candidate_id, c.full_name, COUNT(v.vote_id) as vote_count, 
                             (SELECT COUNT(*) FROM winners w WHERE w.candidate_id = c.candidate_id AND w.position_id = {$position['position_id']}) as is_winner
                             FROM candidates c 
                             LEFT JOIN votes v ON c.candidate_id = v.candidate_id 
                             WHERE c.position_id = {$position['position_id']}
                             GROUP BY c.candidate_id 
                             ORDER BY vote_count DESC");
                        
                        $rank = 1;
                        while ($candidate = mysqli_fetch_assoc($candidates)) {
                            $row_class = $candidate['is_winner'] ? 'winner-row' : '';
                            echo '<tr class="' . $row_class . '">';
                            echo '<td>' . $rank++ . '</td>';
                            echo '<td>' . htmlspecialchars($candidate['full_name']) . '</td>';
                            echo '<td>' . $candidate['vote_count'] . '</td>';
                            if ($candidate['is_winner']) {
                                echo '<td><span class="winner-badge">Winner</span></td>';
                            } else {
                                echo '<td></td>';
                            }
                            echo '</tr>';
                        }
                        
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="alert alert-info">';
                    if ($current_time < $voting_end) {
                        echo '<h5>Voting is still in progress</h5>';
                        echo '<p>Results will be available after voting ends on ' . date('F j, Y H:i', $voting_end) . '</p>';
                    } else {
                        echo '<h5>Results are being calculated</h5>';
                        echo '<p>The voting results are not yet available. Please check back later.</p>';
                        
                        // If admin, show option to manually calculate results
                        if (is_admin()) {
                            echo '<form action="calculate_winners.php" method="post">';
                            echo '<button type="submit" class="btn btn-primary">Calculate Winners Now</button>';
                            echo '</form>';
                        }
                    }
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>