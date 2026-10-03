let activeUserId = 0;// id of the person currently in chat with

// Function to load both user contacts and active chat thread together
async function loadChat(userId = 0) {
    let response = await fetch(`php/chat_process.php?receiver_id=${userId}`);
    let data = await response.json();

    if (data.error === 'unauthorized') {
        window.location.href = data.redirect;
        return;
    }

    activeUserId = data.receiver_id;

    // 1. Render Contacts Sidebar
    let listContainer = document.getElementById('conversations-list');
    if (listContainer && data.users) {
        if (data.users.length > 0) {
            let html = '';
            for (let u of data.users) {
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
            listContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: #888;">No users available.</div>';
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
                                <div class="message-bubble">${m.text}</div>
                                <span class="message-time">${m.time}</span>
                            </div>
                        </div>`;
                } else {
                    html += `
                        <div class="message received">
                            <div class="message-avatar"><i class="fa-solid fa-user"></i></div>
                            <div>
                                <div class="message-bubble">${m.text}</div>
                                <span class="message-time">${m.time}</span>
                            </div>
                        </div>`;
                }
            }
            msgContainer.innerHTML = html;
            msgContainer.scrollTop = msgContainer.scrollHeight;
        } else {
            msgContainer.innerHTML = '<div style="padding: 40px; text-align: center; color: #888;">No messages yet. Start the conversation!</div>';
        }
    }

    // Populate Notifications
    if (data.notifications && data.notifications.length > 0) {
        let html = '';
        for (let n of data.notifications) {
            html += '<div class="notif-item"><h4>' + n.title + '</h4><p>' + n.message + '</p></div>';
        }
        let notifList = document.getElementById('notif-list');
        if (notifList) notifList.innerHTML = html;
    }
}

// Toggle notification dropdown
function toggleNotif() {
    let box = document.getElementById('notif-dropdown');
    if (box) {
        box.style.display = (box.style.display === 'block') ? 'none' : 'block';
    }
}


// Send message
async function sendMessage() {
    if (!activeUserId) return alert('Please select a user to chat with.');

    let input = document.getElementById('message-text');
    let text = input ? input.value.trim() : '';
    if (!text) return;

    await fetch(`php/chat_process.php?action=send&receiver_id=${activeUserId}&text=${text}`);
    input.value = '';
    loadChat(activeUserId);
}
 
// Tab filter function
function filterTab(role, btn) {
    let buttons = document.querySelectorAll('.chat-tabs button'); // button with class chat-tabs
    for (let b of buttons) {
        b.classList.remove('active-tab');
    }
    btn.classList.add('active-tab');

    let cards = document.querySelectorAll('.conversation');// .conversation from loadchat function
    for (let card of cards) {
        if (role === 'All' || card.innerText.includes(role)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    }
}

// Initial load
loadChat();
