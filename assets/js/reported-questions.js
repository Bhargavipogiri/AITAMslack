
document.addEventListener("DOMContentLoaded", function () {

    const reportModal = document.getElementById("reportModal");

    if (!reportModal) {
        return;
    }

    reportModal.addEventListener("show.bs.modal", function (event) {

        const button = event.relatedTarget;

        if (!button) {
            return;
        }

        const fields = {
            modalReportId: "reportId",
            modalQuestionId: "questionId",
            modalQuestionTitle: "questionTitle",
            modalQuestionDescription: "questionDescription",
            modalCategory: "category",
            modalAuthor: "author",
            modalAuthorEmail: "authorEmail",
            modalReporter: "reporter",
            modalReporterEmail: "reporterEmail",
            modalReason: "reason",
            modalReportDescription: "description",
            modalStatus: "status",
            modalAdminNote: "adminNote",
            modalReportedAt: "reportedAt"
        };

        Object.entries(fields).forEach(function ([elementId, dataName]) {

            const element = document.getElementById(elementId);

            if (element) {
                element.textContent = button.dataset[dataName] || "Not provided";
            }

        });

    });

});
