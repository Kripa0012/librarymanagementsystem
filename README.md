# 📚 Library Management System

This is a simple Library Management System built using PHP and MySQL. It includes basic functionality for admin (librarian) and users, such as managing books, borrowing/returning, and viewing available titles.


## 🚀 Features

- **Admin (Librarian)**:
  - Add, edit, delete books
  - Manage users and their borrowing activity

- **Users**:
  - View available books
  - Search books by title or author
  - Borrow books and view remaining time

---

## 🔧 Project Setup

### 1. **Database Configuration**

By default, XAMPP uses port `3306` for MySQL. However, in this project, MySQL was set to **port `3307`** due to port conflict.  
Make sure your MySQL is running on `3307` or update the port as needed in the `db.php` file.

### 2. **Database Name**

The database is named:  library_system
 Use the following SQL to create the required tables in your MySQL database:

```sql
-- Users table
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books table
CREATE TABLE books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  author VARCHAR(255) NOT NULL,
  isbn VARCHAR(20) NOT NULL UNIQUE,
  available_copies INT NOT NULL DEFAULT 0,
  total_copies INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- BorrowedBooks table
CREATE TABLE borrowed_books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  book_id INT NOT NULL,
  borrow_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  due_date DATETIME NOT NULL,
  return_date DATETIME DEFAULT NULL,
  status ENUM('borrowed', 'returned') NOT NULL DEFAULT 'borrowed',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);
📂 File Structure
includes/db.php
Connects the project to the MySQL database.
⚠️ Note: Ensure the correct path when including db.php in other files. Incorrect paths may result in connection errors.

admin/
Admin dashboard and book/user management system

user/
User interface for viewing and borrowing books



---
Unique Feature: Countdown Timer
When a user borrows a book, the system sets a 30-day countdown timer for the due date.
The remaining days are updated dynamically (e.g., "30 days remaining", "29 days remaining", etc.)
This helps users track their return deadline easily.


✅ Requirements
XAMPP (with Apache and MySQL)

PHP 7.x or higher

MySQL (running on port 3307 in this setup)

Basic web browser

💡 Notes
Passwords are stored securely (hashed).

Only admins can manage books and users.

Users can only view and borrow books.


