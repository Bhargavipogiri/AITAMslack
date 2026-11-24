
<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

require_once __DIR__ . '/includes/db.php';

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

$eventId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$eventId || $eventId <= 0) {
    header("Location: events.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header("Location: events.php?error=not_found");
    exit;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$errors = [];

$categories = [
    "Technical",
    "Cultural",
    "Sports",
    "Workshop",
    "Other"
];

$old = [
    "event_title" => $event["event_title"],
    "event_description" => $event["event_description"],
    "category" => $event["category"],
    "venue" => $event["venue"],
    "start_datetime" => date("Y-m-d H:i", strtotime($event["start_datetime"])),
    "end_datetime" => date("Y-m-d H:i", strtotime($event["end_datetime"])),
    "is_featured" => (int)$event["is_featured"]
];

$currentImage = $event["event_image"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        die("Invalid request.");
    }

    foreach ($old as $key => $value) {
        if ($key !== "is_featured") {
            $old[$key] = trim($_POST[$key] ?? "");
        }
    }

    $old["is_featured"] = isset($_POST["is_featured"]) ? 1 : 0;

    // Required field validation
    $requiredFields = [
        "event_title" => "Event title",
        "event_description" => "Event description",
        "category" => "Category",
        "venue" => "Venue",
        "start_datetime" => "Start date and time",
        "end_datetime" => "End date and time"
    ];

    foreach ($requiredFields as $key => $label) {
        if ($old[$key] === "") {
            $errors[] = $label . " is required.";
        }
    }

    // Category validation
    if (
        $old["category"] !== "" &&
        !in_array($old["category"], $categories, true)
    ) {
        $errors[] = "Invalid event category.";
    }

    // Date validation
    $startDate = false;
    $endDate = false;

    if ($old["start_datetime"] !== "") {
        $startDate = DateTime::createFromFormat(
            "Y-m-d H:i",
            $old["start_datetime"]
        );

        $dateErrors = DateTime::getLastErrors();

        if (
            !$startDate ||
            ($dateErrors !== false &&
                ($dateErrors["warning_count"] > 0 ||
                 $dateErrors["error_count"] > 0))
        ) {
            $errors[] = "Please enter a valid start date and time.";
            $startDate = false;
        }
    }

    if ($old["end_datetime"] !== "") {
        $endDate = DateTime::createFromFormat(
            "Y-m-d H:i",
            $old["end_datetime"]
        );

        $dateErrors = DateTime::getLastErrors();

        if (
            !$endDate ||
            ($dateErrors !== false &&
                ($dateErrors["warning_count"] > 0 ||
                 $dateErrors["error_count"] > 0))
        ) {
            $errors[] = "Please enter a valid end date and time.";
            $endDate = false;
        }
    }

    if ($startDate && $endDate && $endDate <= $startDate) {
        $errors[] = "End date and time must be later than start date and time.";
    }

    // Image validation
    $imageName = null;

    if (
        isset($_FILES["event_image"]) &&
        $_FILES["event_image"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES["event_image"]["error"] !== UPLOAD_ERR_OK) {
            $errors[] = "Image upload failed.";
        } elseif ($_FILES["event_image"]["size"] > 5 * 1024 * 1024) {
            $errors[] = "Image size must be less than 5 MB.";
        } else {

            $allowedTypes = [
                "image/jpeg" => "jpg",
                "image/png" => "png",
                "image/webp" => "webp"
            ];

            $imageInfo = getimagesize($_FILES["event_image"]["tmp_name"]);

            if ($imageInfo === false) {
                $errors[] = "Please upload a valid image.";
            } else {

                $mimeType = $imageInfo["mime"] ?? "";

                if (!isset($allowedTypes[$mimeType])) {
                    $errors[] = "Only JPG, PNG and WEBP images are allowed.";
                } else {
                    $imageName = bin2hex(random_bytes(16))
                        . "." . $allowedTypes[$mimeType];
                }
            }
        }
    }

    $uploadDirectory = __DIR__ . '/../assets/uploads/events/';

    if (
        empty($errors) &&
        $imageName !== null &&
        (!is_dir($uploadDirectory) || !is_writable($uploadDirectory))
    ) {
        $errors[] = "Event image upload folder is not writable.";
    }

    // Update event
    if (empty($errors)) {

        $newImagePath = $currentImage;
        $oldImagePath = $currentImage;
        $uploadedFile = false;

        try {

            // Upload replacement image if selected
            if ($imageName !== null) {

                if (!move_uploaded_file(
                    $_FILES["event_image"]["tmp_name"],
                    $uploadDirectory . $imageName
                )) {
                    throw new RuntimeException("Unable to upload event image.");
                }

                $uploadedFile = true;
                $newImagePath = "assets/uploads/events/" . $imageName;
            }

            $pdo->beginTransaction();

            // Only one featured event
            if ($old["is_featured"] === 1) {

                $stmt = $pdo->prepare("
                    UPDATE events
                    SET is_featured = 0
                    WHERE is_featured = 1
                    AND event_id != ?
                ");

                $stmt->execute([$eventId]);
            }

            $stmt = $pdo->prepare("
                UPDATE events SET
                    event_title = ?,
                    event_description = ?,
                    category = ?,
                    event_image = ?,
                    venue = ?,
                    start_datetime = ?,
                    end_datetime = ?,
                    is_featured = ?
                WHERE event_id = ?
            ");

            $stmt->execute([
                $old["event_title"],
                $old["event_description"],
                $old["category"],
                $newImagePath,
                $old["venue"],
                $startDate->format("Y-m-d H:i:s"),
                $endDate->format("Y-m-d H:i:s"),
                $old["is_featured"],
                $eventId
            ]);

            $pdo->commit();

            // Delete previous image after successful update
            if (
                $uploadedFile &&
                !empty($oldImagePath) &&
                strpos($oldImagePath, "assets/uploads/events/") === 0
            ) {
                $oldFile = __DIR__ . '/../' . $oldImagePath;

                if (is_file($oldFile)) {
                    unlink($oldFile);
                }
            }

            header("Location: events.php?success=updated");
            exit;

        } catch (Throwable $error) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Remove newly uploaded image if update failed
            if (
                $uploadedFile &&
                is_file($uploadDirectory . $imageName)
            ) {
                unlink($uploadDirectory . $imageName);
            }

            $errors[] = "Unable to update event. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Event | AITAM Slack</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"
    >

    <link rel="stylesheet" href="../assets/css/root.css">
    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>

        .event-form-panel {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            max-width: 900px;
        }

        .event-form-panel label {
            font-weight: 600;
            margin-bottom: 7px;
        }

        .event-form-panel .form-control,
        .event-form-panel .form-select {
            padding: 10px 12px;
        }

        .event-form-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .event-form-actions button {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
        }

        .event-form-actions a {
            padding: 10px 20px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
        }

        #imagePreview {
            display: none;
            max-width: 220px;
            max-height: 180px;
            margin-top: 12px;
            border-radius: 8px;
            object-fit: cover;
        }

        .current-image {
            max-width: 220px;
            max-height: 180px;
            margin-top: 10px;
            border-radius: 8px;
            object-fit: cover;
            display: block;
        }

        .flatpickr-input {
            background-color: #fff !important;
        }

    </style>

</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="main-content">

    <?php include "includes/topbar.php"; ?>

    <main class="page-content">

        <div class="page-heading">
            <h2>Edit Event</h2>
            <p>Update the event details</p>
        </div>

        <section class="event-form-panel">

            <?php if (!empty($errors)): ?>

                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION["csrf_token"]) ?>"
                >

                <div class="mb-3">
                    <label>Event Title *</label>
                    <input
                        type="text"
                        name="event_title"
                        class="form-control"
                        value="<?= e($old["event_title"]) ?>"
                        maxlength="255"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label>Event Description *</label>
                    <textarea
                        name="event_description"
                        class="form-control"
                        rows="5"
                        required
                    ><?= e($old["event_description"]) ?></textarea>
                </div>

                <div class="mb-3">
                    <label>Event Category *</label>
                    <select name="category" class="form-select" required>

                        <option value="">Select Category</option>

                        <?php foreach ($categories as $item): ?>
                            <option
                                value="<?= e($item) ?>"
                                <?= $old["category"] === $item ? "selected" : "" ?>
                            >
                                <?= e($item) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="mb-3">

                    <label>Current Event Poster</label>

                    <?php if (!empty($currentImage)): ?>

                        <img
                            src="../<?= e($currentImage) ?>"
                            alt="Current event poster"
                            class="current-image"
                        >

                    <?php else: ?>

                        <p class="text-muted">No image available.</p>

                    <?php endif; ?>

                </div>

                <div class="mb-3">

                    <label>Replace Event Poster</label>

                    <input
                        type="file"
                        name="event_image"
                        id="event_image"
                        class="form-control"
                        accept="image/jpeg,image/png,image/webp"
                    >

                    <small class="text-muted">
                        Optional. Leave empty to keep the current image.
                        JPG, PNG or WEBP. Maximum size: 5 MB.
                    </small>

                    <img id="imagePreview" alt="New event image preview">

                </div>

                <div class="mb-3">
                    <label>Venue *</label>
                    <input
                        type="text"
                        name="venue"
                        class="form-control"
                        value="<?= e($old["venue"]) ?>"
                        maxlength="255"
                        required
                    >
                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label>Start Date and Time *</label>

                        <input
                            type="text"
                            name="start_datetime"
                            class="form-control datetime-picker"
                            placeholder="Select start date and time"
                            value="<?= e($old["start_datetime"]) ?>"
                            required
                            readonly
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label>End Date and Time *</label>

                        <input
                            type="text"
                            name="end_datetime"
                            class="form-control datetime-picker"
                            placeholder="Select end date and time"
                            value="<?= e($old["end_datetime"]) ?>"
                            required
                            readonly
                        >

                    </div>

                </div>

                <div class="form-check mb-3">

                    <input
                        type="checkbox"
                        name="is_featured"
                        value="1"
                        class="form-check-input"
                        id="isFeatured"
                        <?= $old["is_featured"] ? "checked" : "" ?>
                    >

                    <label class="form-check-label" for="isFeatured">
                        Mark as Featured Event
                    </label>

                </div>

                <div class="event-form-actions">

                    <button type="submit">
                        <i class="bi bi-save"></i>
                        Update Event
                    </button>

                    <a href="events.php">Cancel</a>

                </div>

            </form>

        </section>

    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script src="../assets/js/admin.js"></script>

<script>

flatpickr(".datetime-picker", {

    enableTime: true,
    dateFormat: "Y-m-d H:i",
    altInput: true,
    altFormat: "d M Y, h:i K",
    time_24hr: false,
    minuteIncrement: 5,
    allowInput: false

});

const imageInput = document.getElementById("event_image");
const imagePreview = document.getElementById("imagePreview");

imageInput.addEventListener("change", function () {

    const file = this.files[0];

    if (file) {

        imagePreview.src = URL.createObjectURL(file);
        imagePreview.style.display = "block";

    } else {

        imagePreview.removeAttribute("src");
        imagePreview.style.display = "none";

    }

});

</script>

</body>
</html>
