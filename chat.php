<?php
include "db.php";
include "admin_auth.php";
$admin_id = $_SESSION['admin_user_id'];

if (isset($_GET['action'])) {
    header("Content-Type: application/json");

    if ($_GET['action'] == "users") {
        $role = isset($_GET['role']) ? $_GET['role'] : "All";

        if ($role == "Donors") {
            $sql = "SELECT user_id, full_name, role FROM users
                    WHERE role='Donor' AND account_status IN ('Active','Verified')
                    ORDER BY full_name";
        } elseif ($role == "Charities") {
            $sql = "SELECT user_id, full_name, role FROM users
                    WHERE role='Charity' AND account_status IN ('Active','Verified')
                    ORDER BY full_name";
        } else {
            $sql = "SELECT user_id, full_name, role FROM users
                    WHERE role IN ('Donor','Charity') AND account_status IN ('Active','Verified')
                    ORDER BY full_name";
        }

        $result = mysqli_query($conn, $sql);
        $users = [];

        while ($user = mysqli_fetch_assoc($result)) {
            $user_id = $user['user_id'];

            $msg_sql = "SELECT message_text, sent_at, sender_id
                        FROM messages
                        WHERE (sender_id=$admin_id AND receiver_id=$user_id)
                           OR (sender_id=$user_id AND receiver_id=$admin_id)
                        ORDER BY sent_at DESC
                        LIMIT 1";

            $msg_result = mysqli_query($conn, $msg_sql);
            $last_message = mysqli_fetch_assoc($msg_result);

            $users[] = [
                "user_id" => $user['user_id'],
                "full_name" => $user['full_name'],
                "role" => $user['role'],
                "message" => $last_message ? $last_message['message_text'] : "No messages yet",
                "sent_at" => $last_message ? $last_message['sent_at'] : null
            ];
        }

        echo json_encode($users);
        exit;
    }

    if ($_GET['action'] == "messages") {
        $user_id = intval($_GET['user_id']);

        $read_sql = "UPDATE messages
                     SET is_read=1
                     WHERE sender_id=$user_id AND receiver_id=$admin_id";

        mysqli_query($conn, $read_sql);

        $sql = "SELECT message_id, sender_id, receiver_id, message_text, sent_at
                FROM messages
                WHERE (sender_id=$admin_id AND receiver_id=$user_id)
                   OR (sender_id=$user_id AND receiver_id=$admin_id)
                ORDER BY sent_at ASC";

        $result = mysqli_query($conn, $sql);
        $messages = [];

        while ($message = mysqli_fetch_assoc($result)) {
            $messages[] = $message;
        }

        echo json_encode($messages);
        exit;
    }

    if ($_GET['action'] == "send") {
        $receiver_id = intval($_POST['receiver_id']);
        $message = mysqli_real_escape_string($conn, $_POST['message']);

        if ($receiver_id > 0 && $message != "") {
            $sql = "INSERT INTO messages (sender_id, receiver_id, message_text)
                    VALUES ($admin_id, $receiver_id, '$message')";

            if (mysqli_query($conn, $sql)) {
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["success" => false, "error" => mysqli_error($conn)]);
            }
        } else {
            echo json_encode(["success" => false, "error" => "Message cannot be empty"]);
        }

        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FoodRescue Chat</title>
<link rel="stylesheet" href="css/chat.css">
</head>

<body>
<div class="webpage">

<aside>
<div class="sidebar-top">
<h2>FoodRescue</h2>
<p>Admin Portal</p>
<ul>
<li><a href="index.php">Dashboard</a></li>
<li><a href="donoracc.php">Donor Accounts</a></li>
<li><a href="volunteer_verify.php">Volunteer Verification</a></li>
<li><a href="foodcategories.php">Food Categories</a></li>
<li><a href="analytics.php">Analytics</a></li>
<li><a href="broadcasts.php">Broadcasts</a></li>
<li class="active"><a href="chat.php">Chat</a></li>
</ul>
</div>

<div class="sidebar-bottom">
<ul>
<li><a href="settings.php">Settings</a></li>
            <li><a href="logout.php">Logout</a></li>
</ul>
</div>
</aside>

<main>

<header class="header">
<h1>Chat</h1>

<div class="admin-profile">
<img src="images/notifications.png" alt="Notifications" class="notification-icon">
<img src="images/profile.png" alt="Admin Profile" class="profile-icon">
<h4>Admin</h4>
</div>
</header>

<div class="chat-container">

<div class="chat-list">

<div class="chat-list-header">
<h2>Messages</h2>

<div class="chat-tabs">
<button class="active-tab" data-role="All">All</button>
<button data-role="Donors">Donors</button>
<button data-role="Charities">Charities</button>
</div>
</div>

<div id="conversationList"></div>

</div>

<div class="chat-window">

<div class="chat-header">
<div class="chat-user">
<div>
<h3 id="chatUserName">Select a conversation</h3>
<p id="chatUserRole"></p>
</div>
</div>
</div>

<div class="messages" id="messages"></div>

<div class="message-input">
<input type="text" id="messageInput" placeholder="Type a message...">
<button type="button" id="sendBtn">➤</button>
</div>

</div>

</div>

</main>
</div>

<script src="js/chat.js"></script>
</body>
</html>