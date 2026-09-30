<?php
// browse_food_process.php
session_start();

// Include database connection (up 2 levels to project root)
require_once __DIR__ . '/../../db.php';

// Check if user is logged in as Charity
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Charity') {
    echo json_encode([
        'error'    => 'unauthorized',
        'redirect' => '../guest/login.html'
    ]);
    exit();
}

$charity_id   = (int)$_SESSION['user_id'];
$charity_name = $_SESSION['full_name'] ?? 'Charity Organization';

// Get filter/sort GET parameters
$area = isset($_GET['area']) ? trim($_GET['area']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$date = isset($_GET['date']) ? trim($_GET['date']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : '';

// Base query for available food donations
$sql = "SELECT 
            d.donation_id,
            d.food_name,
            d.quantity,
            d.quantity_unit,
            d.expiry_date,
            d.preferred_time,
            d.pickup_address,
            d.image_path,
            d.status,
            u.full_name AS donor_name,
            c.category_name
        FROM food_donations d
        INNER JOIN users u ON d.donor_id = u.user_id
        INNER JOIN food_categories c ON d.category_id = c.category_id
        WHERE d.status = 'Available'";

// Filter by Area if selected
if (!empty($area)) {
    $sql .= " AND d.pickup_address LIKE '%$area%'";
}

// Filter by Food Type if selected
if (!empty($type)) {
    $sql .= " AND c.category_name LIKE '%$type%'";
}

// Filter by Expiry Date if selected
if (!empty($date)) {
    $sql .= " AND DATE(d.expiry_date) <= '$date'";
}

// Sort order (default ASC, or DESC if requested)
if ($sort === 'desc') {
    $sql .= " ORDER BY d.expiry_date DESC";
} else {
    $sql .= " ORDER BY d.expiry_date ASC";
}

$result = mysqli_query($conn, $sql);
$donations = array();

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Calculate remaining time until expiry
        $expiry_timestamp = strtotime($row['expiry_date']);
        $now = time();
        $diff_sec = $expiry_timestamp - $now;

        if ($diff_sec > 0) {
            $hours = floor($diff_sec / 3600);
            $mins  = floor(($diff_sec % 3600) / 60);
            if ($hours > 24) {
                $days = floor($hours / 24);
                $expiry_tag = "Expires in {$days} day" . ($days > 1 ? "s" : "");
            } elseif ($hours > 0) {
                $expiry_tag = "Expires in {$hours}h {$mins}m";
            } else {
                $expiry_tag = "Expires in {$mins}m";
            }
        } else {
            $expiry_tag = "Expired";
        }

        $donations[] = array(
            'donation_id'    => $row['donation_id'],
            'food_name'      => $row['food_name'],
            'quantity'       => $row['quantity'],
            'quantity_unit'  => $row['quantity_unit'],
            'category_name'  => $row['category_name'],
            'donor_name'     => $row['donor_name'],
            'pickup_address' => $row['pickup_address'],
            'preferred_time' => $row['preferred_time'],
            'image_path'     => !empty($row['image_path']) ? $row['image_path'] : 'images/vegetables.jpg',
            'expiry_tag'     => $expiry_tag,
            'status'         => $row['status']
        );
    }
}

// Return JSON response
echo json_encode([
    'success'      => true,
    'charity_name' => $charity_name,
    'total_count'  => count($donations),
    'donations'    => $donations
]);
?>
