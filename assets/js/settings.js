document.addEventListener("DOMContentLoaded", function () {

    const forms = document.querySelectorAll(".settings-card form");

    forms.forEach(function (form) {
        form.addEventListener("submit", function () {
            const button = form.querySelector('button[type="submit"]');

            if (button) {
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
            }
        });
    });

});