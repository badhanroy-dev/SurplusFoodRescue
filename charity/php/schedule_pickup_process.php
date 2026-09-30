<?php
// Start session for authenticated user
session_start();

// Set header for JSON output
header('Content-Type: application/json');

// Include database connection (up 2 levels to project root)
require_once __DIR__ . '/../../db.php';

// Check if user is logged in as Charity
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Charity') {
    echo json_encode(['error' => 'unauthorized', 'redirect' => '../guest/login.html']);
    exit();
}

$charity_id  = (int)$_SESSION['user_id'];
$action      = isset($_GET['action']) ? $_GET['action'] : 'get';
$donation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($donation_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid food donation ID.']);
    exit();
}

// 1. ACTION: Confirm Pickup Schedule (action=confirm)
if ($action === 'confirm') {
    $pickup_time = $_GET['time'] ?? '';

    // Fetch food donation info directly
    $res = mysqli_query($conn, "SELECT donation_id, quantity, pickup_address, pickup_date, preferred_time FROM food_donations WHERE donation_id = $donation_id");
    $donation = mysqli_fetch_assoc($res);

    if ($donation) {
        $preferred_time = $donation['preferred_time'];

        // 1. Validate pickup time against donor availability window
        if (!empty($preferred_time) && strpos($preferred_time, '-') !== false) {
            $parts = explode('-', $preferred_time);
            $start_t = strtotime(trim($parts[0]));
            $end_t   = strtotime(trim($parts[1]));
            $user_t  = strtotime($pickup_time);

            if ($user_t < $start_t || $user_t > $end_t) {
                echo json_encode([
                    'success' => false,
                    'message' => "Selected pickup time ($pickup_time) is outside donor availability ($preferred_time)."
                ]);
                exit();
            }
        }

        // 2. Insert new claim record into food_claims
        $quantity = $donation['quantity'];
        mysqli_query($conn, "INSERT INTO food_claims (donation_id, charity_id, quantity_claimed, status) VALUES ($donation_id, $charity_id, $quantity, 'Scheduled')");
        $claim_id = mysqli_insert_id($conn);

        // 3. Update food donation status to Claimed
        mysqli_query($conn, "UPDATE food_donations SET status = 'Claimed' WHERE donation_id = $donation_id");

        // 4. Fetch charity address
        $user_res = mysqli_query($conn, "SELECT address FROM users WHERE user_id = $charity_id");
        $user_row = mysqli_fetch_assoc($user_res);
        $charity_address = $user_row['address'] ?? 'Charity Location';

        $pickup_address = $donation['pickup_address'];
        $pickup_date    = $donation['pickup_date'];

        // 5. Insert delivery entry into pickup_deliveries
        mysqli_query($conn, "INSERT INTO pickup_deliveries (claim_id, pickup_address, delivery_address, pickup_date, scheduled_time, delivery_status) VALUES ($claim_id, '$pickup_address', '$charity_address', '$pickup_date', '$pickup_time', 'Available')");

        // 6. Return success JSON
        echo json_encode([
            'success'  => true,
            'message'  => 'Pickup scheduled successfully!',
            'redirect' => 'claimed-food.html'
        ]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Food donation not found.']);
        exit();
    }
}

// 2. ACTION: Get Details for Pickup Page (default)
$sql = "SELECT 
            d.donation_id,
            d.food_name,
            d.description,
            d.quantity,
            d.quantity_unit,
            d.expiry_date,
            d.preferred_time,
            d.pickup_address,
            d.status
        FROM food_donations d
        WHERE d.donation_id = $donation_id";

$result = mysqli_query($conn, $sql);

if ($result && $row = mysqli_fetch_assoc($result)) {
    // Calculate remaining time until expiry
    $expiry_timestamp = strtotime($row['expiry_date']);
    $now = time();
    $diff_sec = $expiry_timestamp - $now;

    if ($diff_sec > 0) {
        $hours = floor($diff_sec / 3600);
        if ($hours >= 24) {
            $days = floor($hours / 24);
            $expiry_window = "{$days} days remaining";
        } else {
            $expiry_window = "{$hours} hours remaining";
        }
    } else {
        $expiry_window = "Expired";
    }

    echo json_encode([
        'success'  => true,
        'donation' => [
            'donation_id'    => $row['donation_id'],
            'food_name'      => $row['food_name'],
            'description'    => !empty($row['description']) ? $row['description'] : 'No additional description',
            'quantity'       => $row['quantity'],
            'quantity_unit'  => $row['quantity_unit'],
            'preferred_time' => $row['preferred_time'],
            'expiry_window'  => $expiry_window,
            'status'         => $row['status']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Food donation not found.']);
}
?>
