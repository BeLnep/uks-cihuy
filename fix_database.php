<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'uks_db';

// Create connection without database
$conn = mysqli_connect($host, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Drop database if exists
$drop_db = "DROP DATABASE IF EXISTS $database";
if (!mysqli_query($conn, $drop_db)) {
    die("Error dropping database: " . mysqli_error($conn));
}

// Create database
$create_db = "CREATE DATABASE $database";
if (!mysqli_query($conn, $create_db)) {
    die("Error creating database: " . mysqli_error($conn));
}

// Select the database
if (!mysqli_select_db($conn, $database)) {
    die("Error selecting database: " . mysqli_error($conn));
}

// Create students table
$create_students = "CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nis VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    class VARCHAR(20) NOT NULL,
    gender ENUM('L', 'P') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $create_students)) {
    die("Error creating students table: " . mysqli_error($conn));
}

// Create users table
$create_users = "CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $create_users)) {
    die("Error creating users table: " . mysqli_error($conn));
}

// Create health_records table
$create_health_records = "CREATE TABLE health_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    date DATE NOT NULL,
    complaint TEXT NOT NULL,
    diagnosis TEXT,
    treatment TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
)";

if (!mysqli_query($conn, $create_health_records)) {
    die("Error creating health_records table: " . mysqli_error($conn));
}

// Create health_news table
$create_health_news = "CREATE TABLE health_news (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $create_health_news)) {
    die("Error creating health_news table: " . mysqli_error($conn));
}

// Insert default admin user
$default_password = password_hash('admin123', PASSWORD_DEFAULT);
$insert_admin = "INSERT INTO users (username, password, role) VALUES ('admin', '$default_password', 'admin')";
if (!mysqli_query($conn, $insert_admin)) {
    die("Error inserting default admin: " . mysqli_error($conn));
}

echo "Database setup completed successfully!<br>";
echo "Default admin credentials:<br>";
echo "Username: admin<br>";
echo "Password: admin123<br>";
echo "<br>You can now <a href='login.php'>login</a> or <a href='students.php'>manage students</a>.";
?> 