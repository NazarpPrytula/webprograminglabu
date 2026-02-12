<?php
$conn = mysqli_connect('db', 'root', 'rootpassword', 'real_estate');

if (!$conn) {
    die("Connection error: " . mysqli_connect_error());
}

$result = mysqli_query($conn, "SELECT * FROM houses");

if (!$result) {
    die("Query error: " . mysqli_error($conn));
}

$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

echo "<pre>";
print_r($rows);
echo "</pre>";

mysqli_close($conn);
