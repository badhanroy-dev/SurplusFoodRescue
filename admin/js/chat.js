let selectedUser = 0;

const conversationList = document.getElementById("conversationList");
const messagesBox = document.getElementById("messages");
const messageInput = document.getElementById("messageInput");
const sendBtn = document.getElementById("sendBtn");
const chatUserName = document.getElementById("chatUserName");
const chatUserRole = document.getElementById("chatUserRole");

function loadUsers(role = "All") {
    fetch("chat.php?action=users&role=" + role)
    .then(response => response.json())
    .then(users => {
        conversationList.innerHTML = "";

        users.forEach(user => {
            const conversation = document.createElement("div");
            conversation.className = "conversation";

            let time = "";

            if (user.sent_at) {
                const date = new Date(user.sent_at.replace(" ", "T"));
                time = date.toLocaleTimeString([], {
                    hour: "2-digit",
                    minute: "2-digit"
                });
            }

            conversation.innerHTML = `
                <div class="conversation-info">
                    <div class="conversation-top">
                        <h3>${user.full_name}</h3>
                        <span>${time}</span>
                    </div>
                    <p>${user.message}</p>
                </div>
            `;

            conversation.addEventListener("click", function() {
                selectUser(user, conversation);
            });

            conversationList.appendChild(conversation);
        });
    });
}

function selectUser(user, element) {
    selectedUser = user.user_id;

    document.querySelectorAll(".conversation").forEach(item => {
        item.classList.remove("selected");
    });

    element.classList.add("selected");

    chatUserName.textContent = user.full_name;
    chatUserRole.innerHTML = '<span class="online-dot"></span>' + user.role;

    loadMessages();
}

function loadMessages() {
    if (selectedUser === 0) {
        return;
    }

    fetch("chat.php?action=messages&user_id=" + selectedUser)
    .then(response => response.json())
    .then(messages => {
        messagesBox.innerHTML = "";

        messages.forEach(message => {
            const div = document.createElement("div");

            if (parseInt(message.sender_id) === 1) {
                div.className = "message sent";
            } else {
                div.className = "message received";
            }

            const date = new Date(message.sent_at.replace(" ", "T"));

            const time = date.toLocaleTimeString([], {
                hour: "2-digit",
                minute: "2-digit"
            });

            div.innerHTML = `
                <div>
                    <div class="message-bubble">${message.message_text}</div>
                    <span class="message-time">${time}</span>
                </div>
            `;

            messagesBox.appendChild(div);
        });

        messagesBox.scrollTop = messagesBox.scrollHeight;
    });
}

function sendMessage() {
    const message = messageInput.value.trim();

    if (selectedUser === 0) {
        alert("Please select a conversation first.");
        return;
    }

    if (message === "") {
        return;
    }

    const formData = new FormData();

    formData.append("receiver_id", selectedUser);
    formData.append("message", message);

    fetch("chat.php?action=send", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageInput.value = "";
            loadMessages();
            loadUsers();
        } else {
            alert(data.error);
        }
    });
}

sendBtn.addEventListener("click", sendMessage);

messageInput.addEventListener("keydown", function(event) {
    if (event.key === "Enter") {
        event.preventDefault();
        sendMessage();
    }
});

document.querySelectorAll(".chat-tabs button").forEach(button => {
    button.addEventListener("click", function() {
        document.querySelectorAll(".chat-tabs button").forEach(btn => {
            btn.classList.remove("active-tab");
        });

        this.classList.add("active-tab");

        loadUsers(this.dataset.role);
    });
});

loadUsers();