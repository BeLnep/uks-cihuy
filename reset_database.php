<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'uks_db';

echo "<h2>Complete Database Reset</h2>";

// Create connection without database
$conn = mysqli_connect($host, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "✓ Connected to MySQL server<br>";

// Drop database if exists
$drop_db = "DROP DATABASE IF EXISTS $database";
if (!mysqli_query($conn, $drop_db)) {
    die("Error dropping database: " . mysqli_error($conn));
}
echo "✓ Dropped existing database<br>";

// Create database
$create_db = "CREATE DATABASE $database";
if (!mysqli_query($conn, $create_db)) {
    die("Error creating database: " . mysqli_error($conn));
}
echo "✓ Created new database<br>";

// Select the database
if (!mysqli_select_db($conn, $database)) {
    die("Error selecting database: " . mysqli_error($conn));
}
echo "✓ Selected database<br>";

// Create users table
$create_users = "CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB";

if (!mysqli_query($conn, $create_users)) {
    die("Error creating users table: " . mysqli_error($conn));
}
echo "✓ Created users table<br>";

// Create students table
$create_students = "CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nis VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    class VARCHAR(20) NOT NULL,
    gender ENUM('L', 'P') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB";

if (!mysqli_query($conn, $create_students)) {
    die("Error creating students table: " . mysqli_error($conn));
}
echo "✓ Created students table<br>";

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
) ENGINE=InnoDB";

if (!mysqli_query($conn, $create_health_records)) {
    die("Error creating health_records table: " . mysqli_error($conn));
}
echo "✓ Created health_records table<br>";

// Create health_news table
$create_health_news = "CREATE TABLE health_news (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB";

if (!mysqli_query($conn, $create_health_news)) {
    die("Error creating health_news table: " . mysqli_error($conn));
}
echo "✓ Created health_news table<br>";

// Insert default admin user
$default_password = password_hash('admin123', PASSWORD_DEFAULT);
$insert_admin = "INSERT INTO users (username, password, role) VALUES ('admin', '$default_password', 'admin')";
if (!mysqli_query($conn, $insert_admin)) {
    die("Error inserting default admin: " . mysqli_error($conn));
}
echo "✓ Created default admin user<br>";

// Insert sample students
$sample_students = [
    ['2024001', 'Ahmad Fauzi', 'X IPA 1', 'L'],
    ['2024002', 'Siti Nurhaliza', 'X IPA 1', 'P'],
    ['2024003', 'Budi Santoso', 'X IPA 2', 'L'],
    ['2024004', 'Rina Kartika', 'X IPA 2', 'P'],
    ['2024005', 'Dewi Lestari', 'XI IPA 1', 'P']
];

foreach ($sample_students as $student) {
    $insert_student = "INSERT INTO students (nis, name, class, gender) VALUES ('$student[0]', '$student[1]', '$student[2]', '$student[3]')";
    if (!mysqli_query($conn, $insert_student)) {
        die("Error inserting sample student: " . mysqli_error($conn));
    }
}
echo "✓ Inserted sample students<br>";

// Verify table structures
echo "<h3>Table Structures:</h3>";

$tables = ['users', 'students', 'health_records', 'health_news'];
foreach ($tables as $table) {
    echo "<h4>$table table:</h4>";
    $result = mysqli_query($conn, "DESCRIBE $table");
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test queries
echo "<h3>Testing Queries:</h3>";

// Test students table
$test_query = "SELECT id FROM students WHERE nis = '2024001'";
$test_result = mysqli_query($conn, $test_query);
if ($test_result) {
    echo "✓ Students query executed successfully<br>";
    $row = mysqli_fetch_assoc($test_result);
    echo "Found student with ID: " . $row['id'] . "<br>";
} else {
    echo "✗ Students query failed: " . mysqli_error($conn) . "<br>";
}

echo "<br><strong>Database reset completed successfully!</strong><br>";
echo "Default admin credentials:<br>";
echo "Username: admin<br>";
echo "Password: admin123<br>";
echo "<br>You can now <a href='login.php'>login</a> or <a href='students.php'>manage students</a>.";
?> 