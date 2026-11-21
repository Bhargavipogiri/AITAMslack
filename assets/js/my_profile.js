
document.addEventListener("DOMContentLoaded", function () {

    // PROFILE ELEMENTS
    const profileForm = document.getElementById("profileForm");

    const editBtn = document.getElementById("editProfileBtn");

    const cancelBtn = document.getElementById("cancelProfileBtn");

    const profileActions = document.getElementById("profileActions");

    const profileMessage = document.getElementById("profileMessage");

    const profilePicture = document.getElementById("profilePicture");

    const profilePreview = document.getElementById("profilePreview");

    // FORM INPUTS
    const editableFields = [
        "fullName",
        "email",
        "department",
        "phone"
    ];

    let originalValues = {};

    // SAVE ORIGINAL VALUES
    function saveOriginalValues() {

        originalValues = {};

        editableFields.forEach(function (id) {

            const field = document.getElementById(id);

            originalValues[id] = field.value;

        });

    }

    saveOriginalValues();

    // ENABLE EDIT MODE
    function enableEditMode() {

        editableFields.forEach(function (id) {

            document.getElementById(id).disabled = false;

        });

        profileActions.classList.add("show");

        editBtn.disabled = true;

        editBtn.innerHTML =
            '<i class="bi bi-pencil-square"></i> Editing';

        document.getElementById("fullName").focus();

    }

    // DISABLE EDIT MODE
    function disableEditMode() {

        editableFields.forEach(function (id) {

            document.getElementById(id).disabled = true;

        });

        profileActions.classList.remove("show");

        editBtn.disabled = false;

        editBtn.innerHTML =
            '<i class="bi bi-pencil-square"></i> Edit Profile';

    }

    // SHOW MESSAGE
    function showMessage(message, type) {

        profileMessage.textContent = message;

        profileMessage.className =
            "profile-message show " + type;

        window.setTimeout(function () {

            profileMessage.className = "profile-message";

        }, 4000);

    }

    // EDIT BUTTON
    editBtn.addEventListener("click", function () {

        enableEditMode();

    });

    // CANCEL BUTTON
    cancelBtn.addEventListener("click", function () {

        editableFields.forEach(function (id) {

            document.getElementById(id).value =
                originalValues[id];

        });

        disableEditMode();

        showMessage("Changes cancelled.", "success");

    });

    // SAVE PROFILE
    profileForm.addEventListener("submit", function (event) {

        event.preventDefault();

        if (!profileForm.checkValidity()) {

            profileForm.reportValidity();

            return;

        }

        const name = document.getElementById("fullName").value.trim();

        const email = document.getElementById("email").value.trim();

        if (!name || !email) {

            showMessage(
                "Please enter your name and email.",
                "error"
            );

            return;

        }

        // Update profile overview on this page
        document.getElementById("profileName").textContent = name;

        document.getElementById("profileEmail").textContent = email;

        saveOriginalValues();

        disableEditMode();

        showMessage(
            "Profile preview updated. Database saving will be enabled after login is developed.",
            "success"
        );

    });

    // PROFILE PICTURE PREVIEW
    profilePicture.addEventListener("change", function () {

        const file = this.files[0];

        if (!file) return;

        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        if (!allowedTypes.includes(file.type)) {

            showMessage(
                "Please select a JPG, PNG or WEBP image.",
                "error"
            );

            this.value = "";

            return;

        }

        if (file.size > 2 * 1024 * 1024) {

            showMessage(
                "Image size must be less than 2 MB.",
                "error"
            );

            this.value = "";

            return;

        }

        const reader = new FileReader();

        reader.onload = function (event) {

            profilePreview.src = event.target.result;

            showMessage(
                "Profile picture preview updated.",
                "success"
            );

        };

        reader.readAsDataURL(file);

    });

    // SHOW / HIDE PASSWORD
    const passwordToggles = document.querySelectorAll(
        ".password-toggle"
    );

    passwordToggles.forEach(function (button) {

        button.addEventListener("click", function () {

            const targetId = this.dataset.target;

            const input = document.getElementById(targetId);

            if (!input || input.disabled) return;

            if (input.type === "password") {

                input.type = "text";

                this.innerHTML = '<i class="bi bi-eye-slash"></i>';

            } else {

                input.type = "password";

                this.innerHTML = '<i class="bi bi-eye"></i>';

            }

        });

    });

});
