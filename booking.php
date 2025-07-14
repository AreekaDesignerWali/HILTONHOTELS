<?php
session_start();
require_once 'db.php';

// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    $hotel_id = $_POST['hotel_id'];
    $user_name = $_POST['user_name'];
    $user_email = $_POST['user_email'];
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout'];

    // Validate input
    if (empty($hotel_id) || empty($user_name) || empty($user_email) || empty($checkin) || empty($checkout)) {
        $_SESSION['booking_error'] = "All fields are required.";
    } else {
        $query = "INSERT INTO bookings (hotel_id, user_name, user_email, checkin_date, checkout_date) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('issss', $hotel_id, $user_name, $user_email, $checkin, $checkout);

        if ($stmt->execute()) {
            $_SESSION['booking_confirmation'] = "Booking successful! A confirmation has been sent to $user_email.";
        } else {
            $_SESSION['booking_error'] = "Booking failed. Please try again.";
        }
    }
    // Redirect back to booking page to show confirmation/error
    header("Location: booking.php?hotel_id=$hotel_id");
    exit();
}

// Fetch hotel details for display
$hotel = null;
if (isset($_GET['hotel_id'])) {
    $hotel_id = (int)$_GET['hotel_id'];
    $query = "SELECT * FROM hotels WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hotel = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Your Stay - Hilton Hotels</title>
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

        .booking-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 600px;
        }

        .booking-container h2 {
            margin-bottom: 20px;
            color: #004aad;
        }

        .booking-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }

        .booking-container button {
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

        .booking-container button:hover {
            background-color: #003087;
        }

        .hotel-info {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .confirmation, .error {
            padding: 15px;
            margin: 20px auto;
            max-width: 600px;
            border-radius: 5px;
            text-align: center;
        }

        .confirmation {
            background: #e6ffe6;
            color: #006600;
        }

        .error {
            background: #ffe6e6;
            color: #cc0000;
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

        @media (max-width: 768px) {
            .booking-container {
                margin: 10px;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Hilton Hotels</h1>
    </header>

    <?php if (isset($_SESSION['booking_confirmation'])): ?>
        <div class="confirmation"><?php echo $_SESSION['booking_confirmation']; ?></div>
        <?php unset($_SESSION['booking_confirmation']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['booking_error'])): ?>
        <div class="error"><?php echo $_SESSION['booking_error']; ?></div>
        <?php unset($_SESSION['booking_error']); ?>
    <?php endif; ?>

    <?php if ($hotel): ?>
        <div class="booking-container">
            <h2>Book Your Stay at <?php echo htmlspecialchars($hotel['name']); ?></h2>
            <div class="hotel-info">
                <p><strong>Location:</strong> <?php echo htmlspecialchars($hotel['city']); ?></p>
                <p><strong>Price:</strong> $<?php echo htmlspecialchars($hotel['price_per_night']); ?>/night</p>
                <p><strong>Rating:</strong> <?php echo htmlspecialchars($hotel['rating']); ?> ★</p>
                <p><strong>Amenities:</strong> <?php echo htmlspecialchars($hotel['amenities']); ?></p>
            </div>
            <form method="POST">
                <input type="hidden" name="hotel_id" value="<?php echo $hotel['id']; ?>">
                <input type="text" name="user_name" placeholder="Your Name" required>
                <input type="email" name="user_email" placeholder="Your Email" required>
                <input type="date" name="checkin" required>
                <input type="date" name="checkout" required>
                <button type="submit" name="book">Confirm Booking</button>
            </form>
        </div>
    <?php else: ?>
        <div class="error">No hotel selected. Please choose a hotel from the listings.</div>
    <?php endif; ?>

    <a href="hotels.php" class="back-link">Back to Hotel Listings</a>

    <script>
        // Handle back link redirection
        document.querySelector('.back-link').addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = this.href;
        });
    </script>
</body>
</html>
