
document.addEventListener("DOMContentLoaded", function () {

    // View question details
    const viewButtons = document.querySelectorAll(".view-question");

    viewButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            document.getElementById("modalTitle").textContent =
                button.dataset.title || "-";

            document.getElementById("modalDescription").textContent =
                button.dataset.description || "-";

            document.getElementById("modalAuthor").textContent =
                button.dataset.author || "-";

            document.getElementById("modalCategory").textContent =
                button.dataset.category || "-";

            document.getElementById("modalStatus").textContent =
                button.dataset.status || "-";

            const modal = new bootstrap.Modal(
                document.getElementById("questionModal")
            );

            modal.show();

        });

    });

    // Confirm before hiding or publishing
    const actionForms = document.querySelectorAll(".question-action-form");

    actionForms.forEach(function (form) {

        form.addEventListener("submit", function (event) {

            const action = form.querySelector(
                'input[name="action"]'
            ).value;

            const message = action === "hide"
                ? "Are you sure you want to hide this question?"
                : "Do you want to publish this question?";

            if (!confirm(message)) {
                event.preventDefault();
            }

        });

    });

});
