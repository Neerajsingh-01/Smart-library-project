<?php
include "db.php";

$studentId = require_student($conn);
$bookId = intval($_POST["book"] ?? 0);

if ($bookId <= 0) {
  send_json([
    "success" => false,
    "message" => "Invalid book"
  ], 400);
}

$stmt = $conn->prepare("SELECT id, available FROM books WHERE id = ?");
$stmt->bind_param("i", $bookId);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();

if (!$book) {
  send_json([
    "success" => false,
    "message" => "Book not found"
  ], 404);
}

if (intval($book["available"]) > 0) {
  send_json([
    "success" => false,
    "message" => "Book is already available. You can issue it."
  ], 409);
}

$stmt = $conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND book_id = ? AND status IN ('waiting', 'notified')");
$stmt->bind_param("si", $studentId, $bookId);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
  send_json([
    "success" => false,
    "message" => "Notification request already exists"
  ], 409);
}

$stmt = $conn->prepare("INSERT INTO notifications (user_id, book_id, status) VALUES (?, ?, 'waiting')");
$stmt->bind_param("si", $studentId, $bookId);
$stmt->execute();

log_activity($conn, $studentId, "notification", "Requested alert for book #" . $bookId);

send_json([
  "success" => true,
  "message" => "You will be notified on priority basis"
]);
?>
