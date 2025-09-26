<?php
require __DIR__ . '/db_connect.php';

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
  header("Location: select.php?status=error&msg=" . urlencode('Invalid record.'));
  exit;
}

$stmt = $mysqli->prepare("DELETE FROM students WHERE id=?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
  header("Location: select.php?status=success&msg=" . urlencode('Student deleted.'));
} else {
  header("Location: select.php?status=error&msg=" . urlencode('Delete failed.'));
}
