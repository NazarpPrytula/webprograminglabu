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
if ($conn) {
    mysqli_set_charset($conn, "utf8mb4");
} else {
    die("Connection failed: " . mysqli_connect_error());
}

//  CSV 
if (isset($_POST['get_report'])) {
    $start = $_POST['start_date'] ?? '';
    $end = $_POST['end_date'] ?? '';
    
    $query = "SELECT name, price, sold_at FROM houses WHERE is_sold = 1 AND sold_at BETWEEN '$start 00:00:00' AND '$end 23:59:59'";
    $result = mysqli_query($conn, $query);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sales_report.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM для Excel
    
    fputcsv($output, ['Назва будинку', 'Ціна (USD)', 'Дата продажу']);
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// фільтрації ---
$housesCount = isset($_GET['houses']) ? (int)$_GET['houses'] : 3;
$filters = [
    'search' => $_GET['search'] ?? '',
    'max_price' => isset($_GET['max_price']) ? (int)$_GET['max_price'] : 1000000,
    'category' => $_GET['category'] ?? ''
];

$more = $housesCount + 3;
$less = max($housesCount - 3, 3);

// Отримуємо дані з house.php
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
    $fieldCount = 0;
    foreach ($data as $field) { 
        if ($field !== null) {
            $totalLength += strlen((string)$field); 
            $fieldCount++;
        }
    }
    return $fieldCount > 0 ? round($totalLength / $fieldCount) : 0;
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Real Estate Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">

<div class="container mx-auto p-6">
    <h1 class="text-4xl font-extrabold text-center my-8 text-blue-900">Real Estate Agency</h1>
    <div class="bg-white p-6 rounded-xl shadow-sm mb-10 border border-gray-100">
        <?php echo getAboutBlock(); ?>
    </div>
</div>

<?php if (!empty($featuredHouses)): ?>
<section class="bg-slate-900 py-12 mb-12 shadow-inner">
    <div class="container mx-auto px-6">
        <h2 class="text-3xl font-bold text-white mb-6 underline decoration-blue-500">Популярні об'єкти</h2>
        <div class="flex overflow-x-auto pb-6 gap-6 snap-x scrollbar-hide">
            <?php foreach ($featuredHouses as $f): ?>
                <div class="min-w-[300px] bg-white rounded-xl shadow-lg overflow-hidden snap-center transform hover:scale-105 transition duration-300">
                    <img src="./images/image.jpg" class="w-full h-40 object-cover" alt="house">
                    <div class="p-4">
                        <h3 class="font-bold text-lg text-gray-800"><?= htmlspecialchars($f['name'] ?? 'Будинок') ?></h3>
                        <p class="text-blue-600 font-bold text-xl"><?= number_format((float)($f['price'] ?? 0)) ?> USD</p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="container mx-auto px-6 mb-12">
    <div class="bg-white p-8 rounded-2xl shadow-xl border border-blue-50">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Швидкий пошук</h2>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <input type="text" name="search" placeholder="Назва..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>" class="border-2 border-gray-100 p-3 rounded-xl focus:border-blue-400 outline-none transition">
            
            <select name="category" class="border-2 border-gray-100 p-3 rounded-xl focus:border-blue-400 outline-none transition">
                <option value="">Всі категорії</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($filters['category'] == $cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="flex flex-col">
                <label class="text-xs font-semibold text-gray-500 uppercase">Ціна до: <span class="text-blue-600 text-sm" id="pLab"><?= number_format($filters['max_price']) ?></span></label>
                <input type="range" name="max_price" min="0" max="1000000" step="10000" value="<?= $filters['max_price'] ?>" class="mt-2 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer" oninput="document.getElementById('pLab').innerText = Number(this.value).toLocaleString()">
            </div>

            <button type="submit" class="bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-200 transition">Знайти</button>
        </form>
    </div>
</section>

<section class="container mx-auto px-6 mb-12">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
        <?php foreach ($houses as $house): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden relative group hover:shadow-2xl transition duration-500">
                <?php if (!empty($house['is_sold'])): ?>
                    <div class="absolute top-4 right-4 bg-red-600 text-white px-3 py-1 rounded-lg font-bold text-sm z-10 rotate-3 shadow-md">
                        ПРОДАНО <?= !empty($house['sold_at']) ? date('d.m.y', strtotime($house['sold_at'])) : '' ?>
                    </div>
                <?php endif; ?>
                
                <div class="overflow-hidden">
                    <img src="./images/image.jpg" class="w-full h-64 object-cover group-hover:scale-110 transition duration-500">
                </div>
                
                <div class="p-6">
                    <span class="bg-blue-50 text-blue-600 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-tighter">
                        <?= htmlspecialchars($house['category_name'] ?? 'Нерухомість') ?>
                    </span>
                    <h3 class="text-2xl font-bold mt-2 text-gray-800"><?= htmlspecialchars($house['name'] ?? 'Без назви') ?></h3>
                    
                    <div class="flex gap-4 mt-3 text-gray-500 text-sm">
                        <span>🛏️ <?= (int)($house['bedrooms'] ?? 0) ?></span>
                        <span>🚿 <?= (int)($house['bathrooms'] ?? 0) ?></span>
                    </div>

                    <div class="flex items-center justify-between mt-8">
                        <span class="text-2xl font-black text-gray-900">$<?= number_format((float)($house['price'] ?? 0)) ?></span>
                        
                        <form method="POST" action="house.php">
                            <input type="hidden" name="like_house_id" value="<?= $house['id'] ?>">
                            <button type="submit" class="flex items-center gap-2 px-5 py-2 rounded-xl bg-pink-50 text-pink-600 font-bold hover:bg-pink-100 transition">
                                ❤️ <?= (int)($house['likes'] ?? 0) ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="flex justify-center gap-6 mt-16">
        <a href="?houses=<?= $more ?>&search=<?= urlencode($filters['search']) ?>&max_price=<?= $filters['max_price'] ?>&category=<?= $filters['category'] ?>" class="bg-gray-900 text-white px-10 py-4 rounded-2xl font-bold hover:bg-black transition shadow-xl">Більше об'єктів</a>
        <?php if($housesCount > 3): ?>
            <a href="?houses=<?= $less ?>&search=<?= urlencode($filters['search']) ?>&max_price=<?= $filters['max_price'] ?>&category=<?= $filters['category'] ?>" class="bg-white border-2 border-gray-900 px-10 py-4 rounded-2xl font-bold hover:bg-gray-50 transition">Менше</a>
        <?php endif; ?>
    </div>
</section>

<section class="container mx-auto px-6 mb-20">
    <div class="bg-gradient-to-r from-green-600 to-emerald-700 p-10 rounded-3xl shadow-2xl text-white">
        <h2 class="text-3xl font-bold mb-2">Генератор звітів</h2>
        <p class="opacity-80 mb-8 font-light text-lg">Виберіть часовий проміжок для експорту даних у CSV</p>
        <form method="POST" class="flex flex-wrap gap-6 items-end">
            <div class="flex flex-col gap-2">
                <label class="text-sm font-bold opacity-90">Дата початку</label>
                <input type="date" name="start_date" required class="p-3 rounded-xl text-gray-900 outline-none">
            </div>
            <div class="flex flex-col gap-2">
                <label class="text-sm font-bold opacity-90">Дата завершення</label>
                <input type="date" name="end_date" required class="p-3 rounded-xl text-gray-900 outline-none">
            </div>
            <button type="submit" name="get_report" class="bg-white text-green-700 font-black px-10 py-3 rounded-xl hover:bg-green-50 transition transform active:scale-95 shadow-lg">Скачати CSV</button>
        </form>
    </div>
</section>

<section class="bg-white py-12 border-t border-gray-100">
    <div class="container mx-auto px-6 max-w-4xl">
        <h2 class="text-3xl font-bold mb-8 text-center text-gray-800">Зворотній зв'язок</h2>
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <input type="text" name="name" placeholder="Ваше ім'я" required class="w-full border-2 border-gray-50 p-4 rounded-2xl focus:border-blue-200 outline-none bg-gray-50">
                <input type="email" name="email" placeholder="Email" required class="w-full border-2 border-gray-50 p-4 rounded-2xl focus:border-blue-200 outline-none bg-gray-50">
                <input type="date" name="dob" required class="w-full border-2 border-gray-50 p-4 rounded-2xl focus:border-blue-200 outline-none bg-gray-50 text-gray-500">
                <input type="text" name="phone" placeholder="Телефон" required class="w-full border-2 border-gray-50 p-4 rounded-2xl focus:border-blue-200 outline-none bg-gray-50">
            </div>
            <textarea name="message" placeholder="Ваше повідомлення..." required class="w-full border-2 border-gray-50 p-4 rounded-2xl h-40 focus:border-blue-200 outline-none bg-gray-50"></textarea>
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-5 rounded-2xl hover:bg-blue-700 transition shadow-lg shadow-blue-100">Надіслати запит</button>
        </form>

        <?php if ($contact): ?>
            <div class="mt-12 p-8 bg-blue-50 rounded-3xl border-2 border-blue-100 animate-pulse">
                <h3 class="text-xl font-bold mb-4 text-blue-800 italic">Дякуємо! Ми отримали ваші дані:</h3>
                <?= $contact->formatAsTable() ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php echo getFooter($location); ?>

</body>
</html>