<?php
include "db.php";

$studentId = require_student($conn);

$stmt = $conn->prepare("
  SELECT ib.id, ib.issued_at, ib.returned_at, b.name, b.author
  FROM issued_books ib
  JOIN books b ON b.id = ib.book_id
  WHERE ib.user_id = ?
  ORDER BY ib.issued_at DESC
  LIMIT 30
");
$stmt->bind_param("s", $studentId);
$stmt->execute();
$result = $stmt->get_result();

$issued = [];
while ($row = $result->fetch_assoc()) {
  $issued[] = $row;
}

send_json([
  "success" => true,
  "issued" => $issued
]);
?>
