<?php

function getHouses($limit = 3)
{
    $limit = (int)$limit;

    // Важливо: 'db' — це назва сервісу MySQL у docker-compose
    $conn = mysqli_connect('db', 'root', 'rootpassword', 'real_estate');

    if (!$conn) {
        die("Connection error: " . mysqli_connect_error());
    }

    // Простий запит з обмеженням
    $query = "SELECT * FROM houses LIMIT $limit";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Query error: " . mysqli_error($conn));
    }

    $houses = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $houses[] = $row;
    }

    mysqli_close($conn);

    return $houses;
}
