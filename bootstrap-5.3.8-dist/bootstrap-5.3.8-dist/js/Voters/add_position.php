<?php
session_start();
include 'includes/functions.php';
if (!is_admin()) {
    header("Location: index.php");
    exit();
}

include 'includes/db.php';

// Handle POST requests for adding, editing, and deleting
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $name = mysqli_real_escape_string($conn, trim($_POST['position_name']));
            $max_winners = (int)$_POST['max_winners'];
            if (empty($name)) {
                $_SESSION['error_message'] = "Position name is required.";
            } else {
                $checkQuery = "SELECT * FROM positions WHERE position_name = '$name'";
                $result = mysqli_query($conn, $checkQuery);
                if (mysqli_num_rows($result) > 0) {
                    $_SESSION['error_message'] = "Position <strong>'$name'</strong> already exists!";
                } else {
                    $sql = "INSERT INTO positions (position_name, max_winners) VALUES ('$name', $max_winners)";
                    if (mysqli_query($conn, $sql)) {
                        $_SESSION['success_message'] = "Position added successfully!";
                    } else {
                        $_SESSION['error_message'] = "Failed to add position: " . mysqli_error($conn);
                    }
                }
            }
        } elseif ($_POST['action'] == 'edit') {
            $position_id = (int)$_POST['position_id'];
            $name = mysqli_real_escape_string($conn, trim($_POST['position_name']));
            $max_winners = (int)$_POST['max_winners'];
            if (empty($name)) {
                $_SESSION['error_message'] = "Position name is required.";
            } else {
                $checkQuery = "SELECT * FROM positions WHERE position_name = '$name' AND position_id != $position_id";
                $result = mysqli_query($conn, $checkQuery);
                if (mysqli_num_rows($result) > 0) {
                    $_SESSION['error_message'] = "Position <strong>'$name'</strong> already exists!";
                } else {
                    $sql = "UPDATE positions SET position_name = '$name', max_winners = $max_winners WHERE position_id = $position_id";
                    if (mysqli_query($conn, $sql)) {
                        $_SESSION['success_message'] = "Position updated successfully!";
                    } else {
                        $_SESSION['error_message'] = "Failed to update position: " . mysqli_error($conn);
                    }
                }
            }
        } elseif ($_POST['action'] == 'delete') {
            $position_id = (int)$_POST['position_id'];
            // Check for associated candidates
            $checkCandidates = mysqli_query($conn, "SELECT * FROM candidates WHERE position_id = $position_id");
            if (mysqli_num_rows($checkCandidates) > 0) {
                $_SESSION['error_message'] = "Cannot delete position with associated candidates.";
            } else {
                $sql = "DELETE FROM positions WHERE position_id = $position_id";
                if (mysqli_query($conn, $sql)) {
                    $_SESSION['success_message'] = "Position deleted successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to delete position: " . mysqli_error($conn);
                }
            }
        }
    }
    header("Location: add_position.php");
    exit();
}

// Fetch all positions for the table
$query = "SELECT * FROM positions ORDER BY position_name";
$positionsResult = mysqli_query($conn, $query);
if (!$positionsResult) {
    $_SESSION['error_message'] = "Error fetching positions: " . mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Position</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .table-responsive {
            overflow-x: auto;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .table .btn {
            white-space: nowrap;
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
        tr:hover {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button18:43:23 2025-06-12T22:43:23Z"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Add/Edit Position Button -->
        <div class="mb-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#positionModal" 
                    onclick="setModalMode('add')">Add New Position</button>
            <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <!-- Positions Table -->
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Existing Positions</h4>
            </div>
            <div class="card-body">
                <?php if ($positionsResult && mysqli_num_rows($positionsResult) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Position Name</th>
                                    <th>Maximum Winners</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($positionsResult)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['position_name']) ?></td>
                                        <td><?= htmlspecialchars($row['max_winners']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning edit-position" 
                                                    data-id="<?= $row['position_id'] ?>" 
                                                    data-name="<?= htmlspecialchars($row['position_name']) ?>" 
                                                    data-max-winners="<?= $row['max_winners'] ?>"
                                                    data-bs-toggle="modal" data-bs-target="#positionModal">Edit</button>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this position?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="position_id" value="<?= $row['position_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No positions found. Use the button above to add a new position.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Position Modal (Add/Edit) -->
    <div class="modal fade" id="positionModal" tabindex="-1" aria-labelledby="positionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="positionModalLabel">Add Position</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" id="positionForm" novalidate>
                        <input type="hidden" name="action" id="modal_action" value="add">
                        <input type="hidden" name="position_id" id="position_id">
                        <div class="mb-3">
                            <label for="position_name" class="form-label">Position Name</label>
                            <input type="text" class="form-control" id="position_name" name="position_name" required>
                            <div class="invalid-feedback">Please enter a position name.</div>
                        </div>
                        <div class="mb-3">
                            <label for="max_winners" class="form-label">Maximum Winners</label>
                            <input type="number" class="form-control" id="max_winners" name="max_winners" min="1" required>
                            <div class="invalid-feedback">Please enter a valid number (minimum 1).</div>
                        </div>
                        <button type="submit" class="btn btn-primary" id="submitButton">Add Position</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.edit-position');
            const modal = new bootstrap.Modal(document.getElementById('positionModal'));
            const modalTitle = document.getElementById('positionModalLabel');
            const modalAction = document.getElementById('modal_action');
            const positionIdInput = document.getElementById('position_id');
            const nameInput = document.getElementById('position_name');
            const maxWinnersInput = document.getElementById('max_winners');
            const submitButton = document.getElementById('submitButton');
            const form = document.getElementById('positionForm');

            function setModalMode(mode, id = '', name = '', maxWinners = '') {
                form.classList.remove('was-validated');
                nameInput.classList.remove('is-invalid');
                maxWinnersInput.classList.remove('is-invalid');

                if (mode === 'add') {
                    modalTitle.textContent = 'Add Position';
                    modalAction.value = 'add';
                    positionIdInput.value = '';
                    nameInput.value = '';
                    maxWinnersInput.value = '';
                    submitButton.textContent = 'Add Position';
                } else {
                    modalTitle.textContent = 'Edit Position';
                    modalAction.value = 'edit';
                    positionIdInput.value = id;
                    nameInput.value = name;
                    maxWinnersInput.value = maxWinners;
                    submitButton.textContent = 'Save Changes';
                }
            }

            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const maxWinners = this.getAttribute('data-max-winners');
                    setModalMode('edit', id, name, maxWinners);
                    modal.show();
                });
            });

            // Reset form when modal is closed
            document.querySelector('#positionModal').addEventListener('hidden.bs.modal', function() {
                form.classList.remove('was-validated');
                nameInput.classList.remove('is-invalid');
                maxWinnersInput.classList.remove('is-invalid');
                nameInput.value = '';
                maxWinnersInput.value = '';
                modalAction.value = 'add';
                modalTitle.textContent = 'Add Position';
                submitButton.textContent = 'Add Position';
                submitButton.disabled = false;
                submitButton.textContent = modalAction.value === 'add' ? 'Add Position' : 'Save Changes';
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