<?php
// Start session for authenticated user
session_start();

// Set header for JSON output
header('Content-Type: application/json');

// Include database connection (up 2 levels to project root)
require_once __DIR__ . '/../../db.php';

// Check if user is logged in and has 'Charity' role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Charity') {
    echo json_encode(['error' => 'unauthorized', 'redirect' => '../guest/login.html']);
    exit();
}

// Get current logged-in charity user ID and name
$charity_id   = (int)$_SESSION['user_id'];
$charity_name = $_SESSION['full_name'] ?? 'Charity Organization';

// 1. Fetch count of all Available Food Posts in system
$sql_available = "SELECT COUNT(*) AS total_available FROM food_donations WHERE status = 'Available'";
$res_available = mysqli_query($conn, $sql_available);
$available_count = 0;
if ($res_available && $row = mysqli_fetch_assoc($res_available)) {
    $available_count = (int)$row['total_available'];
}

// 2. Fetch Active Claims count for this charity ('Claimed', 'Scheduled', 'In Transit')
$sql_active = "SELECT COUNT(*) AS total_active FROM food_claims WHERE charity_id = $charity_id AND status IN ('Claimed', 'Scheduled', 'In Transit')";
$res_active = mysqli_query($conn, $sql_active);
$active_count = 0;
if ($res_active && $row = mysqli_fetch_assoc($res_active)) {
    $active_count = (int)$row['total_active'];
}

// 3. Fetch Today's Pickups count for this charity (pickup_date = CURDATE())
$sql_pickups = "SELECT COUNT(*) AS today_pickups 
                FROM pickup_deliveries pd 
                INNER JOIN food_claims fc ON pd.claim_id = fc.claim_id 
                WHERE fc.charity_id = $charity_id 
                AND pd.pickup_date = CURDATE() 
                AND pd.delivery_status NOT IN ('Completed', 'Cancelled')";
$res_pickups = mysqli_query($conn, $sql_pickups);
$pickups_count = 0;
if ($res_pickups && $row = mysqli_fetch_assoc($res_pickups)) {
    $pickups_count = (int)$row['today_pickups'];
}

// 4. Fetch total People Served by this charity from food_distributions
$sql_served = "SELECT SUM(fd.people_served) AS total_served 
               FROM food_distributions fd 
               INNER JOIN food_claims fc ON fd.claim_id = fc.claim_id 
               WHERE fc.charity_id = $charity_id";
$res_served = mysqli_query($conn, $sql_served);
$served_count = 0;
if ($res_served && $row = mysqli_fetch_assoc($res_served)) {
    $served_count = $row['total_served'] ? (int)$row['total_served'] : 0;
}

// 5. Fetch Recent Claimed Food list for this charity
$sql_recent = "SELECT 
                d.food_name, 
                c.quantity_claimed, 
                d.quantity_unit, 
                u.full_name AS donor_name, 
                c.claim_date, 
                c.status 
               FROM food_claims c 
               INNER JOIN food_donations d ON c.donation_id = d.donation_id 
               INNER JOIN users u ON d.donor_id = u.user_id 
               WHERE c.charity_id = $charity_id 
               ORDER BY c.claim_date DESC 
               LIMIT 5";
$res_recent = mysqli_query($conn, $sql_recent);
$recent_rows = array();
if ($res_recent) {
    while ($row = mysqli_fetch_assoc($res_recent)) {
        $recent_rows[] = array(
            'food_name'            => $row['food_name'],
            'quantity_claimed'     => $row['quantity_claimed'],
            'quantity_unit'        => $row['quantity_unit'],
            'donor_name'           => $row['donor_name'],
            'claim_date_formatted' => date('M d, Y', strtotime($row['claim_date'])),
            'claim_time_formatted' => date('h:i A', strtotime($row['claim_date'])),
            'status'               => strtoupper($row['status'])
        );
    }
}

// 6. Fetch Broadcast Notifications
$res_notif = mysqli_query($conn, "SELECT title, message FROM broadcasts WHERE status = 'Sent' AND audience IN ('All Users', 'Charities Only') ORDER BY created_at DESC LIMIT 5");
$notifications = mysqli_fetch_all($res_notif, MYSQLI_ASSOC);


// Return JSON output
echo json_encode([
    'success'       => true,
    'charity_name'  => $charity_name,
    'available_count' => number_format($available_count),
    'active_count'    => number_format($active_count),
    'pickups_count'   => number_format($pickups_count),
    'served_count'    => number_format($served_count),
    'recent_claims'   => $recent_rows,
    'notifications'   => $notifications
]);

?>
