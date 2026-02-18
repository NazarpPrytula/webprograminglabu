<?php

// Функція для підключення до бази (щоб не дублювати код)
function db_connect() {
    $conn = mysqli_connect('db', 'root', 'rootpassword', 'real_estate');
    if (!$conn) {
        die("Connection error: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, "utf8mb4");
    return $conn;
}

// Завдання 2 та 6: Отримання будинків з фільтрацією
function getHouses($limit = 3, $filters = []) {
    $conn = db_connect();
    $limit = (int)$limit;

    // Базовий запит з JOIN для отримання назви категорії
    $sql = "SELECT h.*, c.name as category_name 
            FROM houses h 
            LEFT JOIN categories c ON h.category_id = c.id 
            WHERE 1=1";

    // Фільтр по назві
    if (!empty($filters['search'])) {
        $search = mysqli_real_escape_string($conn, $filters['search']);
        $sql .= " AND h.name LIKE '%$search%'";
    }

    // Фільтр по ціні
    if (isset($filters['max_price'])) {
        $max = (int)$filters['max_price'];
        $sql .= " AND h.price <= $max";
    }

    // Фільтр по категорії
    if (!empty($filters['category'])) {
        $catId = (int)$filters['category'];
        $sql .= " AND h.category_id = $catId";
    }

    $sql .= " LIMIT $limit";
    
    $result = mysqli_query($conn, $sql);
    $houses = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    mysqli_close($conn);
    return $houses;
}

// Завдання 4: Отримання будинків для слайдера
function getFeaturedHouses() {
    $conn = db_connect();
    $sql = "SELECT * FROM houses WHERE is_featured = 1 LIMIT 10";
    $result = mysqli_query($conn, $sql);
    $houses = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_close($conn);
    return $houses;
}

// Завдання 5: Отримання всіх категорій для списку
function getCategories() {
    $conn = db_connect();
    $result = mysqli_query($conn, "SELECT * FROM categories");
    $categories = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_close($conn);
    return $categories;
}

// Завдання 2-3: Обробка лайків через Cookie
if (isset($_POST['like_house_id'])) {
    $id = (int)$_POST['like_house_id'];
    $cookieName = "liked_house_" . $id;

    // Перевірка, чи користувач вже лайкав цей будинок
    if (!isset($_COOKIE[$cookieName])) {
        $conn = db_connect();
        mysqli_query($conn, "UPDATE houses SET likes = likes + 1 WHERE id = $id");
        mysqli_close($conn);

        // Встановлюємо куку на 30 днів, щоб не можна було накрутити
        setcookie($cookieName, "true", time() + (86400 * 30), "/");
    }

    // Повертаємо користувача назад на головну
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}