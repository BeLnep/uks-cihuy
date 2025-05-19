<?php
require_once 'config/database.php';

// Check students table structure
$structure_query = "DESCRIBE students";
$structure_result = mysqli_query($conn, $structure_query);

echo "<h3>Table Structure:</h3>";
echo "<pre>";
while ($row = mysqli_fetch_assoc($structure_result)) {
    print_r($row);
}
echo "</pre>";

// Check students data
$data_query = "SELECT * FROM students";
$data_result = mysqli_query($conn, $data_query);

echo "<h3>Student Data:</h3>";
echo "<pre>";
while ($row = mysqli_fetch_assoc($data_result)) {
    print_r($row);
}
echo "</pre>";
?> 