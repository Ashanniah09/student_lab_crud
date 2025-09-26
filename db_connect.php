<?php
// Show errors while building
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ---------- connection ---------- */
$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'student_lab_crud_db';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS);
if ($mysqli->connect_errno) {
  http_response_code(500);
  die('Failed to connect to MySQL: ' . $mysqli->connect_error);
}

// Create DB if needed and use it
$mysqli->query("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$mysqli->select_db($DB_NAME);
$mysqli->set_charset('utf8mb4');

/* ---------- table (matches your screenshot) ----------
   id, first_name, last_name, email, created_at,
   student_id, middle_name, course, year, section, remarks, status
   (NO suffix here)
------------------------------------------------------- */
$createSql = "CREATE TABLE IF NOT EXISTS students (
  id           INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  first_name   VARCHAR(100) NOT NULL,
  last_name    VARCHAR(100) NOT NULL,
  email        VARCHAR(190) NOT NULL UNIQUE,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  student_id   VARCHAR(50) DEFAULT NULL,
  middle_name  VARCHAR(100) DEFAULT NULL,
  course       VARCHAR(100) DEFAULT NULL,
  year         VARCHAR(20)  DEFAULT NULL,
  section      VARCHAR(50)  DEFAULT NULL,
  remarks      VARCHAR(255) DEFAULT NULL,
  status       VARCHAR(50)  DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$mysqli->query($createSql);
