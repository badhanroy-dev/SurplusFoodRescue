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

// 1. ACTION: Submit Claim (action=confirm)
if ($action === 'confirm') {
    $check_res = mysqli_query($conn, "SELECT donation_id, quantity, status FROM food_donations WHERE donation_id = $donation_id");
    
    if ($check_res && $donation = mysqli_fetch_assoc($check_res)) {
        if (strtolower($donation['status']) !== 'available') {
            echo json_encode(['success' => false, 'message' => 'This food donation is no longer available.']);
            exit();
        }

        $quantity = (float)$donation['quantity'];

        // Record claim in food_claims and update food_donations status
        mysqli_query($conn, "INSERT INTO food_claims (donation_id, charity_id, quantity_claimed, status) VALUES ($donation_id, $charity_id, $quantity, 'Claimed')");
        mysqli_query($conn, "UPDATE food_donations SET status = 'Claimed' WHERE donation_id = $donation_id");

        echo json_encode([
            'success'  => true,
            'message'  => 'Food claimed successfully!',
            'redirect' => 'claimed-food.html'
        ]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Food donation not found.']);
        exit();
    }
}

// 2. ACTION: Get Claim Details (default)
$sql = "SELECT 
            d.donation_id,
            d.food_name,
            d.quantity,
            d.quantity_unit,
            d.pickup_date,
            d.expiry_date,
            d.preferred_time,
            d.pickup_address,
            d.image_path,
            d.status,
            u.full_name AS donor_name
        FROM food_donations d
        INNER JOIN users u ON d.donor_id = u.user_id
        WHERE d.donation_id = $donation_id";

$result = mysqli_query($conn, $sql);

if ($result && $row = mysqli_fetch_assoc($result)) {
    $qty_num = (float)$row['quantity'];
    $estimated_people = max(1, (int)round($qty_num * 3)); //let for every 1kg/unit it can feed 3 people

    echo json_encode([
        'success'  => true,
        'donation' => [
            'donation_id'      => $row['donation_id'],
            'food_name'        => $row['food_name'],
            'quantity'         => $row['quantity'],
            'quantity_unit'    => $row['quantity_unit'],
            'donor_name'       => $row['donor_name'],
            'pickup_address'   => $row['pickup_address'],
            'pickup_date'      => date('M d, Y', strtotime($row['pickup_date'])),
            'preferred_time'   => $row['preferred_time'],
            'image_path'       => $row['image_path'],
            'estimated_impact' => "~ {$estimated_people} People"
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Food donation details not found.']);
}
?>
