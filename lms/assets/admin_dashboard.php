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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Fetch current user from DB
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        $error = "User not found.";
    } elseif (!password_verify($old_password, $user['password'])) {
        $error = "Old password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match.";
    } elseif (strlen($new_password) < 6 ||
              !preg_match('/[A-Z]/', $new_password) ||
              !preg_match('/[0-9]/', $new_password)) {
        $error = "Password must be at least 6 characters, include an uppercase letter and a number.";
    } else {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_hash, $_SESSION['user_id']);
        if ($update_stmt->execute()) {
            $success = "Password updated successfully.";
        } else {
            $error = "Failed to update password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f8f9fa;
    }
    .sidebar {
      height: 100vh;
      background-color: #212529;
      padding-top: 20px;
      position: fixed;
      width: 220px;
    }
    .sidebar a {
      color: #ddd;
      padding: 12px 20px;
      display: block;
      text-decoration: none;
      font-size: 16px;
    }
    .sidebar a:hover {
      background-color: #343a40;
      color: #fff;
    }
    .content {
      margin-left: 220px;
      padding: 30px;
    }
    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }
    .dropdown-toggle::after {
      display: none;
    }
    .profile-btn {
      width: 45px;
      height: 45px;
      background-color: #6c757d;
      border-radius: 50%;
      color: #fff;
      font-size: 18px;
      display: flex;
      justify-content: center;
      align-items: center;
      border: none;
      cursor: pointer;
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <h5 class="text-center text-white mb-4">📚 Admin Panel</h5>
    <a href="admin_dashboard.php">🏠 Dashboard</a>
    <a href="manage_users.php">👥 Manage Users</a>
    <a href="manage_books.php">📖 Manage Books</a>
    <a href="view_borrowed.php">📂 View Borrowed</a>
  </div>

  <!-- Main Content -->
  <div class="content">
    <div class="topbar">
      <h3>Welcome to Library Management System, <strong>Admin</strong></h3>
      <div class="dropdown">
        <button class="btn profile-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
          A
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#passwordModal">Update Password</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
        </ul>
      </div>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="alert alert-info">
      <strong>Hello Admin!</strong> Use the sidebar to manage users, books, or view system activity.
    </div>
  </div>

  <!-- Password Update Modal -->
  <div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="">
          <div class="modal-header">
            <h5 class="modal-title" id="passwordModalLabel">Update Password</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
              <input type="hidden" name="update_password" value="1" />
              <div class="mb-3">
                <label for="old_password" class="form-label">Old Password</label>
                <input type="password" name="old_password" id="old_password" class="form-control" required />
              </div>
              <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" name="new_password" id="new_password" class="form-control" required />
                <div class="form-text">At least 6 chars, include uppercase & number</div>
              </div>
              <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required />
              </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Update Password</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
