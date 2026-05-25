<?php
include "db.php";

$studentId = require_student($conn);
$name = trim($_POST["name"] ?? "");
$author = trim($_POST["author"] ?? "");
$edition = trim($_POST["edition"] ?? "");

if ($name === "" || $author === "") {
  send_json([
    "success" => false,
    "message" => "Book name and author are required"
  ], 400);
}

$stmt = $conn->prepare("INSERT INTO suggestions (user_id, book_name, author, edition) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $studentId, $name, $author, $edition);
$stmt->execute();

log_activity($conn, $studentId, "suggestion", "Suggested " . $name);

send_json([
  "success" => true,
  "message" => "Suggestion sent to admin"
]);
?>
