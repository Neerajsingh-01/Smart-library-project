<?php
$sessionPath = __DIR__ . DIRECTORY_SEPARATOR . "sessions";
if (!is_dir($sessionPath)) {
  mkdir($sessionPath, 0777, true);
}
session_save_path($sessionPath);
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$username = "root";
$password = "";
$database = "college_library";

try {
  $conn = new mysqli($host, $username, $password, $database);
  $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $error) {
  send_json([
    "success" => false,
    "message" => "Database connection failed"
  ], 500);
}

function send_json($data, $statusCode = 200) {
  http_response_code($statusCode);
  header("Content-Type: application/json");
  echo json_encode($data);
  exit;
}

function column_exists($conn, $table, $column) {
  $stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
  ");
  $stmt->bind_param("ss", $table, $column);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  return intval($row["total"]) > 0;
}

function role_name($role) {
  return strtolower(trim((string) $role));
}

function require_login() {
  if (empty($_SESSION["student_id"])) {
    send_json([
      "success" => false,
      "message" => "Please login first"
    ], 401);
  }

  return $_SESSION["student_id"];
}

function current_user_role($conn) {
  $role = role_name($_SESSION["role"] ?? "student");

  if (!empty($_SESSION["student_id"]) && column_exists($conn, "students", "role")) {
    $stmt = $conn->prepare("SELECT role FROM students WHERE id = ?");
    $stmt->bind_param("s", $_SESSION["student_id"]);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
      $role = role_name($row["role"]);
      $_SESSION["role"] = $role;
    }
  }

  return $role;
}

function current_user($conn) {
  return [
    "id" => $_SESSION["student_id"] ?? null,
    "name" => $_SESSION["student_name"] ?? null,
    "role" => current_user_role($conn)
  ];
}

function require_role($conn, $role) {
  $userId = require_login();

  if (current_user_role($conn) !== $role) {
    send_json([
      "success" => false,
      "message" => ucfirst($role) . " access required"
    ], 403);
  }

  return $userId;
}

function require_student($conn) {
  return require_role($conn, "student");
}

function require_admin($conn) {
  return require_role($conn, "admin");
}

function log_activity($conn, $userId, $action, $details = "") {
  $stmt = $conn->prepare("INSERT INTO activities (user_id, action, details) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $userId, $action, $details);
  $stmt->execute();
}

function promote_next_notification($conn, $bookId) {
  $stmt = $conn->prepare("
    SELECT id
    FROM notifications
    WHERE book_id = ? AND status = 'waiting'
    ORDER BY requested_at ASC
    LIMIT 1
  ");
  $stmt->bind_param("i", $bookId);
  $stmt->execute();
  $next = $stmt->get_result()->fetch_assoc();

  if ($next) {
    $stmt = $conn->prepare("UPDATE notifications SET status = 'notified', notified_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("i", $next["id"]);
    $stmt->execute();
  }
}
?>
