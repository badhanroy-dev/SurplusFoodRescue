document.addEventListener("DOMContentLoaded", function () {

    /*
        Export PDF button

        The actual PDF generation is handled by the browser's
        print function. JavaScript only opens the print dialog.
    */

    const exportButton = document.querySelector(".export");

    if (exportButton) {

        exportButton.addEventListener("click", function (event) {

            event.preventDefault();

            window.print();

        });

    }


    /*
        Prevent accidental form/button behavior if more
        JavaScript functionality is added later.
    */

});