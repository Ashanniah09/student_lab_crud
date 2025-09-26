<?php
require __DIR__ . '/db_connect.php';

$id    = intval($_POST['id'] ?? 0);
$first = trim($_POST['first_name'] ?? '');
$last  = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');

$errors = [];
if ($id <= 0) $errors[] = 'Invalid record.';
if ($first === '') $errors[] = 'First name is required.';
if ($last === '')  $errors[] = 'Last name is required.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';

if ($errors) {
  $msg = urlencode(implode(' ', $errors));
  header("Location: select.php?status=error&msg=$msg");
  exit;
}

$stmt = $mysqli->prepare("UPDATE students SET first_name=?, last_name=?, email=? WHERE id=?");
$stmt->bind_param('sssi', $first, $last, $email, $id);

if ($stmt->execute()) {
  header("Location: select.php?status=success&msg=" . urlencode('Student updated.'));
} else {
  $dup = $mysqli->errno === 1062 ? ' Email already exists.' : '';
  header("Location: select.php?status=error&msg=" . urlencode('Update failed.' . $dup));
}
