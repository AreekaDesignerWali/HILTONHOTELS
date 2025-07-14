<?php
session_start();
require_once 'db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$search_results = [];
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
    }

    // Handle booking form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
        $hotel_id = $_POST['hotel_id'];
        $user_name = $_POST['user_name'];
        $user_email = $_POST['user_email'];
        $checkin = $_POST['checkin'];
        $checkout = $_POST['checkout'];

        $query = "INSERT INTO bookings (hotel_id, user_name, user_email, checkin_date, checkout_date) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param('issss', $hotel_id, $user_name, $user_email, $checkin, $checkout);

        if ($stmt->execute()) {
            $_SESSION['booking_confirmation'] = "Booking successful! A confirmation has been sent to $user_email.";
        } else {
            $_SESSION['booking_error'] = "Booking failed. Please try again.";
        }
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
    <title>Hilton Hotels - Book Your Stay</title>
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
            margin-bottom: 10px;
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

        .hotel-list {
            max-width: 1200px;
            margin: 20px auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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

        .booking-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 500px;
        }

        .booking-form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .booking-form button {
            width: 100%;
            padding: 10px;
            background-color: #004aad;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .booking-form button:hover {
            background-color: #003087;
        }

        .confirmation {
            background: #e6ffe6;
            color: #006600;
            padding: 15px;
            margin: 20px auto;
            max-width: 800px;
            border-radius: 5px;
            text-align: center;
        }

        .error {
            background: #ffe6e6;
            color: #cc0000;
            padding: 15px;
            margin: 20px auto;
            max-width: 800px;
            border-radius: 5px;
            text-align: center;
        }

        .no-results {
            text-align: center;
            grid-column: 1 / -1;
            padding: 20px;
            color: #666;
        }

        @media (max-width: 768px) {
            .search-container form {
                flex-direction: column;
            }

            .hotel-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Hilton Hotels</h2>
        <p>Book Your Perfect Stay</p>
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

    <?php if (!empty($search_results)): ?>
        <div class="hotel-list">
            <?php foreach ($search_results as $hotel): ?>
                <div class="hotel-card">
                    <img src="https://via.placeholder.com/300x200?text=<?php echo htmlspecialchars($hotel['name']); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>">
                    <div class="hotel-card-content">
                        <h3><?php echo htmlspecialchars($hotel['name']); ?></h3>
                        <p><?php echo htmlspecialchars($hotel['city']); ?></p>
                        <p>Rating: <?php echo htmlspecialchars($hotel['rating']); ?> ★</p>
                        <p>Amenities: <?php echo htmlspecialchars($hotel['amenities']); ?></p>
                        <p class="price">$<?php echo htmlspecialchars($hotel['price_per_night']); ?>/night</p>
                        <button onclick="showBookingForm(<?php echo $hotel['id']; ?>, '<?php echo htmlspecialchars($hotel['name']); ?>')">Book Now</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="hotel-list">
            <p class="no-results">No hotels found. Try adjusting your search or leave fields empty to see all hotels.</p>
        </div>
    <?php endif; ?>

    <div class="booking-form" id="booking-form" style="display: none;">
        <h2>Book Your Stay</h2>
        <form method="POST">
            <input type="hidden" name="hotel_id" id="hotel_id">
            <input type="text" name="user_name" placeholder="Your Name" required>
            <input type="email" name="user_email" placeholder="Your Email" required>
            <input type="date" name="checkin" required>
            <input type="date" name="checkout" required>
            <button type="submit" name="book">Confirm Booking</button>
        </form>
    </div>

    <?php if (isset($_SESSION['booking_confirmation'])): ?>
        <div class="confirmation"><?php echo $_SESSION['booking_confirmation']; ?></div>
        <?php unset($_SESSION['booking_confirmation']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['booking_error'])): ?>
        <div class="error"><?php echo $_SESSION['booking_error']; ?></div>
        <?php unset($_SESSION['booking_error']); ?>
    <?php endif; ?>

    <script>
        function showBookingForm(hotelId, hotelName) {
            document.getElementById('booking-form').style.display = 'block';
            document.getElementById('hotel_id').value = hotelId;
            window.scrollTo({ top: document.getElementById('booking-form').offsetTop, behavior: 'smooth' });
        }
    </script>
</body>
</html>
