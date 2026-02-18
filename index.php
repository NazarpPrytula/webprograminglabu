<?php
include 'house.php';
include 'contact.php';
include 'about.php';
include 'footer.php';

// --- Налаштування з'єднання ---
$host = 'db';
$user = 'root';
$pass = 'rootpassword';
$db = 'real_estate';

$conn = mysqli_connect($host, $user, $pass, $db);
mysqli_set_charset($conn, "utf8mb4");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// --- Завдання 8: Генерація CSV звіту ---
if (isset($_POST['get_report'])) {
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    
    $query = "SELECT name, price, sold_at FROM houses WHERE is_sold = 1 AND sold_at BETWEEN '$start 00:00:00' AND '$end 23:59:59'";
    $result = mysqli_query($conn, $query);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sales_report.csv"');
    
    $output = fopen('php://output', 'w');
    // Додаємо BOM для коректного відображення кирилиці в Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['Назва будинку', 'Ціна (USD)', 'Дата продажу']);
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// --- Параметри пагінації та фільтрації ---
$housesCount = isset($_GET['houses']) ? (int)$_GET['houses'] : 3;
$filters = [
    'search' => $_GET['search'] ?? '',
    'max_price' => isset($_GET['max_price']) ? (int)$_GET['max_price'] : 1000000,
    'category' => $_GET['category'] ?? ''
];

$more = $housesCount + 3;
$less = max($housesCount - 3, 3);

// Отримуємо дані
$houses = getHouses($housesCount, $filters);
$featuredHouses = getFeaturedHouses();
$categories = getCategories();

// --- Обробка форми Contact Me ---
$contact = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);

    $errors = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Невірний формат email";
    if (strtotime($dob) > time()) $errors[] = "Дата народження не може бути в майбутньому";

    if (empty($errors)) {
        $query = "INSERT INTO contacts (name, email, phone, message, dob) VALUES ('$name', '$email', '$phone', '$message', '$dob')";
        if (mysqli_query($conn, $query)) {
            $contact = new ContactInformation($name, $email, $phone, $message, $dob);
        }
    }
}

$location = "RIVNE, Ukraine";

function calculateAverageLength($data) {
    $totalLength = 0;
    $fieldCount = count($data);
    foreach ($data as $field) { $totalLength += strlen($field); }
    return $fieldCount > 0 ? round($totalLength / $fieldCount) : 0;
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Real Estate Elite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./styles.css">
</head>
<body class="bg-gray-50 text-gray-900">

<div class="container mx-auto p-6">
    <h1 class="text-4xl font-bold text-center my-8">Welcome to Our Real Estate Website</h1>
    <div class="bg-white p-6 rounded-lg shadow-md mb-10">
        <?php echo getAboutBlock(); ?>
    </div>
</div>

<?php if (!empty($featuredHouses)): ?>
<section class="bg-slate-800 py-12 mb-12">
    <div class="container mx-auto px-6">
        <h2 class="text-3xl font-bold text-white mb-6 text-center">🔥 Гарячі пропозиції</h2>
        <div class="flex overflow-x-auto pb-4 gap-6 snap-x">
            <?php foreach ($featuredHouses as $f): ?>
                <div class="min-w-[320px] bg-white rounded-xl shadow-2xl overflow-hidden snap-center transform hover:scale-105 transition">
                    <img src="./images/image.jpg" class="w-full h-48 object-cover">
                    <div class="p-4">
                        <h3 class="font-bold text-xl"><?= htmlspecialchars($f['name']) ?></h3>
                        <p class="text-blue-600 font-bold text-lg"><?= number_format($f['price']) ?> USD</p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="container mx-auto px-6 mb-12">
    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
        <h2 class="text-xl font-bold mb-4">Пошук нерухомості</h2>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <input type="text" name="search" placeholder="Назва будинку..." value="<?= htmlspecialchars($filters['search']) ?>" class="border p-3 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            
            <select name="category" class="border p-3 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">Всі категорії</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $filters['category'] == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="flex flex-col">
                <label class="text-sm font-medium">Макс. ціна: <span class="text-blue-600 font-bold" id="priceLabel"><?= number_format($filters['max_price']) ?></span> USD</label>
                <input type="range" name="max_price" min="0" max="1000000" step="10000" value="<?= $filters['max_price'] ?>" class="mt-2" oninput="document.getElementById('priceLabel').innerText = Number(this.value).toLocaleString()">
            </div>

            <button type="submit" class="bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition">Застосувати фільтри</button>
        </form>
    </div>
</section>

<section class="container mx-auto px-6 mb-12">
    <h2 class="text-3xl font-bold mb-8">Наші будинки</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($houses as $house): ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 relative hover:shadow-xl transition">
                <?php if ($house['is_sold']): ?>
                    <div class="absolute top-0 right-0 bg-red-600 text-white px-4 py-1 font-bold z-10 shadow-lg">
                        ПРОДАНО (<?= date('d.m.Y', strtotime($house['sold_at'])) ?>)
                    </div>
                <?php endif; ?>
                
                <img src="./images/image.jpg" class="w-full h-56 object-cover">
                
                <div class="p-6">
                    <span class="text-xs font-bold text-blue-500 uppercase tracking-widest"><?= htmlspecialchars($house['category_name']) ?></span>
                    <h3 class="text-2xl font-bold mt-1 mb-3"><?= htmlspecialchars($house['name']) ?></h3>
                    
                    <div class="grid grid-cols-2 gap-2 text-sm text-gray-600 mb-4">
                        <p>🛏️ <?= htmlspecialchars($house['bedrooms']) ?> Сільні</p>
                        <p>🚿 <?= htmlspecialchars($house['bathrooms']) ?> Ванні</p>
                    </div>

                    <div class="flex items-center justify-between mt-6">
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($house['price']) ?> <span class="text-sm font-normal">USD</span></p>
                        
                        <form method="POST" action="house.php">
                            <input type="hidden" name="like_house_id" value="<?= $house['id'] ?>">
                            <button type="submit" class="flex items-center gap-2 px-4 py-2 rounded-full border border-pink-100 text-pink-600 hover:bg-pink-50 transition">
                                <span>❤️</span> <b><?= $house['likes'] ?></b>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="flex justify-center gap-4 mt-12">
        <a href="?houses=<?= $more ?>&search=<?= $filters['search'] ?>&max_price=<?= $filters['max_price'] ?>&category=<?= $filters['category'] ?>" class="bg-gray-800 text-white px-8 py-3 rounded-lg font-bold hover:bg-gray-900">Показати більше</a>
        <?php if($housesCount > 3): ?>
            <a href="?houses=<?= $less ?>&search=<?= $filters['search'] ?>&max_price=<?= $filters['max_price'] ?>&category=<?= $filters['category'] ?>" class="border-2 border-gray-800 text-gray-800 px-8 py-3 rounded-lg font-bold hover:bg-gray-100">Показати менше</a>
        <?php endif; ?>
    </div>
</section>

<section class="container mx-auto px-6 mb-20">
    <div class="bg-green-50 p-8 rounded-2xl border-2 border-green-100">
        <h2 class="text-2xl font-bold text-green-800 mb-4">📊 Фінансова звітність</h2>
        <p class="text-green-700 mb-6">Виберіть період для вивантаження звіту по проданим об'єктам у форматі CSV.</p>
        <form method="POST" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-green-800">Від:</label>
                <input type="date" name="start_date" required class="border-green-200 border p-3 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-green-800">До:</label>
                <input type="date" name="end_date" required class="border-green-200 border p-3 rounded-lg">
            </div>
            <button type="submit" name="get_report" class="bg-green-600 text-white font-bold px-8 py-3 rounded-lg hover:bg-green-700 transition shadow-lg">Згенерувати звіт (.csv)</button>
        </form>
    </div>
</section>

<section class="bg-white py-16">
    <div class="container mx-auto px-6 max-w-4xl">
        <h2 class="text-3xl font-bold mb-8 text-center">Contact Me</h2>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <input type="text" name="name" placeholder="Name" required class="border p-4 rounded-lg">
            <input type="email" name="email" placeholder="Email" required class="border p-4 rounded-lg">
            <input type="date" name="dob" placeholder="Date of Birth" required class="border p-4 rounded-lg">
            <input type="text" name="phone" placeholder="Phone" required class="border p-4 rounded-lg">
            <textarea name="message" placeholder="Message" required class="border p-4 rounded-lg md:col-span-2 h-32"></textarea>
            <div class="md:col-span-2 flex gap-4">
                <button type="submit" class="bg-blue-600 text-white font-bold py-4 px-8 rounded-lg flex-1">Send Message</button>
                <button type="reset" class="bg-gray-200 font-bold py-4 px-8 rounded-lg">Clear</button>
            </div>
        </form>

        <?php if ($contact): ?>
            <div class="mt-10 p-6 bg-blue-50 rounded-xl border border-blue-100">
                <h3 class="text-lg font-bold mb-4">Дані відправлено:</h3>
                <?= $contact->formatAsTable() ?>
                <?php 
                    $formData = [$_POST['name'], $_POST['email'], $_POST['dob'], $_POST['phone'], $_POST['message']];
                    $averageLength = calculateAverageLength($formData);
                    echo "<p class='mt-4 font-medium'>Середня довжина даних: {$averageLength} символів</p>";
                ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php echo getFooter($location); ?>

</body>
</html>