<?php
include "db.php";

session_destroy();

send_json([
  "success" => true,
  "message" => "Logged out"
]);
?>
