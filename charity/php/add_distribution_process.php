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

$charity_id = (int)$_SESSION['user_id'];
$action     = isset($_GET['action']) ? $_GET['action'] : 'get';

// 1. SAVE DISTRIBUTION RECORD (action=save)
if ($action === 'save') {
    $claim_id             = isset($_GET['claim_id']) ? (int)$_GET['claim_id'] : 0;
    $quantity_distributed = isset($_GET['quantity']) ? (float)$_GET['quantity'] : 0;
    $quantity_unit        = isset($_GET['quantity_unit']) ? trim($_GET['quantity_unit']) : 'unit';
    $quality              = isset($_GET['quality']) ? trim($_GET['quality']) : 'Good';
    $people_served        = isset($_GET['people_served']) ? (int)$_GET['people_served'] : 0;
    $remarks              = isset($_GET['remarks']) ? trim($_GET['remarks']) : '';

    if ($claim_id <= 0 || $quantity_distributed <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please select a valid food item and quantity.']);
        exit();
    }

    // Fetch food item name for this claim
    $res_claim = mysqli_query($conn, "SELECT d.food_name FROM food_claims c INNER JOIN food_donations d ON c.donation_id = d.donation_id WHERE c.claim_id = $claim_id AND c.charity_id = $charity_id");
    
    if ($res_claim && $row_claim = mysqli_fetch_assoc($res_claim)) {
        $food_item_name = $row_claim['food_name'];

        // Insert new record into food_distributions table
        $sql_insert = "INSERT INTO food_distributions (claim_id, food_item_name, quantity_distributed, quantity_unit, quality, people_served, remarks) 
                       VALUES ($claim_id, '$food_item_name', $quantity_distributed, '$quantity_unit', '$quality', $people_served, '$remarks')";
        
        if (mysqli_query($conn, $sql_insert)) {
            echo json_encode([
                'success'  => true,
                'message'  => 'Distribution record saved successfully!',
                'redirect' => 'distribution-log.html'
            ]);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error while saving distribution record.']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Selected claimed food item not found.']);
        exit();
    }
}

// 2. FETCH COMPLETED CLAIMED ITEMS FOR DROPDOWN (action=get)
$sql_items = "SELECT 
                c.claim_id,
                d.food_name,
                c.quantity_claimed,
                d.quantity_unit
              FROM food_claims c
              INNER JOIN food_donations d ON c.donation_id = d.donation_id
              LEFT JOIN pickup_deliveries pd ON c.claim_id = pd.claim_id
              WHERE c.charity_id = $charity_id
              AND (c.status = 'Completed' OR pd.delivery_status IN ('Delivered', 'Completed'))
              ORDER BY c.claim_date DESC";

$res_items = mysqli_query($conn, $sql_items);
$items_list = array();

if ($res_items) {
    while ($row = mysqli_fetch_assoc($res_items)) {
        $items_list[] = array(
            'claim_id'      => $row['claim_id'],
            'food_name'     => $row['food_name'],
            'quantity'      => (float)$row['quantity_claimed'],
            'quantity_unit' => $row['quantity_unit']
        );
    }
}

// Fetch Broadcast Notifications
$res_notif = mysqli_query($conn, "SELECT title, message FROM broadcasts WHERE status = 'Sent' AND audience IN ('All Users', 'Charities Only') ORDER BY created_at DESC LIMIT 5");
$notifications = $res_notif ? mysqli_fetch_all($res_notif, MYSQLI_ASSOC) : [];

echo json_encode([
    'success'       => true,
    'items'         => $items_list,
    'notifications' => $notifications
]);

?>
