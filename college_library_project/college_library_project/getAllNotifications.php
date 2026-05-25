<?php
include "db.php";

require_admin($conn);

$result = $conn->query("
  SELECT
    n.status,
    n.requested_at,
    n.notified_at,
    s.name AS student_name,
    b.name AS book_name
  FROM notifications n
  JOIN students s ON s.id = n.user_id
  JOIN books b ON b.id = n.book_id
  ORDER BY n.requested_at DESC
  LIMIT 80
");

$notifications = [];
while ($row = $result->fetch_assoc()) {
  $notifications[] = $row;
}

send_json([
  "success" => true,
  "notifications" => $notifications
]);
?>
