<?php
session_start();
include 'includes/db.php';
include 'includes/functions.php';

if (!is_voter()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$voter_id = $_SESSION['user_id'] ?? 0;
$has_voted = $_SESSION['has_voted'] ?? false;

if ($has_voted) {
    echo json_encode(['success' => false, 'error' => 'You have already voted']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (empty($_POST['voter_id'])) {
    echo json_encode(['success' => false, 'error' => 'Voter ID missing']);
    exit;
}

if (empty($_POST['votes']) || !is_array($_POST['votes'])) {
    echo json_encode(['success' => false, 'error' => 'No votes selected']);
    exit;
}

$voter_id = intval($_POST['voter_id']);
$receipt_positions = [];

// Begin transaction
mysqli_begin_transaction($conn);

try {
    // Clear all previous votes for this voter (optional)
    $delete_sql = "DELETE FROM votes WHERE voter_id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, "i", $voter_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    foreach ($_POST['votes'] as $position_id => $selected) {
        $position_id = intval($position_id);

        // Get position info
        $pos_sql = "SELECT position_name, max_winners FROM positions WHERE position_id = ?";
        $stmt = mysqli_prepare($conn, $pos_sql);
        mysqli_stmt_bind_param($stmt, "i", $position_id);
        mysqli_stmt_execute($stmt);
        $pos_res = mysqli_stmt_get_result($stmt);

        if (!$pos_res || mysqli_num_rows($pos_res) === 0) {
            throw new Exception("Invalid position ID: $position_id");
        }

        $pos = mysqli_fetch_assoc($pos_res);
        $max_winners = $pos['max_winners'];
        $position_name = $pos['position_name'];
        mysqli_stmt_close($stmt);

        // Normalize input - handle both single and multiple selections
        $candidate_ids = is_array($selected) ? array_map('intval', $selected) : [intval($selected)];

        // Validate number of selections
        if (count($candidate_ids) === 0) {
            throw new Exception("Please select at least one candidate for $position_name");
        }

        if (count($candidate_ids) > $max_winners) {
            throw new Exception("You can only select up to $max_winners candidate(s) for $position_name");
        }

        // Process each vote
        foreach ($candidate_ids as $candidate_id) {
            // Verify candidate exists for this position
            $check_sql = "SELECT * FROM candidates WHERE candidate_id = ? AND position_id = ?";
            $stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($stmt, "ii", $candidate_id, $position_id);
            mysqli_stmt_execute($stmt);
            $check_result = mysqli_stmt_get_result($stmt);

            if (!$check_result || mysqli_num_rows($check_result) === 0) {
                mysqli_stmt_close($stmt);
                throw new Exception("Invalid candidate selected for $position_name");
            }
            mysqli_stmt_close($stmt);

            // Insert vote
            $vote_sql = "INSERT INTO votes (voter_id, candidate_id, position_id) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $vote_sql);
            mysqli_stmt_bind_param($stmt, "iii", $voter_id, $candidate_id, $position_id);

            if (!mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                throw new Exception("Database error: " . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);

            // Store for receipt
            $receipt_positions[$position_name][] = $candidate_id;
        }
    }

    // Mark voter as has_voted
    $update_sql = "UPDATE users SET has_voted = 1 WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "i", $voter_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $_SESSION['has_voted'] = true;

    // Build receipt data
    $receipt_data = [];
    foreach ($receipt_positions as $pos_name => $cand_ids) {
        $receipt_data[$pos_name] = [];

        foreach ($cand_ids as $cid) {
            $cand_sql = "SELECT full_name, photo FROM candidates WHERE candidate_id = ?";
            $stmt = mysqli_prepare($conn, $cand_sql);
            mysqli_stmt_bind_param($stmt, "i", $cid);
            mysqli_stmt_execute($stmt);
            $cand_result = mysqli_stmt_get_result($stmt);
            $cand_row = mysqli_fetch_assoc($cand_result);
            mysqli_stmt_close($stmt);

            $receipt_data[$pos_name][] = [
                'full_name' => $cand_row['full_name'],
                'photo' => $cand_row['photo']
            ];
        }
    }

    // Commit transaction
    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'positions' => $receipt_data
    ]);
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}
?>