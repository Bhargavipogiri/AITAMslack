<?php
$title = isset($pageTitle) && is_string($pageTitle)
    ? $pageTitle
    : 'AITAM Slack';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> | AITAM Slack</title>
    <link rel="stylesheet" href="assets/css/root.css">
</head>
<body>
<?php require __DIR__ . '/../nav.php'; ?>
