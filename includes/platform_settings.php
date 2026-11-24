
<?php

if (!isset($pdo)) {
    require_once __DIR__ . '/../admin/includes/db.php';
}

$platformSettings = [
    'platform_name' => 'AITAM SLACK',
    'platform_description' => 'Connecting the College Community...',
    'support_email' => 'csmdepartment987@gmail.com'
];

$stmt = $pdo->query(
    "SELECT setting_key, setting_value FROM platform_settings"
);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (array_key_exists($row['setting_key'], $platformSettings)) {
        $platformSettings[$row['setting_key']] = $row['setting_value'];
    }
}

?>
