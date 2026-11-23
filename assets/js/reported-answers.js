
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("reportDetailsModal");

    if (!modal) return;

    modal.addEventListener("show.bs.modal", function (event) {
        const button = event.relatedTarget;

        if (!button) return;

        document.getElementById("detailReportId").textContent =
            "#" + button.dataset.id;

        document.getElementById("detailAnswerId").textContent =
            "#" + button.dataset.answer;

        document.getElementById("detailReporter").textContent =
            "#" + button.dataset.reporter;

        document.getElementById("detailReason").textContent =
            button.dataset.reason || "Not provided";

        document.getElementById("detailDescription").textContent =
            button.dataset.description || "No additional description.";

        document.getElementById("detailDate").textContent =
            button.dataset.date || "Not available";

        document.getElementById("detailReviewed").textContent =
            button.dataset.reviewed || "Not reviewed";

        document.getElementById("modalReportId").value =
            button.dataset.id;

        document.getElementById("reportStatus").value =
            button.dataset.status;

        document.getElementById("adminNote").value =
            button.dataset.note || "";
    });
});
