let activeUserId = 0;

// Function to load contacts and message thread
async function loadChat(userId = 0) {
    let response = await fetch(`php/chat_process.php?receiver_id=${userId}`);
    let data = await response.json();

    if (data.error === 'unauthorized') {
        window.location.href = data.redirect;
        return;
    }

    activeUserId = data.receiver_id;

    // 1. Render Contacts List
    let listContainer = document.getElementById('conversations-list');
    if (listContainer && data.users) {
        let filterText = document.getElementById('search-users') ? document.getElementById('search-users').value.toLowerCase().trim() : '';
        let filteredUsers = data.users.filter(u => !filterText || u.full_name.toLowerCase().includes(filterText) || u.role.toLowerCase().includes(filterText));

        if (filteredUsers.length > 0) {
            let html = '';
            for (let u of filteredUsers) {
                let isSelected = u.user_id == activeUserId ? 'selected' : '';
                html += `
                    <div class="conversation ${isSelected}" onclick="loadChat(${u.user_id})">
                        <div class="conversation-avatar"><i class="fa-solid fa-user"></i></div>
                        <div class="conversation-info">
                            <div class="conversation-top">
                                <h3>${u.full_name}</h3>
                            </div>
                            <p>${u.role}</p>
                        </div>
                    </div>`;
            }
            listContainer.innerHTML = html;
        } else {
            listContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: #888; font-size: 13px;">No users available.</div>';
        }
    }

    // 2. Update Active Chat Header
    if (data.active_user) {
        let nameElem = document.getElementById('active-user-name');
        let roleElem = document.getElementById('active-user-role');
        if (nameElem) nameElem.innerText = data.active_user.full_name;
        if (roleElem) roleElem.innerText = data.active_user.role;
    }

    // 3. Render Messages Window
    let msgContainer = document.getElementById('messages-container');
    if (msgContainer && data.messages) {
        if (data.messages.length > 0) {
            let html = '';
            for (let m of data.messages) {
                if (m.is_me) {
                    html += `
                        <div class="message sent">
                            <div>
                                <div class="message-bubble">${escapeHtml(m.text)}</div>
                                <span class="message-time">${m.time}</span>
                            </div>
                        </div>`;
                } else {
                    html += `
                        <div class="message received">
                            <div class="message-avatar"><i class="fa-solid fa-user"></i></div>
                            <div>
                                <div class="message-bubble">${escapeHtml(m.text)}</div>
                                <span class="message-time">${m.time}</span>
                            </div>
                        </div>`;
                }
            }
            msgContainer.innerHTML = html;
            msgContainer.scrollTop = msgContainer.scrollHeight;
        } else {
            msgContainer.innerHTML = '<div style="padding: 40px; text-align: center; color: #888; font-size: 13px;">No messages yet. Start the conversation!</div>';
        }
    }

    // Render Notifications
    if (data.notifications && data.notifications.length > 0) {
        let html = '';
        for (let n of data.notifications) {
            html += '<div class="notif-item" style="padding: 8px 0; border-bottom: 1px solid #f5f5f5;"><strong style="font-size: 12px; color: #C54716; display: block;">' + escapeHtml(n.title) + '</strong><p style="font-size: 11px; color: #555; margin: 3px 0 0 0; line-height: 1.4;">' + escapeHtml(n.message) + '</p></div>';
        }
        let notifList = document.getElementById('notif-list');
        if (notifList) notifList.innerHTML = html;
        let badge = document.getElementById('notif-badge');
        if (badge) {
            badge.innerText = data.notifications.length;
            badge.style.display = 'inline-block';
        }
    }
}

// Escape HTML for safety
function escapeHtml(text) {
    let div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}

// Toggle notification dropdown
function toggleNotif(e) {
    if (e) e.stopPropagation();
    let box = document.getElementById('notif-dropdown');
    if (box) {
        box.style.display = (box.style.display === 'block') ? 'none' : 'block';
    }
}

document.addEventListener('click', function(e) {
    var box = document.getElementById('notif-dropdown');
    if (box && box.style.display === 'block') {
        var notifContainer = e.target.closest('.notif-container');
        if (!notifContainer) {
            box.style.display = 'none';
        }
    }
});

// Send message
async function sendMessage() {
    if (!activeUserId) return alert('Please select a user to chat with.');

    let input = document.getElementById('message-text');
    let text = input ? input.value.trim() : '';
    if (!text) return;

    await fetch(`php/chat_process.php?action=send&receiver_id=${activeUserId}&text=${encodeURIComponent(text)}`);
    input.value = '';
    loadChat(activeUserId);
}

// Keypress handler for Enter key
function handleKeyPress(event) {
    if (event.key === 'Enter') {
        sendMessage();
    }
}

// Tab filter function
function filterTab(role, btn) {
    let buttons = document.querySelectorAll('.chat-tabs button');
    for (let b of buttons) {
        b.classList.remove('active-tab');
    }
    btn.classList.add('active-tab');

    let cards = document.querySelectorAll('.conversation');
    for (let card of cards) {
        if (role === 'All' || card.innerText.includes(role)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    }
}

// Initial load
document.addEventListener("DOMContentLoaded", function () {
    loadChat();
});
