const messageBox = document.querySelector('textarea[name="message"]');
const characterCount = document.querySelector('.character-count');

messageBox.addEventListener('input', function () {
    characterCount.textContent = this.value.length + " / 500";
});


const broadcastForm = document.querySelector('.broadcast-form');

broadcastForm.addEventListener('submit', function (event) {

    const clickedButton = document.activeElement;

    if (clickedButton.name === "sendnow") {
        const confirmSend = confirm(
            "Are you sure you want to send this broadcast?"
        );

        if (!confirmSend) {
            event.preventDefault();
        }
    }

    if (clickedButton.name === "savedraft") {
        const confirmDraft = confirm(
            "Do you want to save this broadcast as a draft?"
        );

        if (!confirmDraft) {
            event.preventDefault();
        }
    }
});