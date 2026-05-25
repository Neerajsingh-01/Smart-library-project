<?php
include "db.php";

require_admin($conn);

$id = intval($_POST["id"] ?? 0);
$name = trim($_POST["name"] ?? "");
$author = trim($_POST["author"] ?? "");
$publisher = trim($_POST["publisher"] ?? "");
$subject = trim($_POST["subject"] ?? "");
$course = trim($_POST["course"] ?? "");
$semesterInput = $_POST["semester"] ?? "";
$semester = $semesterInput === "" ? null : intval($semesterInput);
$total = intval($_POST["total"] ?? 1);
$available = intval($_POST["available"] ?? $total);
$rack = trim($_POST["rack_position"] ?? "");

if ($name === "" || $author === "" || $total < 0 || $available < 0 || $available > $total) {
  send_json([
    "success" => false,
    "message" => "Enter valid book details"
  ], 400);
}

try {
  if ($id > 0) {
    $stmt = $conn->prepare("
      UPDATE books
      SET name = ?, author = ?, publisher = ?, subject = ?, course = ?, semester = ?, total = ?, available = ?, rack_position = ?
      WHERE id = ?
    ");
    $stmt->bind_param("sssssiiisi", $name, $author, $publisher, $subject, $course, $semester, $total, $available, $rack, $id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
      $check = $conn->prepare("SELECT id FROM books WHERE id = ?");
      $check->bind_param("i", $id);
      $check->execute();

      if ($check->get_result()->num_rows === 0) {
        send_json([
          "success" => false,
          "message" => "Book not found"
        ], 404);
      }
    }

    if ($available > 0) {
      promote_next_notification($conn, $id);
    }

    $message = "Book updated";
  } else {
    $stmt = $conn->prepare("
      INSERT INTO books (name, author, publisher, subject, course, semester, total, available, rack_position)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssssiiis", $name, $author, $publisher, $subject, $course, $semester, $total, $available, $rack);
    $stmt->execute();
    $id = $conn->insert_id;
    $message = "Book added";
  }
} catch (mysqli_sql_exception $error) {
  send_json([
    "success" => false,
    "message" => "Book with same name and author already exists"
  ], 409);
}

send_json([
  "success" => true,
  "message" => $message,
  "id" => $id
]);
?>
