
document.addEventListener("DOMContentLoaded", function () {

    // View user details
    const viewButtons = document.querySelectorAll(".view-btn");
    const userModal = document.getElementById("userModal");

    viewButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            const fields = {
                modalName: button.dataset.name,
                modalEmail: button.dataset.email,
                modalRole: button.dataset.role,
                modalDepartment: button.dataset.department,
                modalPhone: button.dataset.phone
            };

            Object.entries(fields).forEach(function ([id, value]) {
                const element = document.getElementById(id);
                if (element) {
                    element.textContent = value || "-";
                }
            });

            if (userModal && window.bootstrap && window.bootstrap.Modal) {
                const modal = window.bootstrap.Modal.getOrCreateInstance(userModal);
                modal.show();
            }
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
