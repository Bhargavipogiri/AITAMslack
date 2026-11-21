
document.addEventListener("DOMContentLoaded", function () {

    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebar = document.getElementById("sidebar");

    // Sidebar collapse and expand
    sidebarToggle.addEventListener("click", function () {

        if (window.innerWidth <= 768) {

            document.body.classList.toggle("mobile-sidebar-open");

        } else {

            document.body.classList.toggle("sidebar-collapsed");

        }

    });

    // Close mobile sidebar when clicking outside
    document.addEventListener("click", function (event) {

        if (
            window.innerWidth <= 768 &&
            document.body.classList.contains("mobile-sidebar-open") &&
            !sidebar.contains(event.target) &&
            !sidebarToggle.contains(event.target)
        ) {

            document.body.classList.remove("mobile-sidebar-open");

        }

    });

    // Close mobile sidebar with Escape key
    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape") {

            document.body.classList.remove("mobile-sidebar-open");

        }

    });

    // Reset mobile state when resizing to desktop
    window.addEventListener("resize", function () {

        if (window.innerWidth > 768) {

            document.body.classList.remove("mobile-sidebar-open");

        }

    });

});
