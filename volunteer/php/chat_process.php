<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

// Check authorization
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'unauthorized', 'redirect' => '../charityGuest/guest/login.html']);
    exit();
}

$my_id       = (int)$_SESSION['user_id'];
$receiver_id = (int)($_GET['receiver_id'] ?? 0);

// 1. SEND MESSAGE
if (($_GET['action'] ?? '') === 'send') {
    $text = trim($_GET['text'] ?? '');
    if ($receiver_id > 0 && !empty($text)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iis", $my_id, $receiver_id, $text);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    echo json_encode(['success' => true]);
    exit();
}

// 2. FETCH CONTACT USERS LIST
$users = [];
$res_u = mysqli_query($conn, "SELECT user_id, full_name, role FROM users WHERE user_id != $my_id AND role != 'Admin' ORDER BY full_name ASC");
if ($res_u) {
    while ($row = mysqli_fetch_assoc($res_u)) {
        $users[] = $row;
    }
}

// Auto-select first contact if none specified
if ($receiver_id === 0 && count($users) > 0) {
    $receiver_id = (int)$users[0]['user_id'];
}

// 3. FETCH MESSAGES FOR ACTIVE RECEIVER
$messages = [];
$active_user = null;

if ($receiver_id > 0) {
    // Get active user details
    $res_active = mysqli_query($conn, "SELECT user_id, full_name, role FROM users WHERE user_id = $receiver_id");
    if ($res_active) {
        $active_user = mysqli_fetch_assoc($res_active);
    }

    // Get messages history
    $sql_m = "SELECT sender_id, message_text, DATE_FORMAT(sent_at, '%h:%i %p') AS msg_time 
              FROM messages 
              WHERE (sender_id = $my_id AND receiver_id = $receiver_id) 
                 OR (sender_id = $receiver_id AND receiver_id = $my_id) 
              ORDER BY sent_at ASC";
    $res_m = mysqli_query($conn, $sql_m);
    if ($res_m) {
        while ($row = mysqli_fetch_assoc($res_m)) {
            $messages[] = [
                'text'  => $row['message_text'],
                'time'  => $row['msg_time'],
                'is_me' => ((int)$row['sender_id'] === $my_id)
            ];
        }
    }
}

// Fetch Broadcast Notifications for Volunteers
$res_notif = mysqli_query($conn, "SELECT title, message FROM broadcasts WHERE status = 'Sent' AND audience IN ('All Users', 'Volunteers Only') ORDER BY created_at DESC LIMIT 5");
$notifications = $res_notif ? mysqli_fetch_all($res_notif, MYSQLI_ASSOC) : [];

echo json_encode([
    'success'       => true,
    'users'         => $users,
    'receiver_id'   => $receiver_id,
    'active_user'   => $active_user,
    'messages'      => $messages,
    'notifications' => $notifications
]);
