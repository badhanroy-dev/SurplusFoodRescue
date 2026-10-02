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

// Get donation ID from GET parameter
$donation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($donation_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid food donation ID.'
    ]);
    exit();
}

// SQL query to fetch detailed food donation info
$sql = "SELECT 
            d.donation_id,
            d.food_name,
            d.quantity,
            d.quantity_unit,
            d.expiry_date,
            d.preferred_time,
            d.pickup_address,
            d.food_safety_info,
            d.description,
            d.image_path,
            d.status,
            u.full_name AS donor_name,
            c.category_name
        FROM food_donations d
        INNER JOIN users u ON d.donor_id = u.user_id
        INNER JOIN food_categories c ON d.category_id = c.category_id
        WHERE d.donation_id = $donation_id";

// Fetch Broadcast Notifications
$res_notif = mysqli_query($conn, "SELECT title, message FROM broadcasts WHERE status = 'Sent' AND audience IN ('All Users', 'Charities Only') ORDER BY created_at DESC LIMIT 5");
$notifications = $res_notif ? mysqli_fetch_all($res_notif, MYSQLI_ASSOC) : [];

if ($result && $row = mysqli_fetch_assoc($result)) {
    // Calculate remaining time until expiry
    $expiry_timestamp = strtotime($row['expiry_date']);
    $now = time();
    $diff_sec = $expiry_timestamp - $now;

    if ($diff_sec > 0) {
        $hours = floor($diff_sec / 3600);
        $mins  = floor(($diff_sec % 3600) / 60);
        if ($hours > 24) {
            $days = floor($hours / 24);
            $expiry_tag = "EXPIRES IN {$days} DAY" . ($days > 1 ? "S" : "");
        } elseif ($hours > 0) {
            $expiry_tag = "EXPIRES IN {$hours}H {$mins}M";
        } else {
            $expiry_tag = "EXPIRES IN {$mins}M";
        }
    } else {
        $expiry_tag = "EXPIRED";
    }

    $donation_details = array(
        'donation_id'      => $row['donation_id'],
        'food_name'        => $row['food_name'],
        'quantity'         => $row['quantity'],
        'quantity_unit'    => $row['quantity_unit'],
        'category_name'    => $row['category_name'],
        'donor_name'       => $row['donor_name'],
        'pickup_address'   => $row['pickup_address'],
        'preferred_time'   => $row['preferred_time'],
        'food_safety_info' => !empty($row['food_safety_info']) ? $row['food_safety_info'] : 'Standard food handling guidelines apply. Ensure safe temperature during transit.',
        'description'      => !empty($row['description']) ? $row['description'] : 'No additional description provided by donor.',
        'image_path'       => !empty($row['image_path']) ? $row['image_path'] : 'images/vegetables.jpg',
        'expiry_tag'       => $expiry_tag,
        'status'           => strtoupper($row['status'])
    );

    echo json_encode([
        'success'       => true,
        'donation'      => $donation_details,
        'notifications' => $notifications
    ]);
} else {
    echo json_encode([
        'success'       => false,
        'message'       => 'Food donation details not found.',
        'notifications' => $notifications
    ]);
}

?>
