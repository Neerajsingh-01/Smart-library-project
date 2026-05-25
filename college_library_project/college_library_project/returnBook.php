<?php
include "db.php";

$adminId = require_admin($conn);
$issueId = intval($_POST["issue_id"] ?? 0);

if ($issueId <= 0) {
  send_json([
    "success" => false,
    "message" => "Invalid issue record"
  ], 400);
}

$conn->begin_transaction();

$stmt = $conn->prepare("
  SELECT user_id, book_id
  FROM issued_books
  WHERE id = ? AND returned_at IS NULL
");
$stmt->bind_param("i", $issueId);
$stmt->execute();
$issue = $stmt->get_result()->fetch_assoc();

if (!$issue) {
  $conn->rollback();
  send_json([
    "success" => false,
    "message" => "Active issue not found"
  ], 404);
}

$stmt = $conn->prepare("UPDATE issued_books SET returned_at = CURRENT_TIMESTAMP WHERE id = ?");
$stmt->bind_param("i", $issueId);
$stmt->execute();

$bookId = intval($issue["book_id"]);
$studentId = $issue["user_id"];

$stmt = $conn->prepare("UPDATE books SET available = LEAST(total, available + 1) WHERE id = ?");
$stmt->bind_param("i", $bookId);
$stmt->execute();

promote_next_notification($conn, $bookId);
log_activity($conn, $studentId, "return", "Returned book #" . $bookId);
log_activity($conn, $adminId, "admin_return", "Marked return for issue #" . $issueId);

$conn->commit();

send_json([
  "success" => true,
  "message" => "Book return marked"
]);
?>
