<?php
include "db.php";

require_admin($conn);

$result = $conn->query("
  SELECT
    ib.id,
    ib.issued_at,
    ib.returned_at,
    s.id AS student_id,
    s.name AS student_name,
    b.name AS book_name,
    b.author
  FROM issued_books ib
  JOIN students s ON s.id = ib.user_id
  JOIN books b ON b.id = ib.book_id
  ORDER BY ib.issued_at DESC
  LIMIT 100
");

$records = [];
while ($row = $result->fetch_assoc()) {
  $records[] = $row;
}

send_json([
  "success" => true,
  "records" => $records
]);
?>
