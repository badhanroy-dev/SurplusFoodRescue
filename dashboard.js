document.addEventListener("DOMContentLoaded", function () {

    const searchInput = document.querySelector(
        ".search input"
    );


    if (searchInput) {

        searchInput.addEventListener(
            "keydown",
            function (event) {

                if (event.key === "Escape") {

                    searchInput.value = "";

                }

            }
        );

    }

});