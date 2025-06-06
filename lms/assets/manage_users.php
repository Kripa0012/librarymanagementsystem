<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require('includes/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$success = '';
$error = '';

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if ($password !== $confirm_password) {
        $error = "Password and confirm password do not match.";
    } elseif (strlen($password) < 6 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = "Password must be at least 6 characters, include uppercase and number.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
            if ($insert_stmt->execute()) {
                $success = "User added successfully.";
            } else {
                $error = "Failed to add user.";
            }
        }
        $stmt->close();
    }
}

// Handle Delete User
if (isset($_GET['delete_user'])) {
    $delete_id = (int)$_GET['delete_user'];
    if ($delete_id !== $_SESSION['user_id']) {  // prevent deleting own admin
        $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $delete_stmt->bind_param("i", $delete_id);
        if ($delete_stmt->execute()) {
            $success = "User deleted successfully.";
        } else {
            $error = "Failed to delete user.";
        }
        $delete_stmt->close();
    } else {
        $error = "You cannot delete yourself!";
    }
}

// Handle Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    $edit_id = (int)$_POST['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    // Optional: password update on edit (leave blank if no change)
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== '' && $new_password !== $confirm_password) {
        $error = "Password and confirm password do not match.";
    } elseif ($new_password !== '' && (strlen($new_password) < 6 || !preg_match('/[A-Z]/', $new_password) || !preg_match('/[0-9]/', $new_password))) {
        $error = "Password must be at least 6 characters, include uppercase and number.";
    } else {
        // Check if username or email exists for other users
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->bind_param("ssi", $username, $email, $edit_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            if ($new_password !== '') {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ?, password = ? WHERE id = ?");
                $update_stmt->bind_param("ssssi", $username, $email, $role, $hashed_password, $edit_id);
            } else {
                $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
                $update_stmt->bind_param("sssi", $username, $email, $role, $edit_id);
            }
            if ($update_stmt->execute()) {
                $success = "User updated successfully.";
            } else {
                $error = "Failed to update user.";
            }
            $update_stmt->close();
        }
        $stmt->close();
    }
}

// Fetch all users to show
$users = [];
$result = $conn->query("SELECT id, username, email, role FROM users ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Users - Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f8f9fa;
      /* margin-left: 220px; Assuming sidebar width */
      padding: 20px;
    }
    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .btn-add-user {
      width: 130px;
    }
  </style>
</head>
<body>

   <div class="container mt-4">
   <a href="admin_dashboard.php" class="btn btn-outline-primary mb-3" title="Back to Dashboard">
      &larr; Back to Dashboard
    </a>

</div>

  <div class="topbar">
    <h2>Manage Users</h2>
    <button class="btn btn-success btn-add-user" data-bs-toggle="modal" data-bs-target="#addUserModal">+ Add User</button>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <table class="table table-striped table-bordered align-middle">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Email</th>
        <th>Role</th>
        <th style="width: 160px;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($users) === 0): ?>
        <tr><td colspan="5" class="text-center">No users found.</td></tr>
      <?php else: ?>
        <?php foreach ($users as $user): ?>
          <tr>
            <td><?= htmlspecialchars($user['id']) ?></td>
            <td><?= htmlspecialchars($user['username']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><?= htmlspecialchars($user['role']) ?></td>
            <td>
              <button class="btn btn-primary btn-sm btn-edit" 
                data-id="<?= $user['id'] ?>"
                data-username="<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>"
                data-email="<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>"
                data-role="<?= $user['role'] ?>"
                data-bs-toggle="modal" data-bs-target="#editUserModal">Edit</button>
              <?php if ($user['id'] !== $_SESSION['user_id']): // can't delete self ?>
              <a href="?delete_user=<?= $user['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?');">Delete</a>
              <?php else: ?>
              <button class="btn btn-secondary btn-sm" disabled>Delete</button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Add User Modal -->
  <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="">
          <input type="hidden" name="action" value="add_user" />
          <div class="modal-header">
            <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
              <div class="mb-3">
                <label for="add_username" class="form-label">Username</label>
                <input type="text" id="add_username" name="username" class="form-control" required />
              </div>
              <div class="mb-3">
                <label for="add_email" class="form-label">Email</label>
                <input type="email" id="add_email" name="email" class="form-control" required />
              </div>
              <div class="mb-3">
                <label for="add_role" class="form-label">Role</label>
                <select id="add_role" name="role" class="form-select" required>
                  <option value="user" selected>User</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="add_password" class="form-label">Password</label>
                <input type="password" id="add_password" name="password" class="form-control" required />
                <div class="form-text">At least 6 chars, include uppercase & number</div>
              </div>
              <div class="mb-3">
                <label for="add_confirm_password" class="form-label">Confirm Password</label>
                <input type="password" id="add_confirm_password" name="confirm_password" class="form-control" required />
              </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Add User</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit User Modal -->
  <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="">
          <input type="hidden" name="action" value="edit_user" />
          <input type="hidden" id="edit_user_id" name="user_id" />
          <div class="modal-header">
            <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
              <div class="mb-3">
                <label for="edit_username" class="form-label">Username</label>
                <input type="text" id="edit_username" name="username" class="form-control" required />
              </div>
              <div class="mb-3">
                <label for="edit_email" class="form-label">Email</label>
                <input type="email" id="edit_email" name="email" class="form-control" required />
              </div>
              <div class="mb-3">
                <label for="edit_role" class="form-label">Role</label>
                <select id="edit_role" name="role" class="form-select" required>
                  <option value="user">User</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
              <hr />
              <p class="text-muted">Leave password fields empty if you don't want to change the password.</p>
              <div class="mb-3">
                <label for="edit_new_password" class="form-label">New Password</label>
                <input type="password" id="edit_new_password" name="new_password" class="form-control" />
              </div>
              <div class="mb-3">
                <label for="edit_confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" id="edit_confirm_password" name="confirm_password" class="form-control" />
              </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Update User</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Fill edit modal with user data when edit button clicked
document.querySelectorAll('.btn-edit').forEach(button => {
  button.addEventListener('click', function() {
    document.getElementById('edit_user_id').value = this.dataset.id;
    document.getElementById('edit_username').value = this.dataset.username;
    document.getElementById('edit_email').value = this.dataset.email;
    document.getElementById('edit_role').value = this.dataset.role;

    // Clear password fields on open
    document.getElementById('edit_new_password').value = '';
    document.getElementById('edit_confirm_password').value = '';
  });
});
</script>
</body>
</html>
