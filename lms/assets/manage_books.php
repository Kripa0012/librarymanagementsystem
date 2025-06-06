<?php
session_start();
require('includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$success = '';
$error = '';

// Handle delete book request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: manage_books.php");
    exit;
}

// Handle add book request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
    $book_title = trim($_POST['book_title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $available_copies = intval($_POST['available_copies'] ?? 0);
    $total_copies = intval($_POST['total_copies'] ?? 0);

    if ($book_title === '' || $author === '' || $isbn === '' || $total_copies <= 0 || $available_copies < 0 || $available_copies > $total_copies) {
        $error = "Please provide valid book details. Available copies cannot exceed total copies.";
    } else {
        $stmt = $conn->prepare("INSERT INTO books (title, author, isbn, available_copies, total_copies) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssii", $book_title, $author, $isbn, $available_copies, $total_copies);
        if ($stmt->execute()) {
            $success = "Book added successfully.";
        } else {
            $error = "Failed to add book. Please try again.";
        }
    }
}

// Handle update book request (inline editing)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_book'])) {
    $id = intval($_POST['id']);
    $book_title = trim($_POST['book_title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $available_copies = intval($_POST['available_copies'] ?? 0);
    $total_copies = intval($_POST['total_copies'] ?? 0);

    if ($book_title === '' || $author === '' || $isbn === '' || $total_copies <= 0 || $available_copies < 0 || $available_copies > $total_copies) {
        $error = "Please provide valid book details for update. Available copies cannot exceed total copies.";
    } else {
        $stmt = $conn->prepare("UPDATE books SET title = ?, author = ?, isbn = ?, available_copies = ?, total_copies = ? WHERE id = ?");
        $stmt->bind_param("sssiii", $book_title, $author, $isbn, $available_copies, $total_copies, $id);
        if ($stmt->execute()) {
            $success = "Book updated successfully.";
        } else {
            $error = "Failed to update book. Please try again.";
        }
    }
}

// Fetch all books
$result = $conn->query("SELECT * FROM books ORDER BY id DESC");

// For inline editing, get the id of the book currently being edited (if any)
$edit_id = $_GET['edit_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Manage Books</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f8f9fa;
      padding: 20px;
    }
  </style>
</head>
<body>
  <div class="container mt-4">
    <a href="admin_dashboard.php" class="btn btn-outline-primary mb-3">&larr; Back to Dashboard</a>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2>Manage Books</h2>
      <a href="#" data-bs-toggle="modal" data-bs-target="#addBookModal" class="btn btn-success">Add New Book</a>
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
          <th>Title</th>
          <th>Author</th>
          <th>ISBN</th>
          <th>Total Copies</th>
          <th>Available Copies</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($book = $result->fetch_assoc()): ?>
            <?php if ($edit_id == $book['id']): ?>
              <!-- Editable Row -->
              <tr>
                <form method="POST" action="">
                  <input type="hidden" name="update_book" value="1" />
                  <input type="hidden" name="id" value="<?= $book['id'] ?>" />
                  <td><?= $book['id'] ?></td>
                  <td><input type="text" name="book_title" class="form-control" value="<?= htmlspecialchars($book['title']) ?>" required></td>
                  <td><input type="text" name="author" class="form-control" value="<?= htmlspecialchars($book['author']) ?>" required></td>
                  <td><input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($book['isbn']) ?>" required></td>
                  <td><input type="number" name="total_copies" class="form-control" min="1" value="<?= $book['total_copies'] ?>" required></td>
                  <td><input type="number" name="available_copies" class="form-control" min="0" max="<?= $book['total_copies'] ?>" value="<?= $book['available_copies'] ?>" required></td>
                  <td>
                    <button type="submit" class="btn btn-sm btn-success">Save</button>
                    <a href="manage_books.php" class="btn btn-sm btn-secondary">Cancel</a>
                  </td>
                </form>
              </tr>
            <?php else: ?>
              <!-- Normal display row -->
              <tr>
                <td><?= htmlspecialchars($book['id']) ?></td>
                <td><?= htmlspecialchars($book['title']) ?></td>
                <td><?= htmlspecialchars($book['author']) ?></td>
                <td><?= htmlspecialchars($book['isbn']) ?></td>
                <td><?= htmlspecialchars($book['total_copies']) ?></td>
                <td><?= htmlspecialchars($book['available_copies']) ?></td>
                <td>
                  <a href="manage_books.php?edit_id=<?= $book['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                  <a href="manage_books.php?delete_id=<?= $book['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this book?');">Delete</a>
                </td>
              </tr>
            <?php endif; ?>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-center">No books found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Add Book Modal -->
  <div class="modal fade" id="addBookModal" tabindex="-1" aria-labelledby="addBookModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="">
          <div class="modal-header">
            <h5 class="modal-title" id="addBookModalLabel">Add New Book</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="add_book" value="1" />
            <div class="mb-3">
              <label for="book_title" class="form-label">Book Title</label>
              <input type="text" name="book_title" id="book_title" class="form-control" required />
            </div>
            <div class="mb-3">
              <label for="author" class="form-label">Author</label>
              <input type="text" name="author" id="author" class="form-control" required />
            </div>
            <div class="mb-3">
              <label for="isbn" class="form-label">ISBN</label>
              <input type="text" name="isbn" id="isbn" class="form-control" required />
            </div>
            <div class="mb-3">
              <label for="available_copies" class="form-label">Available Copies</label>
              <input type="number" name="available_copies" id="available_copies" class="form-control" min="0" required />
            </div>
            <div class="mb-3">
              <label for="total_copies" class="form-label">Total Copies</label>
              <input type="number" name="total_copies" id="total_copies" class="form-control" min="1" required />
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Add Book</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
