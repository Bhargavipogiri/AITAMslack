
<?php

$pageTitles = [
    'dashboard.php' => 'ADMIN-PANEL',
    'users.php' => 'User Management',
    'questions.php' => 'Question Management',
    'answers.php' => 'Answer Management',
    'reported-questions.php' => 'Reported Questions',
    'reported-answers.php' => 'Reported Answers',
    'reports.php' => 'Reports Management',
    'announcements.php' => 'Announcements Management',
    'settings.php' => 'Settings',
    'my_profile.php' => 'My Profile',
    'events.php' => 'Event Management'
];

$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitle = $pageTitles[$currentPage] ?? 'Admin Panel';

$adminName = $_SESSION['admin_name'] ?? 'Administrator';
$adminImage = $_SESSION['admin_profile_image'] ?? '';

?>

<style>
.notification-menu {
    width: 360px;
    max-width: 90vw;
    padding: 0;
    border: 1px solid var(--border-color, #e0e5ed);
    border-radius: 12px;
    overflow: hidden;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid var(--border-color, #e0e5ed);
}

.notification-header strong {
    font-size: 15px;
}

.notification-count {
    background: #e53935;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    min-width: 19px;
    height: 19px;
    padding: 0 5px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-left: 5px;
}

.notification-list {
    max-height: 350px;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 13px;
    border-bottom: 1px solid var(--border-color, #e0e5ed);
    cursor: pointer;
    transition: background 0.2s;
}

.notification-item:hover {
    background: var(--surface-secondary, #f5f7fb);
}

.notification-item.unread {
    background: rgba(49, 95, 158, 0.06);
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-content strong {
    display: block;
    font-size: 13px;
    margin-bottom: 5px;
    overflow-wrap: anywhere;
}

.notification-content p {
    font-size: 12px;
    margin: 0 0 5px;
    color: var(--text-secondary, #77849a);
}

.notification-time {
    font-size: 11px;
    color: var(--text-secondary, #77849a);
}

.notification-delete {
    border: none;
    background: transparent;
    color: #dc3545;
    padding: 5px;
    cursor: pointer;
    border-radius: 5px;
    flex-shrink: 0;
}

.notification-delete:hover {
    background: rgba(220, 53, 69, 0.12);
}

.notification-empty {
    padding: 30px 15px;
    text-align: center;
    color: var(--text-secondary, #77849a);
}

.notification-empty i {
    font-size: 25px;
    display: block;
    margin-bottom: 8px;
}

.notification-empty p {
    margin: 0;
    font-size: 13px;
}

.notification-footer {
    padding: 11px;
    text-align: center;
    border-top: 1px solid var(--border-color, #e0e5ed);
}

.notification-footer a {
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
}
</style>

<header class="topbar">

    <div class="topbar-left">

        <button class="sidebar-toggle"
                id="sidebarToggle"
                type="button"
                aria-label="Toggle sidebar"
                aria-controls="sidebar"
                aria-expanded="true">

            <i class="bi bi-list"></i>

        </button>

        <div class="page-heading">

            <h4>ADITYA INSTITUTE OF TECHNOLOGY AND MANAGMENT</h4>

            <p class="topbar-description">
                Connecting the College Community...
            </p>

        </div>

    </div>

    <div class="topbar-right">

        <!-- NOTIFICATION DROPDOWN -->
        <div class="dropdown">

            <button class="topbar-icon notification-btn"
                    id="notificationButton"
                    type="button"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                    aria-expanded="false"
                    aria-label="Notifications">

                <i class="bi bi-bell"></i>

                <span class="notification-count"
                      id="notificationCount"
                      style="display:none;">0</span>

            </button>

            <div class="dropdown-menu dropdown-menu-end notification-menu">

                <div class="notification-header">
                    <strong>Notifications</strong>
                    <span style="font-size:12px;color:var(--text-secondary,#77849a);">
                        Recent updates
                    </span>
                </div>

                <div class="notification-list" id="notificationList">

                    <div class="notification-empty">
                        <i class="bi bi-arrow-repeat"></i>
                        <p>Loading notifications...</p>
                    </div>

                </div>

                

            </div>

        </div>

        <!-- ADMIN PROFILE -->
        <div class="dropdown">

            <button class="admin-profile-btn"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false">

                <div class="admin-avatar">

                    <?php if (!empty($adminImage)): ?>

                        <img
                            src="<?= htmlspecialchars($adminImage, ENT_QUOTES, 'UTF-8') ?>"
                            alt="Admin Profile"
                        >

                    <?php else: ?>

                        <i class="bi bi-person"></i>

                    <?php endif; ?>

                </div>

                <div class="admin-info">

                    <strong>
                        <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?>
                    </strong>

                    <span>Administrator</span>

                </div>

                <i class="bi bi-chevron-down dropdown-arrow"></i>

            </button>

            <ul class="dropdown-menu dropdown-menu-end profile-menu">

                <li>
                    <a class="dropdown-item" href="my_profile.php">
                        <i class="bi bi-person-circle"></i>
                        My Profile
                    </a>
                </li>

                <li>
                    <a class="dropdown-item" href="settings.php">
                        <i class="bi bi-gear"></i>
                        Settings
                    </a>
                </li>

                <li><hr class="dropdown-divider"></li>

                <li>
                    <a class="dropdown-item logout-dropdown"
                       href="../auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        Logout
                    </a>
                </li>

            </ul>

        </div>

    </div>

</header>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const notificationCount = document.getElementById('notificationCount');
    const notificationList = document.getElementById('notificationList');

    const endpoint = 'notifications.php';

    let csrfToken = '';

    function escapeHTML(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char];
        });
    }

    function formatDate(value) {
        const date = new Date(String(value).replace(' ', 'T'));

        if (isNaN(date.getTime())) {
            return escapeHTML(value);
        }

        return date.toLocaleString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function updateCount(count) {
        const total = Number(count) || 0;

        notificationCount.textContent = total > 99 ? '99+' : total;
        notificationCount.style.display = total > 0 ? 'inline-flex' : 'none';
    }

    function renderNotifications(notifications) {

        if (!notifications || notifications.length === 0) {

            notificationList.innerHTML = `
                <div class="notification-empty">
                    <i class="bi bi-bell-slash"></i>
                    <p>No notifications available</p>
                </div>
            `;

            return;
        }

        notificationList.innerHTML = notifications.map(function (item) {

            const unreadClass = Number(item.is_read) === 0 ? 'unread' : '';

            return `
                <div class="notification-item ${unreadClass}"
                     data-id="${Number(item.notification_id)}">

                    <div class="notification-content">

                        <strong>${escapeHTML(item.subject)}</strong>

                        <p>
                            New report submitted by
                            ${escapeHTML(item.reporter_name)}
                        </p>

                        <p>
                            Priority: ${escapeHTML(item.priority)}
                            &nbsp; | &nbsp;
                            Status: ${escapeHTML(item.status)}
                        </p>

                        <span class="notification-time">
                            ${formatDate(item.notification_time)}
                        </span>

                    </div>

                    <button type="button"
                            class="notification-delete"
                            data-delete-id="${Number(item.notification_id)}"
                            aria-label="Delete notification"
                            title="Delete notification">

                        <i class="bi bi-trash"></i>

                    </button>

                </div>
            `;

        }).join('');
    }

    // FETCH NOTIFICATIONS
    async function loadNotifications() {

        try {

            const response = await fetch(endpoint, {
                method: 'GET',
                cache: 'no-store'
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Unable to load notifications.');
            }

            csrfToken = data.csrf_token;

            updateCount(data.unread_count);
            renderNotifications(data.notifications);

        } catch (error) {

            console.error('Notification loading error:', error);

            notificationList.innerHTML = `
                <div class="notification-empty">
                    <i class="bi bi-exclamation-circle"></i>
                    <p>Unable to load notifications</p>
                </div>
            `;
        }
    }

    // MARK AS READ OR DELETE
    async function notificationAction(action, notificationId) {

        const formData = new FormData();

        formData.append('action', action);
        formData.append('notification_id', notificationId);
        formData.append('csrf_token', csrfToken);

        try {

            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Notification action failed.');
            }

            updateCount(data.unread_count);

            await loadNotifications();

        } catch (error) {

            console.error('Notification action error:', error);

            alert(error.message || 'Unable to update notification.');

        }
    }

    // CLICK EVENTS
    notificationList.addEventListener('click', function (event) {

        const deleteButton = event.target.closest('[data-delete-id]');

        if (deleteButton) {

            event.stopPropagation();

            const id = deleteButton.dataset.deleteId;

            notificationAction('delete', id);

            return;
        }

        const notificationItem = event.target.closest('.notification-item');

        if (notificationItem) {

            const id = notificationItem.dataset.id;

            if (notificationItem.classList.contains('unread')) {
                notificationAction('mark_read', id);
            }
        }

    });

    // INITIAL LOAD
    loadNotifications();

    // CHECK FOR NEW NOTIFICATIONS EVERY 15 SECONDS
    setInterval(loadNotifications, 15000);

});
</script>
