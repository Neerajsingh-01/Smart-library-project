CREATE DATABASE IF NOT EXISTS college_library;
USE college_library;

CREATE TABLE IF NOT EXISTS students (
  id VARCHAR(30) PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(120) DEFAULT '',
  role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  author VARCHAR(120) NOT NULL,
  publisher VARCHAR(120) DEFAULT '',
  subject VARCHAR(120) DEFAULT '',
  course VARCHAR(80) DEFAULT '',
  semester INT DEFAULT NULL,
  rack_position VARCHAR(50) DEFAULT '',
  total INT NOT NULL DEFAULT 1,
  available INT NOT NULL DEFAULT 1,
  added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_book (name, author)
);

CREATE TABLE IF NOT EXISTS issued_books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(30) NOT NULL,
  book_id INT NOT NULL,
  issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  returned_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES students(id),
  FOREIGN KEY (book_id) REFERENCES books(id)
);

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(30) NOT NULL,
  book_id INT NOT NULL,
  status ENUM('waiting', 'notified', 'completed') NOT NULL DEFAULT 'waiting',
  requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  notified_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES students(id),
  FOREIGN KEY (book_id) REFERENCES books(id)
);

CREATE TABLE IF NOT EXISTS suggestions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(30) NOT NULL,
  book_name VARCHAR(150) NOT NULL,
  author VARCHAR(120) NOT NULL,
  edition VARCHAR(80) DEFAULT '',
  status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  reviewed_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES students(id)
);

CREATE TABLE IF NOT EXISTS activities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(30) NOT NULL,
  action VARCHAR(80) NOT NULL,
  details VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO students (id, name, password, email, role) VALUES
('STU001', 'Demo Student', '12345', 'student@example.com', 'student'),
('ADM001', 'Library Admin', 'admin123', 'admin@example.com', 'admin')
ON DUPLICATE KEY UPDATE name = VALUES(name), email = VALUES(email), role = VALUES(role);

INSERT INTO books
(name, author, publisher, subject, course, semester, total, available, rack_position) VALUES
('Database System Concepts', 'Abraham Silberschatz', 'McGraw Hill', 'DBMS', 'B.Tech', 4, 3, 2, 'CS-R2-S1'),
('Clean Code', 'Robert C. Martin', 'Prentice Hall', 'Software Engineering', 'B.Tech', 5, 2, 0, 'CS-R3-S2'),
('Introduction to Algorithms', 'Cormen', 'MIT Press', 'Algorithms', 'B.Tech', 3, 2, 1, 'CS-R1-S4'),
('Let Us C', 'Yashavant Kanetkar', 'BPB', 'C Programming', 'B.Tech', 1, 4, 0, 'CS-R1-S1'),
('Financial Accounting', 'T. S. Grewal', 'S Chand', 'Accounting', 'BBA', 2, 5, 4, 'MG-R1-S3')
ON DUPLICATE KEY UPDATE
publisher = VALUES(publisher),
subject = VALUES(subject),
course = VALUES(course),
semester = VALUES(semester),
total = VALUES(total),
available = VALUES(available),
rack_position = VALUES(rack_position);
