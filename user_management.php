<?php
session_start();
include('config.php'); // Database connection

// Check if user is logged in and has admin role
// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    // If the page is in an iframe, redirect the parent window
    echo "<script>
            if (window.top !== window) {
                window.top.location.href = 'login.php';
            } else {
                window.location.href = 'login.php';
            }
          </script>";
    exit();
}

// Function to generate password reset token
function generateToken() {
    return bin2hex(random_bytes(16)); // Generate a 32 character random token
}



// Handle actions like add, edit, or inactive status update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        // Add a new user
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO users (username, full_name, role, password, status) 
                         VALUES ('$username', '$full_name', '$role', '$password', 'active')";
        mysqli_query($conn, $insert_query);
    } elseif (isset($_POST['edit_user'])) {
        // Edit user details
        $user_id = $_POST['user_id'];
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        
        $update_query = "UPDATE users SET username = '$username', full_name = '$full_name', role = '$role' 
                         WHERE id = '$user_id'";
        mysqli_query($conn, $update_query);
    } elseif (isset($_POST['deactivate_user'])) {
        // Mark user as inactive
        $user_id = $_POST['user_id'];
        $deactivate_query = "UPDATE users SET status = 'inactive' WHERE id = '$user_id'";
        mysqli_query($conn, $deactivate_query);
    } elseif (isset($_POST['generate_token'])) {
        // Generate password reset token
        $user_id = $_POST['user_id'];
        $token = generateToken();
        $update_token_query = "UPDATE users SET password_reset_token = '$token' WHERE id = '$user_id'";
        mysqli_query($conn, $update_token_query);
        
        // Copy token to clipboard functionality will be handled via JS
      echo "<script>
    // Show the alert with the token message
    alert('Password reset token generated successfully.');

    // Create a temporary input field to hold the token
    var tempInput = document.createElement('input');
    tempInput.value = '$token'; // Set the value to the token
    document.body.appendChild(tempInput);
    
    // Select the input field content
    tempInput.select();
    tempInput.setSelectionRange(0, 99999); // For mobile devices
    
    // Copy the content to the clipboard
    document.execCommand('copy');
    
    // Remove the temporary input field after copying
    
    
    // Notify the user that the token has been copied to the clipboard
    alert('Password reset token has been copied to the clipboard!');
</script>";
    }
}
// Fetch all users from the database
$query = "SELECT * FROM users";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
        // Function to copy token to clipboard
        function copyToClipboard(token) {
            navigator.clipboard.writeText(token).then(() => {
                alert('Token copied to clipboard!');
            });
        }
    </script>
</head>
<body>
    <div class="container mt-5">
        <h2>User Management</h2>
        
        <!-- Add User Form -->
        <form action="" method="POST" class="mb-4">
    <h4>Add New User</h4>
    <div class="row g-3">
        <div class="col-md-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" name="umusername" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label for="full_name" class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label for="role" class="form-label">Role</label>
            <select name="role" class="form-select" required>
                <option value="admin">Admin</option>
                <option value="user">User</option>
                <option value="sales">Agent</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" name="add_user" class="btn btn-success w-100">Add User</button>
        </div>
    </div>
</form>

        
        <!-- Users Table -->
        <h4>All Users</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = mysqli_fetch_assoc($result)) : ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= $user['username'] ?></td>
                        <td><?= $user['full_name'] ?></td>
                        <td><?= $user['role'] ?></td>
                        <td><?= $user['status'] ?></td>
                        <td>
                            <!-- Edit User Button -->
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal" 
                                    data-id="<?= $user['id'] ?>" data-username="<?= $user['username'] ?>" 
                                    data-full_name="<?= $user['full_name'] ?>" data-role="<?= $user['role'] ?>">
                                Edit
                            </button>
                            <!-- Deactivate User Button -->
                            <form action="" method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" name="deactivate_user" class="btn btn-warning btn-sm">Deactivate</button>
                            </form>
                            <!-- Generate Reset Token Button -->
                            <form action="" method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" name="generate_token" class="btn btn-info btn-sm">Generate Token</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Username</label>
                            <input type="text" name="username" id="edit_username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_full_name" class="form-label">Full Name</label>
                            <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Role</label>
                            <select name="role" id="edit_role" class="form-select" required>
                                <option value="admin">Admin</option>
                                <option value="user">User</option>
                                <option value="sales">Agent</option>
                            </select>
                        </div>
                        <button type="submit" name="edit_user" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pre-fill the edit user modal with user data
        const editUserModal = document.getElementById('editUserModal');
        editUserModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-id');
            const username = button.getAttribute('data-username');
            const fullName = button.getAttribute('data-full_name');
            const role = button.getAttribute('data-role');
            
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_full_name').value = fullName;
            document.getElementById('edit_role').value = role;
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
