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
    <title>Rider Portal - Chat</title>
    <link rel="stylesheet" href="volunteer-chat.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<div class="container">
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
    <div class="main">
        <div class="page-header">
            <h2 class="page-heading">Volunteer Chat</h2>
            <div class="profile-section">
                <i class="fa-solid fa-bell"></i>
                <img src="https://i.pravatar.cc/50" class="profile-img" alt="profile">
            </div>
        </div>
        <div class="chat-layout">
            <div class="chat-list content-box">
                <div class="chat-tabs">
                    <button class="chat-tab active">All</button>
                    <button class="chat-tab">Donors</button>
                    <button class="chat-tab">Charities</button>
                </div>
                <div class="chat-preview active" role="button" tabindex="0" aria-pressed="true">
                    <div class="chat-avatar">GB</div>
                    <div class="chat-preview-text">
                        <p class="chat-preview-name">The Green Bistro</p>
                        <p class="chat-preview-msg">Pickup coordination</p>
                    </div>
                    <span class="chat-time">Now</span>
                </div>
                <div class="chat-preview" role="button" tabindex="0" aria-pressed="false">
                    <div class="chat-avatar">US</div>
                    <div class="chat-preview-text">
                        <p class="chat-preview-name">Urban Shelter HQ</p>
                        <p class="chat-preview-msg">Charity partner</p>
                    </div>
                    <span class="chat-time">Today</span>
                </div>
            </div>
            <div class="chat-window content-box">
                <div class="chat-window-header">
                    <div>
                        <p class="chat-window-name" id="chat-window-name">The Green Bistro</p>
                        <p class="chat-window-status">Live Chat</p>
                    </div>
                </div>
                <div class="chat-messages" id="chat-messages">
                    <p class="chat-date">TODAY</p>
                    <div class="message received">
                        <p>Hello! Let us know when you arrive for pickup.</p>
                        <span class="msg-time">Now</span>
                    </div>
                </div>
                <div class="chat-input-bar">
                    <input type="text" id="chat-input" class="chat-input" placeholder="Type a message...">
                    <button id="send-message" class="send-btn">Send</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
var previews = document.getElementsByClassName('chat-preview');
var chatNames = ['The Green Bistro', 'Urban Shelter HQ'];
var starterMessages = ['Hello! Let us know when you arrive for pickup.', 'Hello! We are ready to receive the delivery.'];
var conversationMessages = [
    [{text: starterMessages[0], sent: false, time: 'Now'}],
    [{text: starterMessages[1], sent: false, time: 'Today'}]
];
var currentChat = 0;

function showMessages() {
    var area = document.getElementById('chat-messages');
    area.innerHTML = '';
    var date = document.createElement('p');
    date.className = 'chat-date';
    date.textContent = 'TODAY';
    area.appendChild(date);
    for (var i = 0; i < conversationMessages[currentChat].length; i++) {
        var item = conversationMessages[currentChat][i];
        var message = document.createElement('div');
        message.className = item.sent ? 'message sent' : 'message received';
        var paragraph = document.createElement('p');
        paragraph.textContent = item.text;
        var time = document.createElement('span');
        time.className = 'msg-time';
        time.textContent = item.time;
        message.appendChild(paragraph);
        message.appendChild(time);
        area.appendChild(message);
    }
    area.scrollTop = area.scrollHeight;
}

function selectChat(index) {
    currentChat = index;
    for (var i = 0; i < previews.length; i++) {
        previews[i].classList.remove('active');
        previews[i].setAttribute('aria-pressed', 'false');
    }
    previews[index].classList.add('active');
    previews[index].setAttribute('aria-pressed', 'true');
    document.getElementById('chat-window-name').textContent = chatNames[index];
    showMessages();
}

for (var i = 0; i < previews.length; i++) {
    previews[i].setAttribute('data-chat-index', i);
    previews[i].addEventListener('click', function() {
        selectChat(Number(this.getAttribute('data-chat-index')));
    });
}

function sendMessage() {
    var input = document.getElementById('chat-input');
    var text = input.value.trim();
    if (text !== '') {
        var time = new Date().toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
        conversationMessages[currentChat].push({text: text, sent: true, time: time});
        showMessages();
        input.value = '';
    }
}

document.getElementById('send-message').addEventListener('click', sendMessage);
document.getElementById('chat-input').addEventListener('keydown', function(event) {
    if (event.key === 'Enter') {
        sendMessage();
    }
});
</script>
</body>
</html>
