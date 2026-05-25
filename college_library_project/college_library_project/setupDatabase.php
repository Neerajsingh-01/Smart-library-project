<?php
include "db.php";

$queries = [
  "CREATE TABLE IF NOT EXISTS students (
    id VARCHAR(30) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL
  )",
  "CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    author VARCHAR(120) NOT NULL,
    total INT NOT NULL DEFAULT 1,
    available INT NOT NULL DEFAULT 1,
    UNIQUE KEY unique_book (name, author)
  )",
  "CREATE TABLE IF NOT EXISTS issued_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(30) NOT NULL,
    book_id INT NOT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    returned_at TIMESTAMP NULL
  )",
  "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(30) NOT NULL,
    book_id INT NOT NULL,
    status ENUM('waiting', 'notified', 'completed') NOT NULL DEFAULT 'waiting',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )",
  "CREATE TABLE IF NOT EXISTS suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(30) NOT NULL,
    book_name VARCHAR(150) NOT NULL,
    author VARCHAR(120) NOT NULL,
    edition VARCHAR(80) DEFAULT '',
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_note VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL
  )",
  "CREATE TABLE IF NOT EXISTS activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(30) NOT NULL,
    action VARCHAR(80) NOT NULL,
    details VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )"
];

foreach ($queries as $query) {
  $conn->query($query);
}

$columns = [
  ["students", "email", "ALTER TABLE students ADD COLUMN email VARCHAR(120) NULL"],
  ["students", "role", "ALTER TABLE students ADD COLUMN role ENUM('student', 'admin') NOT NULL DEFAULT 'student'"],
  ["students", "created_at", "ALTER TABLE students ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"],
  ["books", "publisher", "ALTER TABLE books ADD COLUMN publisher VARCHAR(120) DEFAULT ''"],
  ["books", "subject", "ALTER TABLE books ADD COLUMN subject VARCHAR(120) DEFAULT ''"],
  ["books", "course", "ALTER TABLE books ADD COLUMN course VARCHAR(80) DEFAULT ''"],
  ["books", "semester", "ALTER TABLE books ADD COLUMN semester INT DEFAULT NULL"],
  ["books", "rack_position", "ALTER TABLE books ADD COLUMN rack_position VARCHAR(50) DEFAULT ''"],
  ["books", "rating", "ALTER TABLE books ADD COLUMN rating DECIMAL(3,2) NOT NULL DEFAULT 0"],
  ["books", "added_at", "ALTER TABLE books ADD COLUMN added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"],
  ["notifications", "notified_at", "ALTER TABLE notifications ADD COLUMN notified_at TIMESTAMP NULL"]
];

foreach ($columns as $column) {
  if (!column_exists($conn, $column[0], $column[1])) {
    $conn->query($column[2]);
  }
}

$conn->query("
  INSERT INTO students (id, name, password, email, role) VALUES
  ('STU001', 'Demo Student', '12345', 'student@example.com', 'student'),
  ('ADM001', 'Library Admin', 'admin123', 'admin@example.com', 'admin')
  ON DUPLICATE KEY UPDATE name = VALUES(name), email = VALUES(email), role = VALUES(role)
");

$conn->query("
  INSERT INTO books
  (name, author, publisher, subject, course, semester, total, available, rack_position, rating) VALUES
  ('Database System Concepts', 'Abraham Silberschatz', 'McGraw Hill', 'Database Management Systems', 'B.Tech', 4, 3, 2, 'CS-R2-S1', 4.8),
  ('Clean Code', 'Robert C. Martin', 'Prentice Hall', 'Software Engineering', 'B.Tech', 5, 2, 0, 'CS-R3-S2', 4.7),
  ('Introduction to Algorithms', 'Cormen, Leiserson, Rivest and Stein', 'MIT Press', 'Data Structures and Algorithms', 'B.Tech', 3, 2, 1, 'CS-R1-S4', 4.9),
  ('Let Us C', 'Yashavant Kanetkar', 'BPB Publications', 'Programming in C', 'B.Tech', 1, 4, 0, 'CS-R1-S1', 4.2),
  ('Financial Accounting', 'T. S. Grewal', 'S Chand', 'Accounting', 'BBA', 2, 5, 4, 'MG-R1-S3', 4.1),
  ('Marketing Management', 'Philip Kotler', 'Pearson', 'Marketing', 'BBA', 4, 3, 3, 'MG-R2-S2', 4.5)
  ON DUPLICATE KEY UPDATE
  publisher = VALUES(publisher),
  subject = VALUES(subject),
  course = VALUES(course),
  semester = VALUES(semester),
  total = VALUES(total),
  available = VALUES(available),
  rack_position = VALUES(rack_position),
  rating = VALUES(rating)
");

send_json([
  "success" => true,
  "message" => "Database setup completed. You can login now.",
  "student_login" => "STU001 / 12345",
  "admin_login" => "ADM001 / admin123"
]);
?>
