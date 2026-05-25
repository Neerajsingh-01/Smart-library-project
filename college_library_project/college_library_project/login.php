<?php
include "db.php";

$id = trim($_POST["id"] ?? "");
$password = $_POST["password"] ?? "";

if ($id === "" || $password === "") {
  send_json([
    "success" => false,
    "message" => "Enter ID and password"
  ], 400);
}

$stmt = $conn->prepare("SELECT id, name, password, role FROM students WHERE id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
  send_json([
    "success" => false,
    "message" => "Invalid login"
  ], 401);
}

$storedPassword = $student["password"];
$valid = password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);

if (!$valid) {
  send_json([
    "success" => false,
    "message" => "Invalid login"
  ], 401);
}

$_SESSION["student_id"] = $student["id"];
$_SESSION["student_name"] = $student["name"];
$_SESSION["role"] = role_name($student["role"]);

log_activity($conn, $student["id"], "login", "Logged in");

send_json([
  "success" => true,
  "message" => "Login successful",
  "user" => [
    "id" => $student["id"],
    "name" => $student["name"],
    "role" => role_name($student["role"])
  ]
]);
?>
