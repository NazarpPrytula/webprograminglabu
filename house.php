<?php

class House {
    public int $price;
    public string $name;
    public string $address;
    public string $phone;
    public string $image;
    public int $bedrooms;
    public int $bathrooms;

    public function __construct(
        int $price,
        string $name,
        string $address,
        string $phone,
        string $image,
        int $bedrooms,
        int $bathrooms
    ) {
        $this->price = $price;
        $this->name = $name;
        $this->address = $address;
        $this->phone = $phone;
        $this->image = $image;
        $this->bedrooms = $bedrooms;
        $this->bathrooms = $bathrooms;
    }
}
function getDiscountedPrice(int $price): array {
    $discountPercentage = rand(5, 30);
    $discountedPrice = $price * (1 - $discountPercentage / 100);

    return [
        'original' => number_format($price, 0),
        'discounted' => number_format($discountedPrice, 0),
        'discountPercentage' => $discountPercentage
    ];
}
function getHouses() {
    $conn = mysql_connect('localhost', 'root', 'rootpassword');
    if (!$conn) {
        die('Could not connect: ' . mysql_error());
    }
    mysql_select_db('real_estate', $conn);

    $housesCount = isset($_GET['houses']) ? (int)$_GET['houses'] : 3;
    $query = "SELECT * FROM houses LIMIT $housesCount";
    $result = mysql_query($query, $conn);

    $houses = [];
    while ($row = mysql_fetch_assoc($result)) {
        $houses[] = $row;
    }

    return $houses;
}