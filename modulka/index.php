<?php
$result = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $num1 = $_POST["num1"];
    $num2 = $_POST["num2"];

    if (is_numeric($num1) && is_numeric($num2)) {
        $result = $num1 + $num2;
    } else {
        $result = "Будь ласка, введіть коректні числа.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Обчислення суми</title>
</head>
<body>

<h2>Обчислення суми двох чисел</h2>

<form method="post" action="">
    <label>Перше число:</label>
    <input type="text" name="num1" required>
    <br><br>

    <label>Друге число:</label>
    <input type="text" name="num2" required>
    <br><br>

    <input type="submit" value="Обчислити">
</form>

<?php
if ($result !== "") {
    echo "<h3>Результат: " . $result . "</h3>";
}
?>

</body>
</html>
