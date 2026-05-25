<?php
include "db.php";

require_login();
$user = current_user($conn);

if ($user["role"] === "admin") {
  $result = $conn->query("
    SELECT sg.*, st.name AS student_name
    FROM suggestions sg
    JOIN students st ON st.id = sg.user_id
    ORDER BY sg.created_at DESC
  ");
} else {
  $stmt = $conn->prepare("SELECT * FROM suggestions WHERE user_id = ? ORDER BY created_at DESC");
  $stmt->bind_param("s", $user["id"]);
  $stmt->execute();
  $result = $stmt->get_result();
}

$suggestions = [];
while ($row = $result->fetch_assoc()) {
  $suggestions[] = $row;
}

send_json([
  "success" => true,
  "suggestions" => $suggestions
]);
?>
