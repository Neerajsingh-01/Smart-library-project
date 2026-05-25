<?php
include "db.php";

$studentId = require_student($conn);

$stmt = $conn->prepare("
  SELECT
    n.id,
    n.status,
    n.requested_at,
    n.notified_at,
    b.id AS book_id,
    b.name,
    b.author,
    b.available
  FROM notifications n
  JOIN books b ON b.id = n.book_id
  WHERE n.user_id = ? AND n.status IN ('waiting', 'notified')
  ORDER BY n.requested_at ASC
");
$stmt->bind_param("s", $studentId);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
  if (intval($row["available"]) > 0 && $row["status"] === "waiting") {
    promote_next_notification($conn, intval($row["book_id"]));

    $check = $conn->prepare("SELECT status, notified_at FROM notifications WHERE id = ?");
    $check->bind_param("i", $row["id"]);
    $check->execute();
    $fresh = $check->get_result()->fetch_assoc();
    $row["status"] = $fresh["status"];
    $row["notified_at"] = $fresh["notified_at"];
  }

  $notifications[] = $row;
}

send_json([
  "success" => true,
  "notifications" => $notifications
]);
?>
