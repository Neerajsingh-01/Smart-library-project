<?php
include "db.php";

require_admin($conn);

$result = $conn->query("
  SELECT
    s.id,
    s.name,
    s.role,
    COUNT(ib.id) AS active_issues
  FROM students s
  LEFT JOIN issued_books ib ON ib.user_id = s.id AND ib.returned_at IS NULL
  GROUP BY s.id, s.name, s.role
  ORDER BY s.role, s.name
");

$users = [];
while ($row = $result->fetch_assoc()) {
  $users[] = $row;
}

send_json([
  "success" => true,
  "users" => $users
]);
?>
