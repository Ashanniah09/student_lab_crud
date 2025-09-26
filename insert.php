<?php
require __DIR__ . '/db_connect.php';

/* --------- Grab & normalize POST --------- */
$student_id = trim($_POST['student_id'] ?? '');
$course     = trim($_POST['course'] ?? '');
$year       = trim($_POST['year'] ?? '');          // <-- matches table
$first      = trim($_POST['first_name'] ?? '');
$middle     = trim($_POST['middle_name'] ?? '');
$last       = trim($_POST['last_name'] ?? '');
$suffix     = trim($_POST['suffix'] ?? 'N/A');     // UI only for now
$section    = trim($_POST['section'] ?? '');
$status     = trim($_POST['status'] ?? '');
$email      = trim($_POST['email'] ?? '');
$remarks    = trim($_POST['remarks'] ?? '');

/* --------- Validation (server-side) --------- */
$errors = [];
if ($student_id === '') $errors[] = 'Student ID is required.';
if ($course === '')     $errors[] = 'Course is required.';
if ($year === '')       $errors[] = 'Year is required.';
if ($first === ''  || !preg_match('/^[A-Za-z\s]+$/', $first)) $errors[] = 'First name is required and must contain letters only.';
if ($last  === ''  || !preg_match('/^[A-Za-z\s]+$/', $last))  $errors[] = 'Last name is required and must contain letters only.';
if ($suffix === '')     $errors[] = 'A suffix is required. Select “N/A” if you don’t have one.';
if ($section === '')    $errors[] = 'Section is required.';
if ($status === '')     $errors[] = 'Status is required.';
if ($email === ''  || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';

if ($errors) {
  $msg = urlencode(implode(' ', $errors));
  header("Location: select.php?status=error&msg=$msg");
  exit;
}

/* --------- Insert (no suffix column yet) ---------
   If you later add `suffix` to the table, include it here.
--------------------------------------------------- */
$sql = "INSERT INTO students
  (student_id, first_name, middle_name, last_name, email, course, year, section, status, remarks)
  VALUES (?,?,?,?,?,?,?,?,?,?)";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param(
  'ssssssssss',
  $student_id, $first, $middle, $last, $email, $course, $year, $section, $status, $remarks
);

if ($stmt->execute()) {
  header("Location: select.php?status=success&msg=" . urlencode('Student added.'));
} else {
  $dup = $mysqli->errno === 1062 ? ' Email already exists.' : '';
  header("Location: select.php?status=error&msg=" . urlencode('Insert failed.' . $dup));
}
