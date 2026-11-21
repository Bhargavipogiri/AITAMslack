
document.addEventListener("DOMContentLoaded", function () {

    // View user details
    const viewButtons = document.querySelectorAll(".view-btn");

    viewButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            document.getElementById("modalName").textContent =
                button.dataset.name || "-";

            document.getElementById("modalEmail").textContent =
                button.dataset.email || "-";

            document.getElementById("modalRole").textContent =
                button.dataset.role || "-";

            document.getElementById("modalDepartment").textContent =
                button.dataset.department || "-";

            document.getElementById("modalPhone").textContent =
                button.dataset.phone || "-";

            const modal = new bootstrap.Modal(
                document.getElementById("userModal")
            );

            modal.show();
        });

    });

    // Confirm before changing account status
    const statusForms = document.querySelectorAll(".status-form");

    statusForms.forEach(function (form) {

        form.addEventListener("submit", function (event) {

            const confirmed = confirm(
                "Are you sure you want to change this user's account status?"
            );

            if (!confirmed) {
                event.preventDefault();
            }

        });

    });

});
