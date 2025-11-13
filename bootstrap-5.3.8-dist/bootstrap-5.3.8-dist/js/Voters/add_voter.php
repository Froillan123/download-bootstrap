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
            $name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
            $email = mysqli_real_escape_string($conn, trim($_POST['email']));
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            if (empty($name) || empty($email) || empty($_POST['password'])) {
                $_SESSION['error_message'] = "All fields are required.";
            } else {
                $checkQuery = "SELECT * FROM users WHERE email = '$email'";
                $result = mysqli_query($conn, $checkQuery);
                if (mysqli_num_rows($result) > 0) {
                    $_SESSION['error_message'] = "Email <strong>'$email'</strong> already exists!";
                } else {
                    $sql = "INSERT INTO users (full_name, email, password, role) VALUES ('$name', '$email', '$password', 'voter')";
                    if (mysqli_query($conn, $sql)) {
                        $_SESSION['success_message'] = "Voter added successfully!";
                    } else {
                        $_SESSION['error_message'] = "Failed to add voter: " . mysqli_error($conn);
                    }
                }
            }
        } elseif ($_POST['action'] == 'edit') {
            $user_id = (int)$_POST['user_id'];
            $name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
            $email = mysqli_real_escape_string($conn, trim($_POST['email']));
            $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
            if (empty($name) || empty($email)) {
                $_SESSION['error_message'] = "Name and email are required.";
            } else {
                $checkQuery = "SELECT * FROM users WHERE email = '$email' AND user_id != $user_id";
                $result = mysqli_query($conn, $checkQuery);
                if (mysqli_num_rows($result) > 0) {
                    $_SESSION['error_message'] = "Email <strong>'$email'</strong> already exists!";
                } else {
                    $sql = "UPDATE users SET full_name = '$name', email = '$email'";
                    if ($password) {
                        $sql .= ", password = '$password'";
                    }
                    $sql .= " WHERE user_id = $user_id AND role = 'voter'";
                    if (mysqli_query($conn, $sql)) {
                        $_SESSION['success_message'] = "Voter updated successfully!";
                    } else {
                        $_SESSION['error_message'] = "Failed to update voter: " . mysqli_error($conn);
                    }
                }
            }
        } elseif ($_POST['action'] == 'delete') {
            $user_id = (int)$_POST['user_id'];
            $sql = "DELETE FROM users WHERE user_id = $user_id AND role = 'voter'";
            if (mysqli_query($conn, $sql)) {
                $_SESSION['success_message'] = "Voter deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to delete voter: " . mysqli_error($conn);
            }
        }
    }
    header("Location: add_voter.php");
    exit();
}

// Pagination setup
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); // Ensure page is at least 1

// Calculate offset
$offset = ($page - 1) * $per_page;

// Get total number of voters
$count_query = "SELECT COUNT(*) as total FROM users WHERE role = 'voter'";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $per_page);

// Fetch voters for the table with pagination
$query = "SELECT user_id, full_name, email, has_voted, created_at FROM users WHERE role = 'voter' ORDER BY full_name LIMIT $per_page OFFSET $offset";
$votersResult = mysqli_query($conn, $query);
if (!$votersResult) {
    $_SESSION['error_message'] = "Error fetching voters: " . mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Voter</title>
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
        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .pagination .page-link {
            color: #0d6efd;
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
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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

        <!-- Add/Edit Voter Button -->
        <div class="mb-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#voterModal" 
                    onclick="setModalMode('add')">Add New Voter</button>
            <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <!-- Voters Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Voter List</h4>
                <div class="text-muted">
                    Showing <?= min($offset + 1, $total_rows) ?>-<?= min($offset + $per_page, $total_rows) ?> of <?= $total_rows ?> voters
                </div>
            </div>
            <div class="card-body">
                <?php if ($votersResult && mysqli_num_rows($votersResult) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Has Voted</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($votersResult)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td><?= $row['has_voted'] ? 'True' : 'False' ?></td>
                                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning edit-voter" 
                                                    data-id="<?= $row['user_id'] ?>" 
                                                    data-name="<?= htmlspecialchars($row['full_name']) ?>" 
                                                    data-email="<?= htmlspecialchars($row['email']) ?>"
                                                    data-bs-toggle="modal" data-bs-target="#voterModal">Edit</button>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this voter?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="add_voter.php?page=<?= $page - 1 ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-hidden="true">&laquo;</span>
                                </li>
                            <?php endif; ?>

                            <?php
                            // Show page numbers
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="add_voter.php?page=1">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                if ($i == $page) {
                                    echo '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
                                } else {
                                    echo '<li class="page-item"><a class="page-link" href="add_voter.php?page=' . $i . '">' . $i . '</a></li>';
                                }
                            }
                            
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="add_voter.php?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                            }
                            ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="add_voter.php?page=<?= $page + 1 ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-hidden="true">&raquo;</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php else: ?>
                    <p>No voters found. Use the button above to add a new voter.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Voter Modal (Add/Edit) -->
    <div class="modal fade" id="voterModal" tabindex="-1" aria-labelledby="voterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="voterModalLabel">Add Voter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" id="voterForm" novalidate>
                        <input type="hidden" name="action" id="modal_action" value="add">
                        <input type="hidden" name="user_id" id="user_id">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                            <div class="invalid-feedback">Please enter a full name.</div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current password">
                            <div class="invalid-feedback">Please enter a password.</div>
                        </div>
                        <button type="submit" class="btn btn-primary" id="submitButton">Add Voter</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.edit-voter');
            const modal = new bootstrap.Modal(document.getElementById('voterModal'));
            const modalTitle = document.getElementById('voterModalLabel');
            const modalAction = document.getElementById('modal_action');
            const userIdInput = document.getElementById('user_id');
            const nameInput = document.getElementById('full_name');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const submitButton = document.getElementById('submitButton');
            const form = document.getElementById('voterForm');

            function setModalMode(mode, id = '', name = '', email = '') {
                form.classList.remove('was-validated');
                nameInput.classList.remove('is-invalid');
                emailInput.classList.remove('is-invalid');
                passwordInput.classList.remove('is-invalid');

                if (mode === 'add') {
                    modalTitle.textContent = 'Add Voter';
                    modalAction.value = 'add';
                    userIdInput.value = '';
                    nameInput.value = '';
                    emailInput.value = '';
                    passwordInput.value = '';
                    passwordInput.required = true;
                    passwordInput.placeholder = 'Enter password';
                    submitButton.textContent = 'Add Voter';
                } else {
                    modalTitle.textContent = 'Edit Voter';
                    modalAction.value = 'edit';
                    userIdInput.value = id;
                    nameInput.value = name;
                    emailInput.value = email;
                    passwordInput.value = '';
                    passwordInput.required = false;
                    passwordInput.placeholder = 'Leave blank to keep current password';
                    submitButton.textContent = 'Save Changes';
                }
            }

            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const email = this.getAttribute('data-email');
                    setModalMode('edit', id, name, email);
                    modal.show();
                });
            });

            // Reset form when modal is closed
            document.querySelector('#voterModal').addEventListener('hidden.bs.modal', function() {
                form.classList.remove('was-validated');
                nameInput.classList.remove('is-invalid');
                emailInput.classList.remove('is-invalid');
                passwordInput.classList.remove('is-invalid');
                nameInput.value = '';
                emailInput.value = '';
                passwordInput.value = '';
                passwordInput.required = true;
                passwordInput.placeholder = 'Enter password';
                modalAction.value = 'add';
                modalTitle.textContent = 'Add Voter';
                submitButton.textContent = 'Add Voter';
                submitButton.disabled = false;
                submitButton.textContent = modalAction.value === 'add' ? 'Add Voter' : 'Save Changes';
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