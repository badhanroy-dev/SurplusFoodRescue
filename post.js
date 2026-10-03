document.addEventListener("DOMContentLoaded", function () {

    const browseButton = document.getElementById("browseButton");

    const foodImage = document.getElementById("foodImage");

    const fileName = document.getElementById("fileName");


    if (browseButton && foodImage) {

        browseButton.addEventListener("click", function () {

            foodImage.click();

        });

    }


    if (foodImage && fileName) {

        foodImage.addEventListener("change", function () {

            if (foodImage.files.length > 0) {

                fileName.textContent =
                    foodImage.files[0].name;

            } else {

                fileName.textContent = "";

            }

        });

    }

});