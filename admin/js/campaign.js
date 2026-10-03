const messageBox = document.querySelector('textarea[name="message"]');
const characterCount = document.querySelector('.character-count');

messageBox.addEventListener('input', function () {
    characterCount.textContent = this.value.length + " / 500";
});


const campaignForm = document.querySelector('.campaign-form form');

campaignForm.addEventListener('submit', function (event) {

    const clickedButton = document.activeElement;

    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;

    if (startDate && endDate && endDate < startDate) {
        alert("End date cannot be before the start date.");
        event.preventDefault();
        return;
    }

    if (clickedButton.name === "publish") {

        const confirmPublish = confirm(
            "Are you sure you want to publish this campaign?"
        );

        if (!confirmPublish) {
            event.preventDefault();
        }
    }

    if (clickedButton.name === "savedraft") {

        const confirmDraft = confirm(
            "Do you want to save this campaign as a draft?"
        );

        if (!confirmDraft) {
            event.preventDefault();
        }
    }
});