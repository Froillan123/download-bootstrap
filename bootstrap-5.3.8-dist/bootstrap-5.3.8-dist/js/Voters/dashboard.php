<?php
session_start();
include 'includes/db.php';
include 'includes/functions.php';

// Set correct timezone (Asia/Manila for Philippines)
date_default_timezone_set('Asia/Manila');

if (!is_voter()) redirect('index.php');

// Get voting period settings
$settings = [];
$res = mysqli_query($conn, "SELECT * FROM settings");
while ($row = mysqli_fetch_assoc($res)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$current_time = time();
$voting_start = strtotime($settings['voting_start'] ?? '');
$voting_end = strtotime($settings['voting_end'] ?? '');
$voting_status = $settings['voting_status'] ?? 'inactive';

// Auto-activate voting if within time range (override admin setting)
if ($current_time >= $voting_start && $current_time <= $voting_end && $voting_status !== 'active') {
    mysqli_query($conn, "UPDATE settings SET setting_value='active' WHERE setting_key='voting_status'");
    $voting_status = 'active';
}

// Check if voting is allowed
$voting_allowed = ($voting_status === 'active') && 
                 ($current_time >= $voting_start) && 
                 ($current_time <= $voting_end);

$voter_id = $_SESSION['user_id'] ?? 0;
$has_voted = $_SESSION['has_voted'] ?? false;
$voter_name = $_SESSION['full_name'] ?? 'Unknown Voter';

// Fetch voter name from database if not set
if (!isset($_SESSION['full_name']) && $voter_id) {
    $sql = "SELECT full_name FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $voter_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $voter_name = htmlspecialchars($row['full_name']);
        $_SESSION['full_name'] = $voter_name;
    }
    mysqli_stmt_close($stmt);
}

if ($has_voted) {
    // Fetch vote receipt data for display
    $receipt_data = [];
    $sql = "SELECT p.position_name, p.max_winners, c.full_name, c.photo 
            FROM votes v 
            JOIN candidates c ON v.candidate_id = c.candidate_id 
            JOIN positions p ON v.position_id = p.position_id 
            WHERE v.voter_id = ? 
            ORDER BY p.position_id";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $voter_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $receipt_data[$row['position_name']][] = [
            'full_name' => $row['full_name'],
            'photo' => $row['photo']
        ];
    }
    mysqli_stmt_close($stmt);
} elseif ($voting_allowed) {
    // Only fetch positions if voting is allowed
    $sql = "SELECT * FROM positions ORDER BY position_id";
    $result = mysqli_query($conn, $sql);
    $positions = [];
    if ($result) {
        while ($position = mysqli_fetch_assoc($result)) {
            $pos_id = $position['position_id'];
            $candidates_sql = "SELECT * FROM candidates WHERE position_id = ?";
            $stmt = mysqli_prepare($conn, $candidates_sql);
            mysqli_stmt_bind_param($stmt, "i", $pos_id);
            mysqli_stmt_execute($stmt);
            $candidates_result = mysqli_stmt_get_result($stmt);
            $candidates = [];
            while ($c = mysqli_fetch_assoc($candidates_result)) {
                $candidates[] = $c;
            }
            $position['candidates'] = $candidates;
            $positions[] = $position;
            mysqli_stmt_close($stmt);
        }
    } else {
        $_SESSION['error_message'] = "Error fetching positions: " . htmlspecialchars(mysqli_error($conn));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .candidate-photo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 10px;
        }
        .form-check {
            margin-bottom: 10px;
        }
        .modal-content {
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .btn-primary:disabled {
            cursor: not-allowed;
            opacity: 0.65;
        }
        .candidate-list li {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .position-section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .max-selection {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        .voting-period-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar_voter.php'; ?>
    
    <div class="container mt-4">
        <!-- Error Messages -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><?= $has_voted ? 'Your Vote Receipt' : 'Cast Your Vote' ?></h4>
            </div>
            <div class="card-body">
                <?php if ($has_voted): ?>
                    <!-- Display Receipt -->
                    <div class="receipt-view">
                        <p><strong>Voter:</strong> <?= htmlspecialchars($voter_name) ?></p>
                        <?php foreach ($receipt_data as $position_name => $candidates): ?>
                            <div class="position-section">
                                <h5><?= htmlspecialchars($position_name) ?></h5>
                                <p><strong>Voted Candidate(s):</strong></p>
                                <ul class="candidate-list">
                                    <?php foreach ($candidates as $candidate): ?>
                                        <li>
                                            <?php if (!empty($candidate['photo'])): ?>
                                                <img src="<?= htmlspecialchars($candidate['photo']) ?>" alt="Photo" class="candidate-photo">
                                            <?php endif; ?>
                                            <?= htmlspecialchars($candidate['full_name']) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                        <p><strong>Timestamp:</strong> <?= date('Y-m-d H:i:s') ?></p>
                    </div>
                <?php elseif ($voting_allowed): ?>
                    <!-- Voting Form -->
                    <div class="voting-period-info">
                        <h5>Voting Period</h5>
                        <p>Voting is currently active from <?= date('F j, Y H:i', $voting_start) ?> to <?= date('F j, Y H:i', $voting_end) ?></p>
                    </div>
                    
                    <form action="process_vote.php" method="post" id="vote-form" novalidate>
                        <?php foreach ($positions as $position): ?>
                            <?php if (count($position['candidates']) > 0): ?>
                                <div class="position-section">
                                    <h5><?= htmlspecialchars($position['position_name']) ?></h5>
                                    <div class="max-selection">Select up to <?= $position['max_winners'] ?> candidate(s)</div>
                                    
                                    <?php if ($position['max_winners'] == 1): ?>
                                        <select class="form-select" name="votes[<?= $position['position_id'] ?>]" required>
                                            <option value="">Select Candidate</option>
                                            <?php foreach ($position['candidates'] as $c): ?>
                                                <option value="<?= $c['candidate_id'] ?>"><?= htmlspecialchars($c['full_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Please select a candidate.</div>
                                    <?php else: ?>
                                        <div class="mb-3">
                                            <?php foreach ($position['candidates'] as $c): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input candidate-checkbox" type="checkbox"
                                                        name="votes[<?= $position['position_id'] ?>][]"
                                                        value="<?= $c['candidate_id'] ?>"
                                                        id="candidate_<?= $c['candidate_id'] ?>"
                                                        data-position="<?= $position['position_id'] ?>"
                                                        data-max="<?= $position['max_winners'] ?>">
                                                    <label class="form-check-label" for="candidate_<?= $c['candidate_id'] ?>">
                                                        <?= htmlspecialchars($c['full_name']) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                            <div class="invalid-feedback" id="checkbox-error-<?= $position['position_id'] ?>">
                                                Please select up to <?= $position['max_winners'] ?> candidate(s).
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <input type="hidden" name="voter_id" value="<?= $voter_id ?>">
                        <button type="submit" class="btn btn-primary vote-btn">Submit All Votes</button>
                    </form>
                <?php else: ?>
                    <!-- Voting not allowed message -->
                    <div class="alert alert-info">
                        <?php if ($voting_status === 'inactive'): ?>
                            <h5>Voting is currently inactive</h5>
                            <p>The administrator has disabled voting at this time.</p>
                        <?php elseif ($current_time < $voting_start): ?>
                            <h5>Voting has not started yet</h5>
                            <p>Voting will begin on <?= date('F j, Y H:i', $voting_start) ?></p>
                        <?php else: ?>
                            <h5>Voting has ended</h5>
                            <p>Voting period was from <?= date('F j, Y H:i', $voting_start) ?> to <?= date('F j, Y H:i', $voting_end) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptModalLabel">Vote Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Voter:</strong> <?= htmlspecialchars($voter_name) ?></p>
                    <div id="receipt-positions"></div>
                    <p><strong>Timestamp:</strong> <span id="receipt-timestamp"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="window.location.href='dashboard.php'">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('vote-form');
        const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));

        if (form) {
            // Handle checkbox selection limits
            document.querySelectorAll('.candidate-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const positionId = this.dataset.position;
                    const max = parseInt(this.dataset.max);
                    const checkboxes = document.querySelectorAll(`input[name="votes[${positionId}][]"]:checked`);
                    const errorElement = document.getElementById(`checkbox-error-${positionId}`);
                    
                    if (checkboxes.length > max) {
                        this.checked = false;
                        errorElement.style.display = 'block';
                        errorElement.textContent = `You can only select up to ${max} candidates for this position.`;
                    } else {
                        errorElement.style.display = 'none';
                    }
                });
            });

            const submitButton = form.querySelector('.vote-btn');

            form.addEventListener('submit', function (event) {
                let valid = true;
                form.classList.add('was-validated');

                // Validate each position
                <?php foreach ($positions as $position): ?>
                    <?php if (count($position['candidates']) > 0): ?>
                        const maxWinners<?= $position['position_id'] ?> = <?= $position['max_winners'] ?>;
                        <?php if ($position['max_winners'] == 1): ?>
                            // Single selection validation
                            const select<?= $position['position_id'] ?> = form.querySelector('select[name="votes[<?= $position['position_id'] ?>]"]');
                            if (!select<?= $position['position_id'] ?>.value) {
                                valid = false;
                                select<?= $position['position_id'] ?>.classList.add('is-invalid');
                            } else {
                                select<?= $position['position_id'] ?>.classList.remove('is-invalid');
                            }
                        <?php else: ?>
                            // Multiple selection validation
                            const checkboxes<?= $position['position_id'] ?> = form.querySelectorAll('input[name="votes[<?= $position['position_id'] ?>][]"]:checked');
                            const error<?= $position['position_id'] ?> = form.querySelector('#checkbox-error-<?= $position['position_id'] ?>');
                            if (checkboxes<?= $position['position_id'] ?>.length === 0 || checkboxes<?= $position['position_id'] ?>.length > maxWinners<?= $position['position_id'] ?>) {
                                valid = false;
                                error<?= $position['position_id'] ?>.style.display = 'block';
                                error<?= $position['position_id'] ?>.textContent = checkboxes<?= $position['position_id'] ?>.length === 0 
                                    ? 'Please select at least one candidate.' 
                                    : `You can only select up to ${maxWinners<?= $position['position_id'] ?>} candidates.`;
                            } else {
                                error<?= $position['position_id'] ?>.style.display = 'none';
                            }
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>

                if (!valid) {
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }

                submitButton.disabled = true;
                submitButton.textContent = 'Submitting...';

                const formData = new FormData(form);
                
                fetch('process_vote.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const positionsDiv = document.getElementById('receipt-positions');
                        positionsDiv.innerHTML = '';
                        
                        for (const [position, candidates] of Object.entries(data.positions)) {
                            const positionDiv = document.createElement('div');
                            positionDiv.className = 'position-section';
                            positionDiv.innerHTML = `<h5>${position}</h5><p><strong>Voted Candidate(s):</strong></p>`;
                            
                            const ul = document.createElement('ul');
                            ul.className = 'candidate-list';
                            
                            candidates.forEach(candidate => {
                                const li = document.createElement('li');
                                if (candidate.photo) {
                                    const img = document.createElement('img');
                                    img.src = candidate.photo;
                                    img.className = 'candidate-photo';
                                    li.appendChild(img);
                                }
                                li.appendChild(document.createTextNode(candidate.full_name));
                                ul.appendChild(li);
                            });
                            
                            positionDiv.appendChild(ul);
                            positionsDiv.appendChild(positionDiv);
                        }
                        
                        document.getElementById('receipt-timestamp').textContent = new Date().toLocaleString();
                        receiptModal.show();
                    } else {
                        alert(data.error || 'An error occurred while submitting your vote.');
                        submitButton.disabled = false;
                        submitButton.textContent = 'Submit All Votes';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while submitting your vote.');
                    submitButton.disabled = false;
                    submitButton.textContent = 'Submit All Votes';
                });

                event.preventDefault();
            });
        }
    });
    </script>
</body>
</html>