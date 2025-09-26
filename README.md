# PHP CRUD - Student Management System

A simple PHP + MySQL CRUD application to manage student records.  
This system allows administrators to add, view, search, update, and delete student information with a modern dark-themed UI.  

It also supports sorting, pagination, and server-side validation, ensuring clean and consistent student data management.

---

## Features

- Add new students  
- View and search student records  
- Edit/Update student details  
- Soft-delete students (confirmation modal before removal)  
- Sorting by columns (ID, Name, Email, Course, Year, etc.)  
- Pagination for long student lists  
- Search bar (filter records across all fields)  
- Dark theme design with consistent modals (Add, View, Edit, Delete)  
- Duplicate checking on email address  
- Success and error popups with Bootstrap modals  

---

## Tech StacK

- Frontend:  
  - HTML5  
  - CSS3 (custom `crud.css` dark theme)  
  - Bootstrap 5 + Bootstrap Icons  

- Backend:  
  - PHP 8+  
  - MySQL / MariaDB  

---

## Database Setup

Create the database and table:

SQL:
CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(50) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  middle_name VARCHAR(100),
  last_name VARCHAR(100) NOT NULL,
  suffix VARCHAR(20) NOT NULL DEFAULT 'N/A',
  email VARCHAR(190) NOT NULL UNIQUE,
  course VARCHAR(100) NOT NULL,
  year VARCHAR(10) NOT NULL,
  section VARCHAR(50) NOT NULL,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  remarks VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
