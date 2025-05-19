<?php
require_once 'config/database.php';

// Check if students table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'students'");
if (mysqli_num_rows($table_check) == 0) {
    // Create students table if it doesn't exist
    $create_table = "CREATE TABLE students (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nis VARCHAR(20) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        class VARCHAR(20) NOT NULL,
        gender VARCHAR(20) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    if (mysqli_query($conn, $create_table)) {
        echo "Students table created successfully<br>";
    } else {
        echo "Error creating students table: " . mysqli_error($conn) . "<br>";
    }
} else {
    // Check if gender column exists
    $columns_check = mysqli_query($conn, "SHOW COLUMNS FROM students LIKE 'gender'");
    if (mysqli_num_rows($columns_check) == 0) {
        // Add gender column if it doesn't exist
        $alter_query = "ALTER TABLE students ADD COLUMN gender VARCHAR(20) NOT NULL DEFAULT 'Laki-laki'";
        if (mysqli_query($conn, $alter_query)) {
            echo "Gender column added successfully<br>";
        } else {
            echo "Error adding gender column: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "Gender column already exists<br>";
    }
}

// Check for any NULL or empty gender values and fix them
$update_query = "UPDATE students SET gender = 'Laki-laki' WHERE gender IS NULL OR gender = ''";
if (mysqli_query($conn, $update_query)) {
    echo "Gender data fixed successfully<br>";
} else {
    echo "Error fixing gender data: " . mysqli_error($conn) . "<br>";
}

echo "Table check completed.";
?> 