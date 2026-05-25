<?php
include "db.php";

require_admin($conn);

$id = intval($_POST["id"] ?? 0);

if ($id <= 0) {
  send_json([
    "success" => false,
    "message" => "Invalid book"
  ], 400);
}

try {
  $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
} catch (mysqli_sql_exception $error) {
  send_json([
    "success" => false,
    "message" => "Book has issue/notification history and cannot be deleted"
  ], 409);
}

if ($stmt->affected_rows === 0) {
  send_json([
    "success" => false,
    "message" => "Book not found"
  ], 404);
}

send_json([
  "success" => true,
  "message" => "Book deleted"
]);
?>
