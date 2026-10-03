<?php
// Start session for authenticated user
session_start();

// Set header for JSON output
header('Content-Type: application/json');

// Include database connection
require_once __DIR__ . '/../../db.php';

// Check if user is logged in as Charity
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Charity') {
    echo json_encode(['error' => 'unauthorized', 'redirect' => '../guest/login.html']);
    exit();
}

$charity_id = (int)$_SESSION['user_id'];

// Fetch distribution log records for this charity
$sql_dist = "SELECT 
                fd.distribution_id,
                fd.food_item_name,
                fd.quantity_distributed,
                fd.quantity_unit,
                fd.quality,
                fd.people_served,
                fd.remarks,
                fd.distributed_at,
                fc.donation_id,
                fc.quantity_claimed
            FROM food_distributions fd
            INNER JOIN food_claims fc ON fd.claim_id = fc.claim_id
            WHERE fc.charity_id = $charity_id
            ORDER BY fd.distributed_at DESC";

$res_dist = mysqli_query($conn, $sql_dist);
$distributions = array();

if ($res_dist) {
    while ($row = mysqli_fetch_assoc($res_dist)) {
        $ts = strtotime($row['distributed_at']);
        $distributions[] = array(
            'distribution_id' => (int)$row['distribution_id'],
            'food_item_name'  => $row['food_item_name'],
            'food_id'         => 'FD-' . $row['donation_id'],
            'date_month_day'  => date('M d', $ts),
            'date_year'       => date('Y', $ts),
            'received_qty'    => (float)$row['quantity_claimed'],
            'distributed_qty' => (float)$row['quantity_distributed'],
            'quantity_unit'   => $row['quantity_unit'],
            'people_served'   => (int)$row['people_served'],
            'quality'         => $row['quality'],
            'remarks'         => $row['remarks']
        );
    }
}

// Fetch Broadcast Notifications
$res_notif = mysqli_query($conn, "SELECT title, message FROM broadcasts WHERE status = 'Sent' AND audience IN ('All Users', 'Charities Only') ORDER BY created_at DESC LIMIT 5");
$notifications = $res_notif ? mysqli_fetch_all($res_notif, MYSQLI_ASSOC) : [];

// Return JSON response
echo json_encode([
    'success'       => true,
    'distributions' => $distributions,
    'notifications' => $notifications
]);

?>
