<?php
session_start();
require_once 'db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$search_results = [];
$sort = $_GET['sort'] ?? 'price_asc';
$order_by = 'price_per_night ASC';
if ($sort === 'price_desc') {
    $order_by = 'price_per_night DESC';
} elseif ($sort === 'rating_desc') {
    $order_by = 'rating DESC';
}

$error_message = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
        $destination = trim($_POST['destination'] ?? '');
        $checkin = $_POST['checkin'] ?? '';
        $checkout = $_POST['checkout'] ?? '';
        $price_range = $_POST['price_range'] ?? '';

        $query = "SELECT * FROM hotels WHERE 1=1";
        $params = [];
        $types = '';

        if (!empty($destination)) {
            $query .= " AND LOWER(city) LIKE LOWER(?)";
            $params[] = "%$destination%";
            $types .= 's';
        }

        if (!empty($price_range)) {
            $query .= " AND price_per_night <= ?";
            $params[] = (int)$price_range;
            $types .= 'i';
        }

        $query .= " ORDER BY $order_by";

        // Debug: Print query and parameters
        // Remove this after debugging
        // echo "Debug Query: $query<br>";
        // print_r($params);
        // exit;

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $search_results = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $query = "SELECT * FROM hotels ORDER BY $order_by";
        $result = $conn->query($query);
        if (!$result) {
            throw new Exception("Query failed: " . $conn->error);
        }
        $search_results = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Listings - Hilton Hotels</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background-color: #f4f4f9;
            color: #333;
        }

        header {
            background: linear-gradient(90deg, #004aad, #007bff);
            color: white;
            padding: 20px;
            text-align: center;
        }

        header h1 {
            font-size: 2.5em;
        }

        .search-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 800px;
        }

        .search-container form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }

        .search-container input, .search-container select, .search-container button {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }

        .search-container button {
            background-color: #004aad;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .search-container button:hover {
            background-color: #003087;
        }

        .sort-container {
            max-width: 800px;
            margin: 10px auto;
            text-align: right;
        }

        .sort-container select {
            padding: 8px;
            border-radius: 5px;
            font-size: 1em;
        }

        .hotel-list {
            max-width: 1200px;
            margin: 20px auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 0 20px;
        }

        .hotel-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .hotel-card:hover {
            transform: translateY(-5px);
        }

        .hotel-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .hotel-card-content {
            padding: 15px;
        }

        .hotel-card-content h3 {
            font-size: 1.5em;
            margin-bottom: 10px;
        }

        .hotel-card-content p {
            margin-bottom: 10px;
            color: #666;
        }

        .hotel-card-content .price {
            font-size: 1.2em;
            color: #004aad;
            font-weight: bold;
        }

        .hotel-card-content button {
            width: 100%;
            padding: 10px;
            background-color: #004aad;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s;
        }

        .hotel-card-content button:hover {
            background-color: #003087;
        }

        .back-link {
            display: block;
            text-align: center;
            margin: 20px;
            color: #004aad;
            text-decoration: none;
            font-size: 1em;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .no-results, .error {
            text-align: center;
            grid-column: 1 / -1;
            padding: 20px;
            color: #666;
        }

        .error {
            color: #cc0000;
            background: #ffe6e6;
            border-radius: 5px;
        }

        @media (max-width: 768px) {
            .search-container form {
                flex-direction: column;
            }

            .hotel-list {
                grid-template-columns: 1fr;
            }

            .sort-container {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Hilton Hotels</h1>
    </header>

    <?php if ($error_message): ?>
        <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="search-container">
        <form method="POST">
            <input type="text" name="destination" placeholder="Destination (e.g., New York)">
            <input type="date" name="checkin">
            <input type="date" name="checkout">
            <select name="price_range">
                <option value="">Select Price Range</option>
                <option value="100">$0 - $100</option>
                <option value="200">$100 - $200</option>
                <option value="300">$200 - $300</option>
                <option value="500">$300+</option>
            </select>
            <button type="submit" name="search">Search Hotels</button>
        </form>
    </div>

    <div class="sort-container">
        <select onchange="sortHotels(this.value)">
            <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
            <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
            <option value="rating_desc" <?php echo $sort === 'rating_desc' ? 'selected' : ''; ?>>Rating: High to Low</option>
        </select>
    </div>

    <div class="hotel-list">
        <?php if (!empty($search_results)): ?>
            <?php foreach ($search_results as $hotel): ?>
                <div class="hotel-card">
                    <img src="https://via.placeholder.com/300x200?text=<?php echo htmlspecialchars($hotel['name']); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>">
                    <div class="hotel-card-content">
                        <h3><?php echo htmlspecialchars($hotel['name']); ?></h3>
                        <p><?php echo htmlspecialchars($hotel['city']); ?></p>
                        <p>Rating: <?php echo htmlspecialchars($hotel['rating']); ?> ★</p>
                        <p>Amenities: <?php echo htmlspecialchars($hotel['amenities']); ?></p>
                        <p class="price">$<?php echo htmlspecialchars($hotel['price_per_night']); ?>/night</p>
                        <button onclick="bookHotel(<?php echo $hotel['id']; ?>)">Book Now</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-results">No hotels found. Try adjusting your search or leave fields empty to see all hotels.</p>
        <?php endif; ?>
    </div>

    <a href="index.php" class="back-link">Back to Home</a>

    <script>
        function bookHotel(hotelId) {
            window.location.href = `booking.php?hotel_id=${hotelId}`;
        }

        function sortHotels(criteria) {
            window.location.href = `hotels.php?sort=${criteria}`;
        }
    </script>
</body>
</html>
