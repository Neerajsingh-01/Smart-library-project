<?php
include "db.php";

$studentId = require_student($conn);
$bookId = intval($_POST["id"] ?? 0);

if ($bookId <= 0) {
  send_json([
    "success" => false,
    "message" => "Invalid book"
  ], 400);
}

$stmt = $conn->prepare("SELECT id FROM issued_books WHERE user_id = ? AND book_id = ? AND returned_at IS NULL");
$stmt->bind_param("si", $studentId, $bookId);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
  send_json([
    "success" => false,
    "message" => "This book is already issued to you"
  ], 409);
}

$conn->begin_transaction();

$stmt = $conn->prepare("UPDATE books SET available = available - 1 WHERE id = ? AND available > 0");
$stmt->bind_param("i", $bookId);
$stmt->execute();

if ($stmt->affected_rows === 0) {
  $conn->rollback();
  send_json([
    "success" => false,
    "message" => "Book is not available right now"
  ], 409);
}

$stmt = $conn->prepare("INSERT INTO issued_books (user_id, book_id) VALUES (?, ?)");
$stmt->bind_param("si", $studentId, $bookId);
$stmt->execute();

$stmt = $conn->prepare("UPDATE notifications SET status = 'completed' WHERE user_id = ? AND book_id = ? AND status IN ('waiting', 'notified')");
$stmt->bind_param("si", $studentId, $bookId);
$stmt->execute();

log_activity($conn, $studentId, "issue", "Issued book #" . $bookId);

$conn->commit();

send_json([
  "success" => true,
  "message" => "Book issued successfully"
]);
?>
