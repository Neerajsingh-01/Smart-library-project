<?php
include "db.php";

require_admin($conn);

$id = intval($_POST["id"] ?? 0);
$decision = trim($_POST["decision"] ?? "");

if ($id <= 0 || !in_array($decision, ["approved", "rejected"], true)) {
  send_json([
    "success" => false,
    "message" => "Invalid suggestion review"
  ], 400);
}

$stmt = $conn->prepare("SELECT * FROM suggestions WHERE id = ? AND status = 'pending'");
$stmt->bind_param("i", $id);
$stmt->execute();
$suggestion = $stmt->get_result()->fetch_assoc();

if (!$suggestion) {
  send_json([
    "success" => false,
    "message" => "Pending suggestion not found"
  ], 404);
}

$conn->begin_transaction();

if ($decision === "approved") {
  $publisher = "Suggested";
  $subject = "General";
  $course = "General";
  $semester = null;
  $total = 1;
  $available = 1;
  $rack = "NEW";

  $stmt = $conn->prepare("
    INSERT INTO books (name, author, publisher, subject, course, semester, total, available, rack_position)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE total = total + 1, available = available + 1
  ");
  $stmt->bind_param("sssssiiis", $suggestion["book_name"], $suggestion["author"], $publisher, $subject, $course, $semester, $total, $available, $rack);
  $stmt->execute();
}

$stmt = $conn->prepare("UPDATE suggestions SET status = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ?");
$stmt->bind_param("si", $decision, $id);
$stmt->execute();

log_activity($conn, $suggestion["user_id"], "suggestion_" . $decision, $suggestion["book_name"]);

$conn->commit();

send_json([
  "success" => true,
  "message" => "Suggestion " . $decision
]);
?>
