<?php
session_start();
include 'includes/functions.php';
if (!is_admin()) header("Location: index.php");

include 'includes/db.php';

// Handle POST requests for adding, editing, and deleting
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $success = false;
        if ($_POST['action'] == 'add') {
            $name = mysqli_real_escape_string($conn, $_POST['full_name']);
            $position_id = (int)$_POST['position_id'];
            $sql = "INSERT INTO candidates (full_name, position_id) VALUES ('$name', $position_id)";
            if (mysqli_query($conn, $sql)) {
                $_SESSION['success_message'] = "Candidate added successfully!";
                $success = true;
            } else {
                $_SESSION['error_message'] = "Failed to add candidate: " . mysqli_error($conn);
            }
        } elseif ($_POST['action'] == 'edit') {
            $candidate_id = (int)$_POST['candidate_id'];
            $name = mysqli_real_escape_string($conn, $_POST['full_name']);
            $position_id = (int)$_POST['position_id'];
            $sql = "UPDATE candidates SET full_name = '$name', position_id = $position_id WHERE candidate_id = $candidate_id";
            if (mysqli_query($conn, $sql)) {
                $_SESSION['success_message'] = "Candidate updated successfully!";
                $success = true;
            } else {
                $_SESSION['error_message'] = "Failed to update candidate: " . mysqli_error($conn);
            }
        } elseif ($_POST['action'] == 'delete') {
            $candidate_id = (int)$_POST['candidate_id'];
            $sql = "DELETE FROM candidates WHERE candidate_id = $candidate_id";
            if (mysqli_query($conn, $sql)) {
                $_SESSION['success_message'] = "Candidate deleted successfully!";
                $success = true;
            } else {
                $_SESSION['error_message'] = "Failed to delete candidate: " . mysqli_error($conn);
            }
        }
        header("Location: add_candidate.php");
        exit();
    }
}

// Fetch positions ordered by position_id
$positions = mysqli_query($conn, "SELECT * FROM positions ORDER BY position_id ASC");
if (!$positions) {
    die("Error fetching positions: " . mysqli_error($conn));
}

// Fetch all candidates with position names
$candidates_query = "SELECT c.candidate_id, c.full_name, c.position_id, p.position_name 
                     FROM candidates c 
                     JOIN positions p ON c.position_id = p.position_id 
                     ORDER BY p.position_id, c.full_name";
$candidates = mysqli_query($conn, $candidates_query);
if (!$candidates) {
    die("Error fetching candidates: " . mysqli_error($conn));
}

// Check if view preference is set
$view_mode = isset($_GET['view']) && $_GET['view'] === 'table' ? 'table' : 'card';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Card View Styles */
        .positions-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            justify-content: start;
        }
        .position-card {
            flex: 1 1 300px;
            max-width: 350px;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
            max-height: 400px; /* Fixed maximum height */
        }
        .position-card-header {
            background-color: #f8f9fa;
            padding: 1rem;
            font-size: 1.25rem;
            font-weight: 500;
            border-bottom: 1px solid #dee2e6;
        }
        .position-card-body {
            padding: 1rem;
            overflow-y: auto; /* Make content scrollable */
            flex-grow: 1; /* Take up remaining space */
        }
        .candidate-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .candidate-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.2s;
        }
        .candidate-list li:hover {
            background-color: #f9f9f9;
        }
        .candidate-list li:last-child {
            border-bottom: none;
        }
        .no-candidates {
            color: #6c757d;
            font-style: italic;
        }
        .candidate-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        /* Table View Styles */
        .table-view {
            display: none;
        }
        .table-view table {
            width: 100%;
            border-collapse: collapse;
        }
        .table-view th, .table-view td {
            padding: 0.75rem;
            vertical-align: middle;
            border: 1px solid #dee2e6;
        }
        .table-view th {
            background-color: #f8f9fa;
            font-weight: 500;
        }
        .table-view tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .table-view tr:hover {
            background-color: #f1f1f1;
        }
        
        /* View Toggle */
        .view-toggle {
            position: absolute;
            top: 1rem;
            right: 1rem;
        }
        .view-toggle-btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        /* Modal Animation */
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
        
        /* Scrollbar styling for card body */
        .position-card-body::-webkit-scrollbar {
            width: 8px;
        }
        .position-card-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        .position-card-body::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        .position-card-body::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .position-card {
                flex: 1 1 100%;
                max-width: 100%;
            }
            .view-toggle {
                position: static;
                margin-bottom: 1rem;
                text-align: right;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4 position-relative">
        <!-- View Toggle Button -->
        <div class="view-toggle">
            <div class="btn-group" role="group">
                <a href="?view=card" class="btn btn-sm <?= $view_mode === 'card' ? 'btn-primary' : 'btn-outline-primary' ?> view-toggle-btn">Card View</a>
                <a href="?view=table" class="btn btn-sm <?= $view_mode === 'table' ? 'btn-primary' : 'btn-outline-primary' ?> view-toggle-btn">Table View</a>
            </div>
        </div>

        <!-- Success/Error Messages -->
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

        <!-- Add/Edit Candidate Button -->
        <div class="mb-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#candidateModal" 
                    onclick="setModalMode('add')">Add New Candidate</button>
            <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <!-- Card View -->
        <div class="<?= $view_mode === 'card' ? '' : 'd-none' ?>" id="cardView">
            <div class="positions-container">
                <?php 
                mysqli_data_seek($positions, 0);
                while ($position = mysqli_fetch_assoc($positions)): ?>
                    <div class="position-card">
                        <div class="position-card-header">
                            <?= htmlspecialchars($position['position_name']) ?>
                        </div>
                        <div class="position-card-body">
                            <ul class="candidate-list">
                                <?php
                                $pos_id = $position['position_id'];
                                $pos_candidates = mysqli_query($conn, "SELECT candidate_id, full_name, position_id FROM candidates WHERE position_id = $pos_id");
                                if (mysqli_num_rows($pos_candidates) > 0) {
                                    while ($candidate = mysqli_fetch_assoc($pos_candidates)) {
                                        echo '<li>';
                                        echo htmlspecialchars($candidate['full_name']);
                                        echo '<div class="candidate-actions">';
                                        echo '<button class="btn btn-sm btn-warning edit-candidate" 
                                                data-id="' . $candidate['candidate_id'] . '" 
                                                data-name="' . htmlspecialchars($candidate['full_name']) . '" 
                                                data-position="' . $candidate['position_id'] . '"
                                                data-bs-toggle="modal" data-bs-target="#candidateModal">Edit</button>';
                                        echo '<form method="post" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this candidate?\');">';
                                        echo '<input type="hidden" name="action" value="delete">';
                                        echo '<input type="hidden" name="candidate_id" value="' . $candidate['candidate_id'] . '">';
                                        echo '<button type="submit" class="btn btn-sm btn-danger">Delete</button>';
                                        echo '</form>';
                                        echo '</div>';
                                        echo '</li>';
                                    }
                                } else {
                                    echo '<li class="no-candidates">No candidates</li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Table View -->
        <div class="<?= $view_mode === 'table' ? '' : 'd-none' ?>" id="tableView">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Candidate Name</th>
                            <th>Position</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($candidates) > 0): ?>
                            <?php while ($candidate = mysqli_fetch_assoc($candidates)): ?>
                                <tr>
                                    <td><?= $candidate['candidate_id'] ?></td>
                                    <td><?= htmlspecialchars($candidate['full_name']) ?></td>
                                    <td><?= htmlspecialchars($candidate['position_name']) ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-warning edit-candidate" 
                                                    data-id="<?= $candidate['candidate_id'] ?>" 
                                                    data-name="<?= htmlspecialchars($candidate['full_name']) ?>" 
                                                    data-position="<?= $candidate['position_id'] ?>"
                                                    data-bs-toggle="modal" data-bs-target="#candidateModal">Edit</button>
                                            <form method="post" onsubmit="return confirm('Are you sure you want to delete this candidate?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="candidate_id" value="<?= $candidate['candidate_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No candidates found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Candidate Modal (Add/Edit) -->
    <div class="modal fade" id="candidateModal" tabindex="-1" aria-labelledby="candidateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="candidateModalLabel">Add Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" id="candidateForm" novalidate>
                        <input type="hidden" name="action" id="modal_action" value="add">
                        <input type="hidden" name="candidate_id" id="candidate_id">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Candidate Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                            <div class="invalid-feedback">Please enter a candidate name.</div>
                        </div>
                        <div class="mb-3">
                            <label for="position_id" class="form-label">Position</label>
                            <select class="form-select" id="position_id" name="position_id" required>
                                <option value="">Select Position</option>
                                <?php 
                                mysqli_data_seek($positions, 0);
                                while ($p = mysqli_fetch_assoc($positions)): ?>
                                    <option value="<?= $p['position_id'] ?>"><?= htmlspecialchars($p['position_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <div class="invalid-feedback">Please select a position.</div>
                        </div>
                        <button type="submit" class="btn btn-primary" id="submitButton">Add Candidate</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.edit-candidate');
            const modal = new bootstrap.Modal(document.getElementById('candidateModal'));
            const modalTitle = document.getElementById('candidateModalLabel');
            const modalAction = document.getElementById('modal_action');
            const candidateIdInput = document.getElementById('candidate_id');
            const nameInput = document.getElementById('full_name');
            const positionSelect = document.getElementById('position_id');
            const submitButton = document.getElementById('submitButton');
            const form = document.getElementById('candidateForm');

            function setModalMode(mode, id = '', name = '', position = '') {
                // Reset form validation
                form.classList.remove('was-validated');
                nameInput.classList.remove('is-invalid');
                positionSelect.classList.remove('is-invalid');

                if (mode === 'add') {
                    modalTitle.textContent = 'Add Candidate';
                    modalAction.value = 'add';
                    candidateIdInput.value = '';
                    nameInput.value = '';
                    positionSelect.value = '';
                    submitButton.textContent = 'Add Candidate';
                } else {
                    modalTitle.textContent = 'Edit Candidate';
                    modalAction.value = 'edit';
                    candidateIdInput.value = id;
                    nameInput.value = name;
                    positionSelect.value = position;
                    submitButton.textContent = 'Save Changes';
                }
            }

            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const position = this.getAttribute('data-position');
                    setModalMode('edit', id, name, position);
                    modal.show();
                });
            });

            // Reset form when modal is closed
            document.querySelector('#candidateModal').addEventListener('hidden.bs.modal', function() {
                form.classList.remove('was-validated');
                nameInput.classList.remove('is-invalid');
                positionSelect.classList.remove('is-invalid');
                nameInput.value = '';
                positionSelect.value = '';
                modalAction.value = 'add';
                modalTitle.textContent = 'Add Candidate';
                submitButton.textContent = 'Add Candidate';
                candidateIdInput.value = '';
                submitButton.disabled = false;
                submitButton.textContent = modalAction.value === 'add' ? 'Add Candidate' : 'Save Changes';
            });

            // Client-side form validation and loading state
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    form.classList.add('was-validated');
                } else {
                    submitButton.disabled = true;
                    submitButton.textContent = modalAction.value === 'add' ? 'Adding...' : 'Saving...';
                }
            });
        });
    </script>
</body>
</html>