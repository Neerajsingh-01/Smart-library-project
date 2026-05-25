<?php
include "db.php";

require_login();

$search = trim($_GET["q"] ?? "");
$course = trim($_GET["course"] ?? "");
$semester = trim($_GET["semester"] ?? "");
$subject = trim($_GET["subject"] ?? "");

$sql = "
  SELECT
    b.id,
    b.name,
    b.author,
    b.publisher,
    b.subject,
    b.course,
    b.semester,
    b.rack_position,
    b.total,
    b.available,
    b.added_at
  FROM books b
  WHERE 1 = 1
";

$types = "";
$params = [];

if ($search !== "") {
  $sql .= " AND (b.name LIKE ? OR b.author LIKE ? OR b.subject LIKE ? OR b.course LIKE ?)";
  $term = "%" . $search . "%";
  $types .= "ssss";
  array_push($params, $term, $term, $term, $term);
}

if ($course !== "") {
  $sql .= " AND b.course = ?";
  $types .= "s";
  $params[] = $course;
}

if ($semester !== "") {
  $sql .= " AND b.semester = ?";
  $types .= "i";
  $params[] = intval($semester);
}

if ($subject !== "") {
  $sql .= " AND b.subject = ?";
  $types .= "s";
  $params[] = $subject;
}

$sql .= " ORDER BY b.name";

$stmt = $conn->prepare($sql);
if ($types !== "") {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$books = [];
while ($row = $result->fetch_assoc()) {
  $books[] = $row;
}

send_json([
  "success" => true,
  "books" => $books
]);
?>
