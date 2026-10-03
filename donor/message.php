<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../charityGuest/guest/login.html");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodRescue - Messages</title>
    <link rel="stylesheet" href="message.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="dashboard">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="logo">
                <h2>FoodRescue</h2>
                <p>Donor Portal</p>
            </div>

            <nav class="menu">
                <a href="dashboard.php">Dashboard</a>
                <a href="post.php">Post Food</a>
                <a href="impact_summary.php">Impact Summary</a>
                <a href="mulreceipt.php">Receipts</a>
                <a href="message.php" class="active">Messages</a>
                <a href="history.php">Donation History</a>
                <a href="multracking.php">Pickup Status</a>
            </nav>

            <div class="bottom-menu">
                <a href="profile.php">Settings</a>
                <a href="logout.php">Logout</a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main">

            <!-- TOP BAR -->
            <header class="topbar">
                <input type="search" id="search-users" placeholder="Search users..." oninput="loadChat()">

                <div class="top-icons">
                    <span>
                        <i class="fa-solid fa-bell"></i> &nbsp; <i class="fa-solid fa-circle-user"></i>
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
                                <button onclick="filterTab('Charity', this)">Charities</button>
                                <button onclick="filterTab('Volunteer', this)">Volunteers</button>
                                <button onclick="filterTab('Admin', this)">Admin</button>
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

    <script src="js/donor-chat.js"></script>
</body>
</html>