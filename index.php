<?php
include 'about.php';
include 'footer.php';
include 'house.php';
include 'contact.php';


$host = "localhost";       
$user = "root";            
$pass = "rootpassword";    
$db = "real_estate";       

$conn = new mysqli($host, $user, $pass, $db);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$conn = mysql_connect('localhost', 'root', 'rootpassword');  
if (!$conn) {
    die('Could not connect: ' . mysql_error());
}
mysql_select_db('real_estate', $conn); 

$housesCount = isset($_GET['houses']) ? (int)$_GET['houses'] : 3;
$more = $housesCount + 3;
$less = max($housesCount - 3, 3);

$houses = getHouses();

$contact = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $name = mysql_real_escape_string($_POST['name']);
    $email = mysql_real_escape_string($_POST['email']);
    $phone = mysql_real_escape_string($_POST['phone']);
    $message = mysql_real_escape_string($_POST['message']);
    $dob = mysql_real_escape_string($_POST['dob']);
    
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<p>Invalid email format</p>";
    }

    
    if (strtotime($dob) > time()) {
        echo "<p>Date of birth cannot be in the future.</p>";
    }

 
    if (filter_var($email, FILTER_VALIDATE_EMAIL) && strtotime($dob) <= time()) {
        $query = "INSERT INTO contacts (name, email, phone, message, dob) 
                  VALUES ('$name', '$email', '$phone', '$message', '$dob')";
        $result = mysql_query($query, $conn);

        if ($result) {
            echo "<p>Contact information has been saved successfully!</p>";
        } else {
            echo "<p>Error saving contact information: " . mysql_error() . "</p>";
        }
    }
}

$location = "RIVNE, Ukraine";

function calculateAverageLength($data) {
    $totalLength = 0;
    $fieldCount = 0;

    foreach ($data as $field) {
        $totalLength += strlen($field);
        $fieldCount++;
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
        <?php foreach ($houses as $house): 
            $prices = getDiscountedPrice($house->price); ?>
            
             <div class="house">
                <h3><?= $house['name'] ?></h3>
                <img src="<?= $house['image'] ?>" alt="<?= $house['name'] ?>">
                <p>Address: <?= $house['address'] ?></p>
                <p>Phone: <?= $house['phone'] ?></p>
                <p>Bedrooms: <?= $house['bedrooms'] ?></p>
                <p>Bathrooms: <?= $house['bathrooms'] ?></p>
                <p>Price: <?= number_format($house['price'], 0) ?></p>
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

        a
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
