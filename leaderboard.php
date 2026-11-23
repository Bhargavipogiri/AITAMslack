
<?php

require_once 'lesder-db.php';

/* Fetch students based on total points */
$sql = "SELECT id, name, department, year,
               profile_image, total_points
        FROM students
        ORDER BY total_points DESC, name ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Get top three students */
$topThree = array_slice($students, 0, 3);

/* Display order: Second, First, Third */
$podium = [];

if (isset($topThree[1])) {
    $podium[] = [
        'student' => $topThree[1],
        'rank' => 2,
        'class' => 'second-place',
        'label' => 'RUNNER UP',
        'medal' => '🥈'
    ];
}

if (isset($topThree[0])) {
    $podium[] = [
        'student' => $topThree[0],
        'rank' => 1,
        'class' => 'first-place',
        'label' => 'CHAMPION',
        'medal' => '🥇'
    ];
}

if (isset($topThree[2])) {
    $podium[] = [
        'student' => $topThree[2],
        'rank' => 3,
        'class' => 'third-place',
        'label' => 'THIRD PLACE',
        'medal' => '🥉'
    ];
}

/* Generate student initials */
function getInitials($name) {
    $words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);
    $initials = '';

    foreach (array_slice($words, 0, 2) as $word) {
        $initials .= mb_substr($word, 0, 1);
    }

    return strtoupper($initials);
}

/* Format points */
function formatPoints($points) {
    return number_format((float)$points, 2);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AITAM Slack - Leaderboard</title>

    <!-- Root CSS -->
    <link rel="stylesheet" href="./assets/css/root.css">

    <style>

    /* ==============================
       GLOBAL
    ============================== */

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        font-family: var(--font-family-base);
        background: var(--color-background);
        color: var(--text-primary);
        transition: var(--transition-fast);
    }

    .leaderboard-page {
        width: 100%;
        max-width: 1500px;
        margin: 0 auto;
        padding: 35px 25px 45px;
    }

    /* ==============================
       PAGE HEADER
    ============================== */

    .leaderboard-header {
        text-align: center;
        margin-bottom: 35px;
    }

    .leaderboard-header h1 {
        font-size: 32px;
        font-weight: 800;
        color: var(--text-primary);
        margin: 0 0 10px;
    }

    .leaderboard-header p {
        color: var(--text-secondary);
        font-size: 15px;
        margin: 0;
    }

    /* ==============================
       TOP THREE PODIUM
    ============================== */

    .top-three {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        align-items: end;
        gap: 25px;
        max-width: 1100px;
        margin: 55px auto 45px;
    }

    .top-card {
        position: relative;
        min-width: 0;
        min-height: 390px;
        padding: 35px 22px 25px;
        text-align: center;

        background: var(--bg-surface, var(--color-white));
        border: 1px solid var(--border-strong);
        border-radius: 35px;

        box-shadow: var(--shadow-md);
        transition: var(--transition-fast);

        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .top-card:hover {
        transform: translateY(-5px);
    }

    /* First Place */
    .first-place {
        min-height: 450px;
        border: 3px solid #E9C94A;
        background: var(--surface-secondary);
        box-shadow: 0 12px 30px rgba(220, 180, 40, 0.18);
        z-index: 2;
    }

    /* Second Place */
    .second-place {
        border: 2px solid #AEBBCB;
    }

    /* Third Place */
    .third-place {
        border: 2px solid #E8B47C;
    }

    /* Medal */
    .rank-icon {
        position: absolute;
        top: -27px;
        left: 50%;
        transform: translateX(-50%);
        width: 58px;
        height: 58px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 50%;
        background: var(--bg-surface, var(--color-white));
        box-shadow: var(--shadow-md);
        font-size: 30px;
    }

    .first-place .rank-icon {
        background: #F3C928;
    }

    .second-place .rank-icon {
        background: #D7E0ED;
    }

    .third-place .rank-icon {
        background: #F5BB7A;
    }

    /* Student Avatar */
    .student-avatar {
        width: 75px;
        height: 75px;
        border-radius: 50%;
        object-fit: cover;

        display: flex;
        align-items: center;
        justify-content: center;

        margin: 10px auto 20px;
        background: var(--gradient-brand);
        color: var(--text-on-brand);

        font-size: 25px;
        font-weight: 800;
        flex-shrink: 0;
    }

    /* Position Label */
    .position-label {
        color: var(--text-secondary);
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 1px;
        margin: 0 0 12px;
    }

    /* Student Name */
    .top-card h3 {
        color: var(--text-primary);
        font-size: 20px;
        font-weight: 800;
        margin: 0 0 12px;
        overflow-wrap: anywhere;
        line-height: 1.4;
    }

    /* Department and Year */
    .student-detail {
        display: inline-block;
        padding: 8px 15px;
        border-radius: 20px;

        background: var(--surface-secondary);
        border: 1px solid var(--border-strong);

        color: var(--text-secondary);
        font-size: 12px;
        font-weight: 600;
        margin: 0 0 22px;
    }

    /* Points Divider */
    .points-divider {
        width: 75%;
        height: 1px;
        background: var(--border-strong);
        margin: 0 auto 20px;
    }

    /* Points */
    .top-card .points {
        color: var(--color-blue-600);
        font-size: 38px;
        font-weight: 900;
        line-height: 1.2;
        margin: 0;
    }

    .first-place .points {
        color: #C58A00;
        font-size: 44px;
    }

    .points-label {
        color: var(--text-secondary);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 1.5px;
        margin-top: 8px;
    }

    /* ==============================
       RANKINGS TABLE SECTION
    ============================== */

    .leaderboard-table-section {
        width: 100%;
        background: var(--bg-surface, var(--color-white));
        border: 1px solid var(--border-strong);
        border-radius: 18px;
        box-shadow: var(--shadow-md);
        overflow: hidden;
        margin-top: 35px;
    }

    /* Table Top Header */
    .table-top-header {
        min-height: 75px;
        padding: 15px 25px;

        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        flex-wrap: wrap;

        border-bottom: 1px solid var(--border-strong);
    }

    .institution-info {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .institution-icon {
        font-size: 25px;
        color: var(--color-blue-600);
    }

    .institution-info h2 {
        font-size: 17px;
        font-weight: 800;
        color: var(--text-primary);
        margin: 0;
    }

    .institution-tag {
        display: inline-block;
        padding: 7px 13px;
        border-radius: 20px;

        background: var(--surface-secondary);
        border: 1px solid var(--border-strong);

        color: var(--text-secondary);
        font-size: 12px;
        font-weight: 700;
    }

    .participant-count {
        color: var(--text-secondary);
        font-size: 14px;
        font-weight: 600;
    }

    /* ==============================
       TABLE
    ============================== */

    .table-wrapper {
        width: 100%;
        overflow-x: auto;
    }

    .leaderboard-table {
        width: 100%;
        min-width: 700px;
        border-collapse: collapse;
        text-align: left;
    }

    .leaderboard-table thead {
        background: var(--surface-secondary);
    }

    .leaderboard-table th {
        padding: 17px 25px;
        color: var(--text-secondary);
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .leaderboard-table td {
        padding: 17px 25px;
        border-bottom: 1px solid var(--border-strong);
        color: var(--text-primary);
        font-size: 14px;
        vertical-align: middle;
    }

    .leaderboard-table tbody tr {
        transition: var(--transition-fast);
    }

    .leaderboard-table tbody tr:hover {
        background: var(--surface-secondary);
    }

    .leaderboard-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Rank Circle */
    .rank-number {
        width: 45px;
        height: 45px;
        display: inline-flex;
        align-items: center;
        justify-content: center;

        border-radius: 50%;
        background: var(--surface-secondary);
        color: var(--text-primary);

        font-size: 15px;
        font-weight: 800;
    }

    .rank-number.rank-1 {
        background: #F4CD36;
        color: #493900;
    }

    .rank-number.rank-2 {
        background: #B9C6D5;
        color: #263444;
    }

    .rank-number.rank-3 {
        background: #E6A15E;
        color: #58300B;
    }

    /* Student Details */
    .student-name-cell {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 220px;
    }

    .student-mini-avatar {
        width: 40px;
        height: 40px;
        min-width: 40px;
        border-radius: 50%;
        object-fit: cover;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        background: var(--gradient-brand);
        color: var(--text-on-brand);
        font-size: 13px;
        font-weight: 800;
    }

    .student-name {
        color: var(--text-primary);
        font-size: 14px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    /* Points */
    .score {
        color: var(--color-blue-600) !important;
        font-weight: 800;
        font-size: 16px !important;
        white-space: nowrap;
    }

    /* Achievement */
    .rank-badge {
        display: inline-block;
        padding: 7px 12px;
        border-radius: 8px;

        background: var(--surface-secondary);
        border: 1px solid var(--border-strong);

        color: var(--color-blue-600);
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    /* Empty Message */
    .empty-message {
        text-align: center;
        padding: 35px !important;
        color: var(--text-secondary) !important;
        font-size: 15px !important;
    }

    /* ==============================
       RESPONSIVE DESIGN
    ============================== */

    @media (max-width: 900px) {

        .top-three {
            gap: 15px;
        }

        .top-card {
            min-height: 350px;
            padding: 30px 15px 20px;
        }

        .first-place {
            min-height: 400px;
        }

        .top-card h3 {
            font-size: 17px;
        }

        .top-card .points {
            font-size: 30px;
        }

        .first-place .points {
            font-size: 36px;
        }

        .table-top-header {
            align-items: flex-start;
        }

    }

    @media (max-width: 600px) {

        .leaderboard-page {
            padding: 25px 12px 35px;
        }

        .leaderboard-header h1 {
            font-size: 25px;
        }

        .leaderboard-header p {
            font-size: 13px;
        }

        .top-three {
            grid-template-columns: 1fr;
            gap: 35px;
            max-width: 380px;
            margin-top: 45px;
        }

        .top-card,
        .first-place {
            min-height: 310px;
        }

        .first-place {
            order: -1;
        }

        .table-top-header {
            padding: 15px;
        }

        .institution-info h2 {
            font-size: 14px;
        }

        .participant-count {
            font-size: 13px;
        }

        .leaderboard-table th,
        .leaderboard-table td {
            padding: 13px 15px;
        }

    }

    @media (max-width: 380px) {

        .top-card h3 {
            font-size: 16px;
        }

        .top-card .points {
            font-size: 29px;
        }

        .first-place .points {
            font-size: 34px;
        }

        .institution-info {
            gap: 8px;
        }

        .institution-tag {
            font-size: 11px;
        }

    }

    </style>

</head>

<body>

<?php include 'nav.php'; ?>

<main class="leaderboard-page">

    <!-- Page Header -->
    <section class="leaderboard-header">

        <h1>🏆 Student Leaderboard</h1>

        <p>
            Recognizing the top contributors of the
            AITAM Slack community.
        </p>

    </section>


    <!-- Top Three Students -->
    <?php if (count($students) > 0): ?>

    <section class="top-three">

        <?php foreach ($podium as $item): ?>

            <?php
                $student = $item['student'];
                $initials = getInitials($student['name']);
            ?>

            <div class="top-card <?php echo $item['class']; ?>">

                <div class="rank-icon">
                    <?php echo $item['medal']; ?>
                </div>

                <?php if (!empty($student['profile_image'])): ?>

                    <img
                        src="<?php echo htmlspecialchars($student['profile_image']); ?>"
                        alt="<?php echo htmlspecialchars($student['name']); ?>"
                        class="student-avatar"
                    >

                <?php else: ?>

                    <div class="student-avatar">
                        <?php echo htmlspecialchars($initials); ?>
                    </div>

                <?php endif; ?>

                <p class="position-label">
                    <?php echo $item['label']; ?>
                </p>

                <h3>
                    <?php echo htmlspecialchars($student['name']); ?>
                </h3>

                <div class="student-detail">
                    <?php echo htmlspecialchars($student['department'] ?? 'N/A'); ?>
                    -
                    <?php echo htmlspecialchars($student['year'] ?? 'N/A'); ?>
                </div>

                <div class="points-divider"></div>

                <div class="points">
                    <?php echo formatPoints($student['total_points']); ?>
                </div>

                <div class="points-label">POINTS</div>

            </div>

        <?php endforeach; ?>

    </section>

    <?php endif; ?>


    <!-- Overall Rankings -->
    <section class="leaderboard-table-section">

        <!-- Table Header -->
        <div class="table-top-header">

            <div class="institution-info">

                <span class="institution-icon">▥</span>

                <h2>
                    Aditya Institute of Technology and Management
                </h2>

                <span class="institution-tag">
                    Institution
                </span>

                <span class="institution-tag">
                    Student Rankings
                </span>

            </div>

            <div class="participant-count">
                <?php echo count($students); ?> participants
            </div>

        </div>


        <!-- Rankings Table -->
        <div class="table-wrapper">

            <table class="leaderboard-table">

                <thead>

                    <tr>
                        <th>Rank</th>
                        <th>Student</th>
                        <th>Department</th>
                        <th>Points</th>
                        <th>Achievement</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (count($students) > 0): ?>

                        <?php foreach ($students as $index => $student): ?>

                            <?php

                                $rank = $index + 1;
                                $initials = getInitials($student['name']);

                                if ($rank == 1) {
                                    $achievement = "Gold";
                                } elseif ($rank == 2) {
                                    $achievement = "Silver";
                                } elseif ($rank == 3) {
                                    $achievement = "Bronze";
                                } elseif ($rank <= 10) {
                                    $achievement = "Top Contributor";
                                } else {
                                    $achievement = "Active Member";
                                }

                            ?>

                            <tr>

                                <!-- Rank -->
                                <td>
                                    <span class="rank-number rank-<?php echo $rank <= 3 ? $rank : 'other'; ?>">
                                        <?php echo $rank; ?>
                                    </span>
                                </td>

                                <!-- Student -->
                                <td>

                                    <div class="student-name-cell">

                                        <?php if (!empty($student['profile_image'])): ?>

                                            <img
                                                src="<?php echo htmlspecialchars($student['profile_image']); ?>"
                                                alt="<?php echo htmlspecialchars($student['name']); ?>"
                                                class="student-mini-avatar"
                                            >

                                        <?php else: ?>

                                            <span class="student-mini-avatar">
                                                <?php echo htmlspecialchars($initials); ?>
                                            </span>

                                        <?php endif; ?>

                                        <span class="student-name">
                                            <?php echo htmlspecialchars($student['name']); ?>
                                        </span>

                                    </div>

                                </td>

                                <!-- Department -->
                                <td>
                                    <?php echo htmlspecialchars($student['department'] ?? 'N/A'); ?>
                                </td>

                                <!-- Points -->
                                <td class="score">
                                    <?php echo formatPoints($student['total_points']); ?>
                                    <span style="font-size:12px;font-weight:500;color:var(--text-secondary);">
                                        pts
                                    </span>
                                </td>

                                <!-- Achievement -->
                                <td>
                                    <span class="rank-badge">
                                        <?php echo $achievement; ?>
                                    </span>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="empty-message">
                                No student records available.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<?php include 'footer.php'; ?>

</body>
</html>
