
document.addEventListener("DOMContentLoaded", function () {

    const statusForms = document.querySelectorAll(".status-form");

    statusForms.forEach(function (form) {

        form.addEventListener("submit", function (event) {

            const selectedStatus = form.querySelector(
                'select[name="status"]'
            );

            if (
                selectedStatus &&
                selectedStatus.value === "Hidden"
            ) {

                const confirmHide = confirm(
                    "Are you sure you want to hide this answer?"
                );

                if (!confirmHide) {
                    event.preventDefault();
                }

            }

        });

    });

});
