
document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("announcementModal");
    const addButton = document.getElementById("addAnnouncementBtn");

    const modalTitle = document.getElementById("announcementModalTitle");
    const formAction = document.getElementById("formAction");
    const announcementId = document.getElementById("announcementId");
    const title = document.getElementById("announcementTitle");
    const description = document.getElementById("announcementDescription");
    const status = document.getElementById("announcementStatus");
    const saveButton = document.getElementById("saveAnnouncementBtn");

    addButton.addEventListener("click", function () {
        modalTitle.textContent = "Create Announcement";
        formAction.value = "create";
        announcementId.value = "";
        title.value = "";
        description.value = "";
        status.value = "draft";
        saveButton.textContent = "Save Announcement";
    });

    modal.addEventListener("show.bs.modal", function (event) {

        const button = event.relatedTarget;

        if (!button || !button.classList.contains("edit-btn")) {
            return;
        }

        modalTitle.textContent = "Edit Announcement";
        formAction.value = "update";

        announcementId.value = button.dataset.id;
        title.value = button.dataset.title;
        description.value = button.dataset.description;
        status.value = button.dataset.status;

        saveButton.textContent = "Update Announcement";
    });

});
