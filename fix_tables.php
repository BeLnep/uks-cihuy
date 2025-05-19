<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'uks_db';

echo "<h2>Fixing Database Tables</h2>";

// Create connection
$conn = mysqli_connect($host, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "✓ Connected to MySQL server<br>";

// Select the database
if (!mysqli_select_db($conn, $database)) {
    die("Error selecting database: " . mysqli_error($conn));
}
echo "✓ Selected database '$database'<br>";

// Drop students table if exists
$drop_table = "DROP TABLE IF EXISTS students";
if (!mysqli_query($conn, $drop_table)) {
    die("Error dropping students table: " . mysqli_error($conn));
}
echo "✓ Dropped existing students table<br>";

// Create students table with correct structure
$create_table = "CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nis VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    class VARCHAR(20) NOT NULL,
    gender ENUM('L', 'P') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB";

if (!mysqli_query($conn, $create_table)) {
    die("Error creating students table: " . mysqli_error($conn));
}
echo "✓ Created students table with correct structure<br>";

// Insert sample data
$sample_data = [
    ['2024001', 'Ahmad Fauzi', 'X IPA 1', 'L'],
    ['2024002', 'Siti Nurhaliza', 'X IPA 1', 'P'],
    ['2024003', 'Budi Santoso', 'X IPA 2', 'L'],
    ['2024004', 'Rina Kartika', 'X IPA 2', 'P'],
    ['2024005', 'Dewi Lestari', 'XI IPA 1', 'P']
];

foreach ($sample_data as $student) {
    $insert = "INSERT INTO students (nis, name, class, gender) VALUES ('$student[0]', '$student[1]', '$student[2]', '$student[3]')";
    if (!mysqli_query($conn, $insert)) {
        die("Error inserting sample data: " . mysqli_error($conn));
    }
}
echo "✓ Inserted sample data<br>";

// Verify table structure
echo "<h3>Students Table Structure:</h3>";
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

// Test query
echo "<h3>Testing Query:</h3>";
$test_query = "SELECT id FROM students WHERE nis = '2024001'";
$test_result = mysqli_query($conn, $test_query);
if ($test_result) {
    echo "✓ Query executed successfully<br>";
    $row = mysqli_fetch_assoc($test_result);
    echo "Found student with ID: " . $row['id'] . "<br>";
} else {
    echo "✗ Query failed: " . mysqli_error($conn) . "<br>";
}

echo "<br><strong>Table fix completed!</strong><br>";
echo "You can now <a href='students.php'>go to the students page</a>.";
?> 