<?php
// Start session & set header for JSON output
session_start();
header('Content-Type: application/json');

// Include database connection
require_once __DIR__ . '/../../db.php';

// Check user login authorization
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Charity') {
    echo json_encode(['error' => 'unauthorized', 'redirect' => '../guest/login.html']);
    exit();
}

$charity_id = (int)$_SESSION['user_id'];

// =============================================================
// SECTION 1: SUBMIT NEW REPORT (POST Request)
// =============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_type         = trim($_POST['report_type'] ?? '');
    $related_donation_id = (int)($_POST['related_donation_id'] ?? 0);
    $description         = trim($_POST['description'] ?? '');

    // Validate mandatory fields
    if (empty($report_type) || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all mandatory fields.']);
        exit();
    }

    // Handle evidence file upload
    $evidence_path = null;
    if (isset($_FILES['evidence_file']) && $_FILES['evidence_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp   = $_FILES['evidence_file']['tmp_name'];
        $file_name  = $_FILES['evidence_file']['name'];
        $file_ext   = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $upload_dir = __DIR__ . '/../../uploads/evidence/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $new_file_name = time() . '_' . uniqid() . '.' . $file_ext;
        if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
            $evidence_path = 'uploads/evidence/' . $new_file_name;
        }
    }

    // Save report 
    $evidence_val = $evidence_path;
    $donation_val = $related_donation_id > 0 ? $related_donation_id : "NULL";

    $sql = "INSERT INTO issue_reports (reporter_id, report_type, related_donation_id, description, evidence_file, status, resolution_details) 
            VALUES ($charity_id, '$report_type', $donation_val, '$description', $evidence_val, 'Under Review', 'Support team is reviewing the reported issue.')";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Report submitted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error while submitting report.']);
    }
    exit();
}

// =============================================================
// SECTION 2: FETCH DATA FOR PAGE (GET Request)
// =============================================================

// 1. Fetch claimed food listings for the dropdown
$food_listings = array();
$res_food = mysqli_query($conn, "SELECT DISTINCT d.donation_id, d.food_name FROM food_claims c INNER JOIN food_donations d ON c.donation_id = d.donation_id WHERE c.charity_id = $charity_id ORDER BY c.claim_date DESC");
if ($res_food) {
    while ($row = mysqli_fetch_assoc($res_food)) {
        $food_listings[] = $row;
    }
}

// 2. Fetch all issue reports submitted by this charity
$reports_list = array();
$sql_reports = "SELECT ir.issue_id, ir.report_type, ir.status, ir.resolution_details, ir.created_at, fd.food_name 
                FROM issue_reports ir 
                LEFT JOIN food_donations fd ON ir.related_donation_id = fd.donation_id 
                WHERE ir.reporter_id = $charity_id 
                ORDER BY ir.created_at DESC";

$res_reports = mysqli_query($conn, $sql_reports);
if ($res_reports) {
    while ($row = mysqli_fetch_assoc($res_reports)) {
        $status       = $row['status'];
        $status_class = 'reviewing';
        $status_icon  = 'fa-clock';
        $status_text  = 'Under Review';
        $res_class    = 'pending';

        if ($status === 'Resolved') {
            $status_class = 'resolved';
            $status_icon  = 'fa-circle-check';
            $status_text  = 'Resolved';
            $res_class    = '';
        } elseif ($status === 'Closed') {
            $status_class = 'closed';
            $status_icon  = 'fa-circle-xmark';
            $status_text  = 'Closed';
            $res_class    = '';
        }

        $reports_list[] = array(
            'issue_id'           => $row['issue_id'],
            'report_type'        => $row['report_type'],
            'food_name'          => $row['food_name'] ?? 'N/A',
            'date_formatted'     => date('M j, Y', strtotime($row['created_at'])),
            'status_text'        => $status_text,
            'status_class'       => $status_class,
            'status_icon'        => $status_icon,
            'resolution_details' => $row['resolution_details'] ?: 'No resolution details yet.',
            'resolution_class'   => $res_class
        );
    }
}

// Fetch Broadcast Notifications
$res_notif = mysqli_query($conn, "SELECT title, message FROM broadcasts WHERE status = 'Sent' AND audience IN ('All Users', 'Charities Only') ORDER BY created_at DESC LIMIT 5");
$notifications = $res_notif ? mysqli_fetch_all($res_notif, MYSQLI_ASSOC) : [];

// Return JSON response to frontend
echo json_encode([
    'success'       => true,
    'food_listings' => $food_listings,
    'reports'       => $reports_list,
    'notifications' => $notifications
]);

?>
