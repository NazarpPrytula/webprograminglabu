<?php
include 'house.php';
include 'contact.php';
include 'about.php';
include 'footer.php';

// --- Налаштування з'єднання з MySQL ---
$host = 'db'; // назва сервісу MySQL у docker-compose
$user = 'root';
$pass = 'rootpassword';
$db = 'real_estate';

// --- Створюємо з'єднання ---
$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// --- Параметри пагінації будинків ---
$housesCount = isset($_GET['houses']) ? (int)$_GET['houses'] : 3;
$more = $housesCount + 3;
$less = max($housesCount - 3, 3);

// --- Отримуємо будинки з бази ---
$houses = getHouses($housesCount);

// --- Обробка форми Contact Me ---
$contact = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);

    $errors = [];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Невірний формат email";
    }

    if (strtotime($dob) > time()) {
        $errors[] = "Дата народження не може бути в майбутньому";
    }

    if (empty($errors)) {
        $query = "INSERT INTO contacts (name, email, phone, message, dob) 
                  VALUES ('$name', '$email', '$phone', '$message', '$dob')";
        if (mysqli_query($conn, $query)) {
            echo "<p style='color:green;'>Контактні дані успішно збережено!</p>";
            $contact = new ContactInformation($name, $email, $phone, $message, $dob);
        } else {
            echo "<p style='color:red;'>Помилка збереження: " . mysqli_error($conn) . "</p>";
        }
    } else {
        foreach ($errors as $err) {
            echo "<p style='color:red;'>$err</p>";
        }
    }
}

$location = "RIVNE, Ukraine";

// --- Функція для розрахунку середньої довжини полів форми ---
function calculateAverageLength($data) {
    $totalLength = 0;
    $fieldCount = count($data);

    foreach ($data as $field) {
        $totalLength += strlen($field);
    }

    return $fieldCount > 0 ? round($totalLength / $fieldCount) : 0;
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Real Estate</title>
    <link rel="stylesheet" href="./styles.css">
</head>
<body>

<div class="container">
    <h1>Welcome to Our Real Estate Website</h1>
    <?php echo getAboutBlock(); ?>
</div>

<section class="houses">
    <h2>Our Houses</h2>

    <div class="houses-list">
        <?php foreach ($houses as $house): ?>
            <div class="house">
                <h3><?= htmlspecialchars($house['name']) ?></h3>
                <img src="./images/image.jpg" alt="House Image">
                <p>Address: <?= htmlspecialchars($house['address']) ?></p>
                <p>Phone: <?= htmlspecialchars($house['phone']) ?></p>
                <p>Bedrooms: <?= htmlspecialchars($house['bedrooms']) ?></p>
                <p>Bathrooms: <?= htmlspecialchars($house['bathrooms']) ?></p>
                <p>Price: <?= number_format($house['price'], 0) ?> USD</p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="load-more">
        <a href="?houses=<?= $more ?>"><button>Показати більше</button></a>
        <?php if($housesCount > 3): ?>
            <a href="?houses=<?= $less ?>"><button>Показати менше</button></a>
        <?php endif; ?>
    </div>
</section>

<section class="contact">
    <h2>Contact Me</h2>

    <form method="POST">
        <input type="text" name="name" placeholder="Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="date" name="dob" placeholder="Date of Birth" required>
        <input type="text" name="phone" placeholder="Phone" required>
        <textarea name="message" placeholder="Message" required></textarea>
        <button type="submit">Send</button>
        <button type="reset">Clear Form</button>
    </form>

    <?php
    if ($contact) {
        echo $contact->formatAsTable();

        $formData = [
            $_POST['name'],
            $_POST['email'],
            $_POST['dob'],
            $_POST['phone'],
            $_POST['message']
        ];

        $averageLength = calculateAverageLength($formData);
        echo "<p>Середня довжина даних у формі: {$averageLength} символів</p>";
    }
    ?>
</section>

<?php echo getFooter($location); ?>

</body>
</html>
