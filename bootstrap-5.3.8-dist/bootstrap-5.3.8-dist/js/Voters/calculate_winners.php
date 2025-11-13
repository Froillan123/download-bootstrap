<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'includes/db.php';
include 'includes/functions.php';

// Set timezone
date_default_timezone_set('Asia/Manila');

// Only admin can manually trigger this
if (!is_admin()) {
    $_SESSION['error_message'] = "Unauthorized access!";
    header("Location: index.php");
    exit();
}

// Get voting end time
$settings = [];
$res = mysqli_query($conn, "SELECT * FROM settings");
while ($row = mysqli_fetch_assoc($res)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$voting_end = strtotime($settings['voting_end'] ?? '');
$current_time = time();

// Check if voting has ended (allow admin to force calculation if needed)
if ($current_time > $voting_end || (is_admin() && isset($_POST['force']))) {
    // Calculate winners for each position
    $positions = [];
    $pos_query = mysqli_query($conn, "SELECT * FROM positions ORDER BY position_id");
    while ($position = mysqli_fetch_assoc($pos_query)) {
        $pos_id = $position['position_id'];
        $max_winners = $position['max_winners'];
        
        // Get candidates with most votes for this position
        $winners_query = mysqli_query($conn, 
            "SELECT c.candidate_id, c.full_name, COUNT(v.vote_id) as vote_count 
             FROM candidates c 
             LEFT JOIN votes v ON c.candidate_id = v.candidate_id 
             WHERE c.position_id = $pos_id 
             GROUP BY c.candidate_id 
             ORDER BY vote_count DESC 
             LIMIT $max_winners");
        
        $winners = [];
        while ($winner = mysqli_fetch_assoc($winners_query)) {
            $winners[] = $winner;
        }
        
        $positions[$pos_id] = [
            'position_name' => $position['position_name'],
            'winners' => $winners
        ];
    }
    
    // Store winners in database
    foreach ($positions as $pos_id => $data) {
        $position_name = mysqli_real_escape_string($conn, $data['position_name']);
        
        // Clear previous winners for this position
        mysqli_query($conn, "DELETE FROM winners WHERE position_id = $pos_id");
        
        // Insert new winners
        foreach ($data['winners'] as $winner) {
            $candidate_id = $winner['candidate_id'];
            $vote_count = $winner['vote_count'];
            $full_name = mysqli_real_escape_string($conn, $winner['full_name']);
            
            mysqli_query($conn, 
                "INSERT INTO winners (position_id, position_name, candidate_id, candidate_name, vote_count) 
                 VALUES ($pos_id, '$position_name', $candidate_id, '$full_name', $vote_count)");
        }
    }
    
    // Update voting status to inactive
    mysqli_query($conn, "UPDATE settings SET setting_value='inactive' WHERE setting_key='voting_status'");
    
    // Log the calculation
    file_put_contents('winner_calculation.log', 
        "[" . date('Y-m-d H:i:s') . "] Winners calculated for " . count($positions) . " positions\n", 
        FILE_APPEND);
    
    $_SESSION['success_message'] = "Winners calculated successfully!";
} else {
    $_SESSION['error_message'] = "Cannot calculate winners before voting ends!";
}

header("Location: winners.php");
exit();
?>