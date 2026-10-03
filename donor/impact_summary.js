document.addEventListener("DOMContentLoaded", function () {

    /*
        Year-to-date button

        This button is currently informational.
        The year itself comes from PHP/database page logic.

        No hard-coded statistics are used here.
    */

    const dateButton = document.querySelector(".date");

    if (dateButton) {

        dateButton.addEventListener("click", function () {

            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });

        });

    }


    /*
        Prevent accidental form submission behavior
        if the page is later placed inside a form.
    */

    const buttons = document.querySelectorAll("button");

    buttons.forEach(function (button) {

        button.addEventListener("click", function (event) {

            if (button.type !== "submit") {

                event.preventDefault();

            }

        });

    });

});