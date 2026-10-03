<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../charityGuest/guest/login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodRescue - Rider Chat</title>
    <link rel="stylesheet" href="volunteer-chat.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="dashboard">

        <!-- SIDEBAR -->
        <div class="sidebar">
            <div class="sidebar-title">
                <h1>Rider Portal</h1>
                <p>Mission-Driven Service</p>
            </div>
            <ul class="menu">
                <li class="menu-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="menu-item"><a href="available-tasks.php">Available Tasks</a></li>
                <li class="menu-item"><a href="assigned-tasks.php">Assigned Tasks</a></li>
                <li class="menu-item"><a href="otp-verification.php">OTP Verification</a></li>
                <li class="menu-item"><a href="update-delivery-status.php">Update Delivery Status</a></li>
                <li class="menu-item"><a href="report-delay.php">Report Delay</a></li>
                <li class="menu-item active"><a href="volunteer-chat.php">Chat</a></li>
            </ul>
            <ul class="menu bottom-menu">
                <li class="menu-item"><a href="../charityGuest/guest/settings.html">Settings</a></li>
                <li class="menu-item"><a href="../charityGuest/guest/logout.php">Logout</a></li>
            </ul>
        </div>

        <!-- MAIN CONTENT -->
        <main class="main">

            <!-- TOP BAR -->
            <header class="topbar">
                <input type="search" id="search-users" placeholder="Search users..." oninput="loadChat()">

                <div class="top-icons">
                    <div class="notif-container" style="position: relative; cursor: pointer;" onclick="toggleNotif(event)">
                        <i id="notif-bell" class="fa-solid fa-bell" style="font-size: 18px; color: #A33215;"></i>
                        <span id="notif-badge" style="display: none; position: absolute; top: -8px; right: -8px; background: #e71c16; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: bold; line-height: 1;"></span>
                        <div id="notif-dropdown" class="notif-dropdown" style="display: none; position: absolute; right: 0; top: 30px; width: 260px; background: white; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000; padding: 12px; text-align: left;">
                            <h4 style="margin: 0 0 8px 0; color: #A33215; font-size: 13px; border-bottom: 1px solid #eee; padding-bottom: 6px;">Broadcast Notifications</h4>
                            <div id="notif-list" style="font-size: 12px; color: #888;">No new notifications</div>
                        </div>
                    </div>
                    <span>
                        <i class="fa-solid fa-circle-user" style="font-size: 18px; color: #A33215;"></i>
                    </span>
                </div>
            </header>

            <!-- CHAT CONTENT -->
            <section class="content">
                <div class="chat-container">

                    <!-- CHAT LIST -->
                    <div class="chat-list">

                        <!-- CHAT LIST HEADER -->
                        <div class="chat-list-header">
                            <h2>Messages</h2>

                            <!-- TABS -->
                            <div class="chat-tabs">
                                <button class="active-tab" onclick="filterTab('All', this)">All</button>
                                <button onclick="filterTab('Donor', this)">Donors</button>
                                <button onclick="filterTab('Charity', this)">Charities</button>
                            </div>
                        </div>

                        <!-- CONVERSATIONS LIST CONTAINER -->
                        <div id="conversations-list"></div>

                    </div>

                    <!-- CHAT WINDOW -->
                    <div class="chat-window">

                        <!-- CHAT HEADER -->
                        <div class="chat-header">
                            <div class="chat-user">
                                <div class="chat-avatar">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <div>
                                    <h3 id="active-user-name">Select a conversation</h3>
                                    <p id="active-user-role" style="font-size:12px; color:#666;"></p>
                                </div>
                            </div>
                        </div>

                        <!-- MESSAGES -->
                        <div class="messages" id="messages-container"></div>

                        <!-- MESSAGE INPUT -->
                        <div class="message-input">
                            <input type="text" id="message-text" placeholder="Type a message..." onkeypress="handleKeyPress(event)">
                            <button onclick="sendMessage()">
                                <i class="fa-solid fa-paper-plane"></i>
                            </button>
                        </div>

                    </div>

                </div>
            </section>

        </main>

    </div>

    <script src="js/volunteer-chat.js"></script>
</body>
</html>
