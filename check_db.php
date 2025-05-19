<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'uks_db';

echo "<h2>Database Diagnostic Tool</h2>";

// Create connection without database
$conn = mysqli_connect($host, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "✓ Connected to MySQL server<br>";

// Check if database exists
$db_check = mysqli_query($conn, "SHOW DATABASES LIKE '$database'");
if (mysqli_num_rows($db_check) == 0) {
    echo "✗ Database '$database' does not exist. Creating it...<br>";
    if (!mysqli_query($conn, "CREATE DATABASE $database")) {
        die("Error creating database: " . mysqli_error($conn));
    }
    echo "✓ Database created successfully<br>";
} else {
    echo "✓ Database '$database' exists<br>";
}

// Select the database
if (!mysqli_select_db($conn, $database)) {
    die("Error selecting database: " . mysqli_error($conn));
}
echo "✓ Selected database '$database'<br>";

// Check if students table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'students'");
if (mysqli_num_rows($table_check) == 0) {
    echo "✗ Table 'students' does not exist. Creating it...<br>";
    
    // Create students table
    $create_table = "CREATE TABLE students (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nis VARCHAR(20) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        class VARCHAR(20) NOT NULL,
        gender ENUM('L', 'P') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $create_table)) {
        die("Error creating table: " . mysqli_error($conn));
    }
    echo "✓ Table 'students' created successfully<br>";
} else {
    echo "✓ Table 'students' exists<br>";
    
    // Check table structure
    echo "<h3>Table Structure:</h3>";
    $result = mysqli_query($conn, "DESCRIBE students");
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
    
    // Check if nis column exists
    $column_check = mysqli_query($conn, "SHOW COLUMNS FROM students LIKE 'nis'");
    if (mysqli_num_rows($column_check) == 0) {
        echo "✗ Column 'nis' does not exist. Adding it...<br>";
        if (!mysqli_query($conn, "ALTER TABLE students ADD COLUMN nis VARCHAR(20) UNIQUE NOT NULL AFTER id")) {
            die("Error adding column: " . mysqli_error($conn));
        }
        echo "✓ Column 'nis' added successfully<br>";
    } else {
        echo "✓ Column 'nis' exists<br>";
    }
}

// Test query
echo "<h3>Testing Query:</h3>";
$test_query = "SELECT id FROM students WHERE nis = 'test'";
$test_result = mysqli_query($conn, $test_query);
if ($test_result) {
    echo "✓ Query executed successfully<br>";
} else {
    echo "✗ Query failed: " . mysqli_error($conn) . "<br>";
}

echo "<br><strong>Diagnostic complete!</strong><br>";
echo "You can now <a href='students.php'>go to the students page</a>.";
?> 