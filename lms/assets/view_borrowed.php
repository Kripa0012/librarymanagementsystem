<?php
session_start();
require('includes/db.php');

// Check admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Fetch all currently borrowed books (not returned)
$sql = "
    SELECT bb.id AS borrow_id, u.username, b.title, bb.borrow_date
    FROM borrowed_books bb
    JOIN users u ON bb.user_id = u.id
    JOIN books b ON bb.book_id = b.id
    WHERE bb.return_date IS NULL
    ORDER BY bb.borrow_date DESC
";

$result = $conn->query($sql);

function remainingDays($borrow_date) {
    $borrow_time = strtotime($borrow_date);
    $now = time();
    $diff = (30*24*60*60) - ($now - $borrow_time);
    $days = ceil($diff / (24*60*60));
    return $days > 0 ? $days : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>View Borrowed Books - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      padding: 20px;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f9f9f9;
    }
    .overdue {
      color: #d9534f;
      font-weight: bold;
    }
  </style>
</head>
<body>

  <h1>Currently Borrowed Books</h1>
  <a href="admin_dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>

  <?php if ($result->num_rows === 0): ?>
    <div class="alert alert-info">No books are currently borrowed.</div>
  <?php else: ?>
    <table class="table table-bordered table-striped">
      <thead class="table-dark">
        <tr>
          <th>Book Title</th>
          <th>Borrower</th>
          <th>Borrow Date</th>
          <th>Days Remaining</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): 
          $days_left = remainingDays($row['borrow_date']);
          $overdue_class = $days_left === 0 ? 'overdue' : '';
        ?>
          <tr>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['borrow_date']) ?></td>
            <td class="<?= $overdue_class ?>">
              <?= $days_left === 0 ? 'Overdue!' : $days_left . ' day(s)' ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php endif; ?>

</body>
</html>
