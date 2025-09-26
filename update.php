<?php
require __DIR__ . '/db_connect.php';

// Collect & sanitize 
$id            = intval($_POST['id'] ?? 0);
$student_id    = trim($_POST['student_id'] ?? '');
$course        = trim($_POST['course'] ?? '');
$year          = trim($_POST['year'] ?? '');
$first_name    = trim($_POST['first_name'] ?? '');
$middle_name   = trim($_POST['middle_name'] ?? '');
$last_name     = trim($_POST['last_name'] ?? '');
$suffix        = trim($_POST['suffix'] ?? '');
$section       = trim($_POST['section'] ?? '');
$status        = trim($_POST['status'] ?? '');
$email         = trim($_POST['email'] ?? '');
$remarks       = trim($_POST['remarks'] ?? '');

// Server-side validation
$errors = [];
if ($id <= 0) $errors[] = 'Invalid record.';

if ($student_id === '' || !preg_match('/^[A-Za-z0-9\-_.]+$/', $student_id)) {
  $errors[] = 'Student ID is required and must be letters/numbers, dashes, underscores, or dots.';
}

if ($course === '' || !preg_match('/^[A-Za-z0-9\s\-&().\/]+$/', $course)) {
  $errors[] = 'Course is required and must use allowed characters.';
}

if ($year === '') $errors[] = 'Year is required.';
if ($first_name === '' || !preg_match('/^[A-Za-z\s]+$/', $first_name)) {
  $errors[] = 'First name is required and letters only.';
}
if ($middle_name !== '' && !preg_match('/^[A-Za-z\s]*$/', $middle_name)) {
  $errors[] = 'Middle name must contain letters only.';
}
if ($last_name === '' || !preg_match('/^[A-Za-z\s]+$/', $last_name)) {
  $errors[] = 'Last name is required and letters only.';
}
if ($suffix === '') $errors[] = 'Suffix is required.';
if ($section === '' || !preg_match('/^[A-Za-z0-9\- ]+$/', $section)) {
  $errors[] = 'Section is required and can include letters, numbers, spaces or dashes.';
}
if ($status === '') $errors[] = 'Status is required.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $errors[] = 'Valid email is required.';
}

if ($errors) {
  $msg = urlencode(implode(' ', $errors));
  header("Location: select.php?status=error&msg=$msg");
  exit;
}

// Update all the columns
$sql = "UPDATE students
        SET student_id = ?, course = ?, year = ?, first_name = ?, middle_name = ?, last_name = ?,
            suffix = ?, section = ?, status = ?, email = ?, remarks = ?
        WHERE id = ?";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
  header("Location: select.php?status=error&msg=" . urlencode('Update failed: prepare error.'));
  exit;
}

$stmt->bind_param(
  'sssssssssssi',
  $student_id, $course, $year, $first_name, $middle_name, $last_name,
  $suffix, $section, $status, $email, $remarks, $id
);

if ($stmt->execute()) {
  header("Location: select.php?status=success&msg=" . urlencode('Student updated.'));
  exit;
} else {
  $dup = $mysqli->errno === 1062 ? ' Duplicate value exists (email or student ID).' : '';
  header("Location: select.php?status=error&msg=" . urlencode('Update failed.' . $dup));
  exit;
}
