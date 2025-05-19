<?php
require_once 'config/database.php';

// Check if students table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'students'");
if (mysqli_num_rows($table_check) == 0) {
    // Create students table
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
    // Check and modify gender column
    $alter_query = "ALTER TABLE students MODIFY COLUMN gender VARCHAR(20) NOT NULL";
    if (mysqli_query($conn, $alter_query)) {
        echo "Gender column modified successfully<br>";
    } else {
        echo "Error modifying gender column: " . mysqli_error($conn) . "<br>";
    }

    // Update existing gender values
    $update_query = "UPDATE students SET gender = CASE 
        WHEN gender = 'L' THEN 'Laki-laki'
        WHEN gender = 'P' THEN 'Perempuan'
        ELSE gender
        END";
    if (mysqli_query($conn, $update_query)) {
        echo "Gender values updated successfully<br>";
    } else {
        echo "Error updating gender values: " . mysqli_error($conn) . "<br>";
    }
}

echo "Database structure check completed.";
?> 