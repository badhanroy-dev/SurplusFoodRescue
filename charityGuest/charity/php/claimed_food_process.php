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

// Get current logged-in charity user ID
$charity_id = (int)$_SESSION['user_id'];

// 1. Fetch Total Claimed Items count for this charity
$sql_total = "SELECT COUNT(*) AS total_items FROM food_claims WHERE charity_id = $charity_id";
$res_total = mysqli_query($conn, $sql_total);
$total_count = 0;
if ($res_total && $row = mysqli_fetch_assoc($res_total)) {
    $total_count = (int)$row['total_items'];
}

// 2. Fetch In Transit count for this charity ('In Transit' status in claim or pickup_delivery)
$sql_transit = "SELECT COUNT(*) AS total_transit 
                FROM food_claims fc 
                LEFT JOIN pickup_deliveries pd ON fc.claim_id = pd.claim_id 
                WHERE fc.charity_id = $charity_id 
                AND (fc.status = 'In Transit' OR pd.delivery_status IN ('Picked Up', 'In Transit'))";
$res_transit = mysqli_query($conn, $sql_transit);
$transit_count = 0;
if ($res_transit && $row = mysqli_fetch_assoc($res_transit)) {
    $transit_count = (int)$row['total_transit'];
}

// 3. Fetch Awaiting Pickup count ('Claimed' or 'Scheduled' status, not yet in transit/completed)
$sql_awaiting = "SELECT COUNT(*) AS total_awaiting 
                 FROM food_claims fc 
                 LEFT JOIN pickup_deliveries pd ON fc.claim_id = pd.claim_id 
                 WHERE fc.charity_id = $charity_id 
                 AND fc.status IN ('Claimed', 'Scheduled') 
                 AND (pd.delivery_status IS NULL OR pd.delivery_status IN ('Available', 'Assigned', 'Accepted'))";
$res_awaiting = mysqli_query($conn, $sql_awaiting);
$awaiting_count = 0;
if ($res_awaiting && $row = mysqli_fetch_assoc($res_awaiting)) {
    $awaiting_count = (int)$row['total_awaiting'];
}

// 4. Fetch Ready To Log / Completed count ('Completed' or 'Delivered' and not yet logged in food_distributions)
$sql_ready = "SELECT COUNT(*) AS total_ready 
              FROM food_claims fc 
              LEFT JOIN pickup_deliveries pd ON fc.claim_id = pd.claim_id 
              WHERE fc.charity_id = $charity_id 
              AND (fc.status = 'Completed' OR pd.delivery_status IN ('Delivered', 'Completed'))
              AND fc.claim_id NOT IN (SELECT claim_id FROM food_distributions)";
$res_ready = mysqli_query($conn, $sql_ready);
$ready_count = 0;
if ($res_ready && $row = mysqli_fetch_assoc($res_ready)) {
    $ready_count = (int)$row['total_ready'];
}

// 5. Fetch all Claimed Food Items for this charity with details
$sql_claimed_list = "SELECT 
                        c.claim_id,
                        c.donation_id,
                        d.food_name,
                        c.quantity_claimed,
                        d.quantity_unit,
                        d.expiry_date,
                        u.full_name AS donor_name,
                        c.claim_date,
                        c.status AS claim_status,
                        pd.delivery_status,
                        ov.otp_code
                     FROM food_claims c
                     INNER JOIN food_donations d ON c.donation_id = d.donation_id
                     INNER JOIN users u ON d.donor_id = u.user_id
                     LEFT JOIN pickup_deliveries pd ON c.claim_id = pd.claim_id
                     LEFT JOIN otp_verifications ov ON pd.delivery_id = ov.delivery_id AND ov.status = 'Pending'
                     WHERE c.charity_id = $charity_id
                     ORDER BY c.claim_date DESC";

$res_claimed_list = mysqli_query($conn, $sql_claimed_list);
$claimed_items = array();

if ($res_claimed_list) {
    while ($row = mysqli_fetch_assoc($res_claimed_list)) {
        // Calculate remaining time until expiry
        $expiry_timestamp = strtotime($row['expiry_date']);
        $now = time();
        $diff_sec = $expiry_timestamp - $now;

        if ($diff_sec > 0) {
            $hours = floor($diff_sec / 3600);
            $mins  = floor(($diff_sec % 3600) / 60);

            if ($hours >= 24) {
                $days = floor($hours / 24);
                $expiry_text = "{$days} days";
            } elseif ($hours > 0) {
                $expiry_text = "{$hours} hrs";
            } else {
                $expiry_text = "{$mins} mins";
            }
        } else {
            $expiry_text = "Expired";
        }

        $is_urgent = ($diff_sec <= 0 || ($diff_sec / 3600) <= 24);

        // Status mapping
        $status       = $row['delivery_status'] ?: $row['claim_status'];
        $status_text  = $status;
        $status_class = 'claimed';
        $status_icon  = 'fa-check';

        if (in_array($status, ['In Transit', 'Picked Up'])) {
            $status_text  = 'In Transit'; 
            $status_class = 'transit'; 
            $status_icon  = 'fa-truck';
        } elseif (in_array($status, ['Scheduled', 'Assigned', 'Accepted', 'Available'])) {
            $status_text  = 'Awaiting Pickup'; 
            $status_class = 'awaiting'; 
            $status_icon  = 'fa-clock';
        } elseif (in_array($status, ['Delivered', 'Completed'])) {
            $status_text  = 'Completed'; 
            $status_class = 'claimed'; 
            $status_icon  = 'fa-check-double';
        }

        $claimed_items[] = array(
            'claim_id'             => $row['claim_id'],
            'donation_id'          => $row['donation_id'],
            'food_name'            => $row['food_name'],
            'claim_date_formatted' => date('M d, h:i A', strtotime($row['claim_date'])),
            'donor_name'           => $row['donor_name'],
            'quantity'             => (float)$row['quantity_claimed'],
            'quantity_unit'        => $row['quantity_unit'],
            'expiry_text'          => $expiry_text,
            'is_urgent'            => $is_urgent,
            'status_text'          => $status_text,
            'status_class'         => $status_class,
            'status_icon'          => $status_icon,
            'otp_code'             => !empty($row['otp_code']) ? $row['otp_code'] : '—'
        );
    }
}

// Fetch Broadcast Notifications
$res_notif = mysqli_query($conn, "SELECT title, message FROM broadcasts WHERE status = 'Sent' AND audience IN ('All Users', 'Charities Only') ORDER BY created_at DESC LIMIT 5");
$notifications = $res_notif ? mysqli_fetch_all($res_notif, MYSQLI_ASSOC) : [];

// Return JSON response
echo json_encode([
    'success'        => true,
    'total_count'    => number_format($total_count),
    'transit_count'  => number_format($transit_count),
    'awaiting_count' => number_format($awaiting_count),
    'ready_count'    => number_format($ready_count),
    'claimed_items'  => $claimed_items,
    'notifications'  => $notifications
]);

?>
