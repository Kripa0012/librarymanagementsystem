<?php
session_start();
require('includes/db.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$search_term = $_GET['search'] ?? '';

// Handle borrow action
if (isset($_POST['borrow_book_id'])) {
    $book_id = intval($_POST['borrow_book_id']);

    // Check if already borrowed and not returned
    $stmt = $conn->prepare("SELECT * FROM borrowed_books WHERE user_id = ? AND book_id = ? AND return_date IS NULL");
    $stmt->bind_param("ii", $user_id, $book_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        // Check available copies
        $stmt2 = $conn->prepare("SELECT available_copies FROM books WHERE id = ?");
        $stmt2->bind_param("i", $book_id);
        $stmt2->execute();
        $book_res = $stmt2->get_result()->fetch_assoc();

        if ($book_res && $book_res['available_copies'] > 0) {
            // Insert borrow record
            $now = date('Y-m-d');
            $insert = $conn->prepare("INSERT INTO borrowed_books (user_id, book_id, borrow_date) VALUES (?, ?, ?)");
            $insert->bind_param("iis", $user_id, $book_id, $now);
            if ($insert->execute()) {
                // Decrement available copies
                $upd = $conn->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
                $upd->bind_param("i", $book_id);
                $upd->execute();
                $msg = "Book borrowed successfully.";
            } else {
                $msg = "Failed to borrow book.";
            }
        } else {
            $msg = "No available copies to borrow.";
        }
    } else {
        $msg = "You already borrowed this book and haven't returned it.";
    }
}

// Handle return action
if (isset($_POST['return_book_id'])) {
    $book_id = intval($_POST['return_book_id']);
    // Update borrowed_books return_date
    $now = date('Y-m-d');
    $upd = $conn->prepare("UPDATE borrowed_books SET return_date = ? WHERE user_id = ? AND book_id = ? AND return_date IS NULL");
    $upd->bind_param("sii", $now, $user_id, $book_id);
    if ($upd->execute()) {
        // Increment available copies
        $inc = $conn->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
        $inc->bind_param("i", $book_id);
        $inc->execute();
        $msg = "Book returned successfully.";
    } else {
        $msg = "Failed to return book.";
    }
}

// Fetch books with optional search
$search_sql = "";
$params = [];
$types = "";

if ($search_term !== '') {
    $search_sql = " WHERE title LIKE ? OR author LIKE ? OR isbn LIKE ? ";
    $like_term = "%$search_term%";
    $params = [$like_term, $like_term, $like_term];
    $types = "sss";
}

$sql = "SELECT * FROM books $search_sql ORDER BY title ASC";
$stmt = $conn->prepare($sql);

if ($search_term !== '') {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$books_result = $stmt->get_result();

// Fetch user's borrowed books with no return_date (currently borrowed)
$borrowed_books = [];
$stmt2 = $conn->prepare("SELECT book_id, borrow_date FROM borrowed_books WHERE user_id = ? AND return_date IS NULL");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
while ($row = $res2->fetch_assoc()) {
    $borrowed_books[$row['book_id']] = $row['borrow_date'];
}

// Function to calculate remaining days
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
  <title>User Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body, html {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f0f2f5;
      padding: 20px;
    }
    .borrowed-info {
      font-size: 0.9rem;
      font-weight: bold;
      color: #006400;
    }
    .borrowed-info.overdue {
      color: #a00;
    }
  </style>
</head>
<body>
  <div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h1>
      <form class="d-flex" method="get" action="">
        <input class="form-control me-2" type="search" name="search" placeholder="Search books..." value="<?= htmlspecialchars($search_term) ?>">
        <button class="btn btn-outline-primary" type="submit">Search</button>
      </form>
      <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <?php if (!empty($msg)): ?>
      <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <table class="table table-striped table-bordered align-middle">
      <thead class="table-dark">
        <tr>
          <th>Title</th>
          <th>Author</th>
          <th>ISBN</th>
          <th>Available Copies</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($books_result->num_rows === 0): ?>
          <tr><td colspan="5" class="text-center">No books found.</td></tr>
        <?php else: ?>
          <?php while ($book = $books_result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($book['title']) ?></td>
              <td><?= htmlspecialchars($book['author']) ?></td>
              <td><?= htmlspecialchars($book['isbn']) ?></td>
              <td><?= (int)$book['available_copies'] ?></td>
              <td>
                <?php if (isset($borrowed_books[$book['id']])): 
                    $days_left = remainingDays($borrowed_books[$book['id']]);
                    $overdue = $days_left === 0;
                ?>
                  <div class="borrowed-info <?= $overdue ? 'overdue' : '' ?>">
                    <?= $overdue ? "Overdue! Please return." : "$days_left days remaining" ?>
                  </div>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="return_book_id" value="<?= $book['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-warning">Return</button>
                  </form>
                <?php else: ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="borrow_book_id" value="<?= $book['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-primary" <?= $book['available_copies'] <= 0 ? 'disabled' : '' ?>>Borrow</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>

  </div>
</body>
</html>
