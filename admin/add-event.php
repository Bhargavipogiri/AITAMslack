
<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

require_once __DIR__ . '/../config/db.php';
function e($value)
{
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$errors = [];

$old = [
    "event_title" => "",
    "event_description" => "",
    "category" => "",
    "venue" => "",
    "start_datetime" => "",
    "end_datetime" => "",
    "is_featured" => 0
];

$categories = [
    "Technical",
    "Cultural",
    "Sports",
    "Workshop",
    "Other"
];

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
        !isset($_FILES["event_image"]) ||
        $_FILES["event_image"]["error"] === UPLOAD_ERR_NO_FILE
    ) {
        $errors[] = "Please upload an event image.";
    } elseif ($_FILES["event_image"]["error"] !== UPLOAD_ERR_OK) {
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

    // Upload folder validation
    $uploadDirectory = __DIR__ . '/../assets/uploads/events/';

    if (
        empty($errors) &&
        (!is_dir($uploadDirectory) || !is_writable($uploadDirectory))
    ) {
        $errors[] = "Event image upload folder is not writable.";
    }

    // Save event
    if (empty($errors)) {

        $imagePath = null;

        try {

            // Upload event image
            if ($imageName !== null) {

                if (!move_uploaded_file(
                    $_FILES["event_image"]["tmp_name"],
                    $uploadDirectory . $imageName
                )) {
                    throw new RuntimeException("Unable to save event image.");
                }

                $imagePath = "assets/uploads/events/" . $imageName;
            }

            $pdo->beginTransaction();

            // Allow only one featured event
            if ($old["is_featured"] === 1) {
                $pdo->exec("
                    UPDATE events
                    SET is_featured = 0
                    WHERE is_featured = 1
                ");
            }

            $stmt = $pdo->prepare("
                INSERT INTO events (
                    event_title,
                    event_description,
                    category,
                    event_image,
                    venue,
                    start_datetime,
                    end_datetime,
                    is_featured
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $old["event_title"],
                $old["event_description"],
                $old["category"],
                $imagePath,
                $old["venue"],
                $startDate->format("Y-m-d H:i:s"),
                $endDate->format("Y-m-d H:i:s"),
                $old["is_featured"]
            ]);

            $pdo->commit();

            header("Location: events.php?success=added");
            exit;

        } catch (Throwable $error) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log("Add Event Error: " . $error->getMessage());

            // Remove uploaded image if saving failed
            if (
                $imageName !== null &&
                is_file($uploadDirectory . $imageName)
            ) {
                unlink($uploadDirectory . $imageName);
            }

            $errors[] = "Unable to add event. Please try again.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Event | AITAM Slack</title>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <!-- Flatpickr CSS -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"
    >

    <!-- Project CSS -->
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

        <!-- Page Heading -->
        <div class="page-heading">
            <h2>Add New Event</h2>
            <p>Create and publish a college event</p>
        </div>

        <!-- Event Form -->
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

                <!-- Event Title -->
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

                <!-- Event Description -->
                <div class="mb-3">

                    <label>Event Description *</label>

                    <textarea
                        name="event_description"
                        class="form-control"
                        rows="5"
                        required
                    ><?= e($old["event_description"]) ?></textarea>

                </div>

                <!-- Event Category -->
                <div class="mb-3">

                    <label>Event Category *</label>

                    <select
                        name="category"
                        class="form-select"
                        required
                    >

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

                <!-- Event Image -->
                <div class="mb-3">

                    <label>Event Poster</label>

                    <input
                        type="file"
                        name="event_image"
                        id="event_image"
                        class="form-control"
                        accept="image/jpeg,image/png,image/webp"
                        required
                    >

                    <small class="text-muted">
                        JPG, PNG or WEBP. Maximum size: 5 MB.
                    </small>

                    <img
                        id="imagePreview"
                        alt="Event image preview"
                    >

                </div>

                <!-- Venue -->
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

                <!-- Start and End Date -->
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

                <!-- Featured Event -->
                <div class="form-check mb-3">

                    <input
                        type="checkbox"
                        name="is_featured"
                        value="1"
                        class="form-check-input"
                        id="isFeatured"
                        <?= $old["is_featured"] ? "checked" : "" ?>
                    >

                    <label
                        class="form-check-label"
                        for="isFeatured"
                    >
                        Mark as Featured Event
                    </label>

                </div>

                <!-- Form Buttons -->
                <div class="event-form-actions">

                    <button type="submit">

                        <i class="bi bi-plus-circle"></i>
                        Add Event

                    </button>

                    <a href="events.php">Cancel</a>

                </div>

            </form>

        </section>

    </main>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- Project JS -->
<script src="../assets/js/admin.js"></script>

<script>

// Date and time picker
flatpickr(".datetime-picker", {

    enableTime: true,

    dateFormat: "Y-m-d H:i",

    altInput: true,

    altFormat: "d M Y, h:i K",

    time_24hr: false,

    minuteIncrement: 5,

    allowInput: false

});

// Event image preview
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
