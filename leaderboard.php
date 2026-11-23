
<?php
require_once 'lesder-db.php';

// Fetch students based on total points
$sql = "SELECT id, name, department, year,
               profile_image, total_points
        FROM students
        ORDER BY total_points DESC, name ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top three students
$topThree = array_slice($students, 0, 3);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AITAM Slack - Leaderboard</title>

    <!-- Root CSS -->
    <link rel="stylesheet" href="root.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: var(--font-family-base);
            background: var(--color-background);
            color: var(--text-primary);
        }

        .leaderboard-page {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        /* Header */

        .leaderboard-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .leaderboard-header h1 {
            font-size: 32px;
            color: var(--color-navy-950);
        }

        .leaderboard-header p {
            color: var(--text-secondary);
        }

        /* Top Three */

        .top-three {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
            align-items: end;
        }

        .top-card {
            background: var(--color-white);
            border: 1px solid var(--color-blue-500);
            border-radius: 14px;
            padding: 25px 15px;
            text-align: center;
            box-shadow: var(--shadow-md);
            transition: var(--transition-fast);
        }

        .top-card:hover {
            transform: translateY(-5px);
        }

        .first-place {
            border: 2px solid #E5B43C;
        }

        .second-place {
            border: 2px solid #A7A7A7;
        }

        .third-place {
            border: 2px solid #CD7F32;
        }

        .rank-icon {
            font-size: 35px;
        }

        .student-avatar {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            object-fit: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 12px auto;
            background: var(--gradient-brand);
            color: var(--color-white);
            font-size: 25px;
            font-weight: 700;
        }

        .top-card h3 {
            color: var(--color-navy-950);
            font-size: 18px;
        }

        .top-card p {
            color: var(--text-secondary);
            font-size: 13px;
        }

        .points {
            color: var(--color-blue-600);
            font-size: 20px;
            font-weight: 800;
        }

        /* Table */

        .leaderboard-table-section {
            background: var(--color-white);
            padding: 25px;
            border-radius: 14px;
            border: 1px solid var(--color-blue-500);
            box-shadow: var(--shadow-md);
            overflow-x: auto;
        }

        .leaderboard-table-section h2 {
            color: var(--color-navy-950);
            margin-bottom: 20px;
        }

        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 550px;
        }

        .leaderboard-table th {
            background: var(--gradient-brand);
            color: var(--text-on-brand);
            padding: 15px;
            text-align: left;
        }

        .leaderboard-table td {
            padding: 15px;
            border-bottom: 1px solid var(--color-blue-500);
        }

        .leaderboard-table tbody tr:hover {
            background: var(--surface-secondary);
        }

        .student-mini-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            vertical-align: middle;
            margin-right: 10px;
            background: var(--gradient-brand);
            color: var(--color-white);
            font-weight: 700;
        }

        .student-name {
            font-weight: 600;
        }

        .score {
            color: var(--color-blue-600);
            font-weight: 700;
        }

        .rank-badge {
            background: var(--surface-secondary);
            color: var(--color-navy-950);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .empty-message {
            text-align: center;
            padding: 30px;
            color: var(--text-secondary);
        }

        /* Responsive */

        @media(max-width: 768px) {

            .leaderboard-page {
                padding: 15px;
            }

            .leaderboard-header h1 {
                font-size: 25px;
            }

            .top-three {
                grid-template-columns: 1fr;
            }

            .leaderboard-table-section {
                padding: 15px;
            }

        }

    </style>

</head>

<body>
<?php include 'nav.php'; ?>

<main class="leaderboard-page">

    <!-- Header -->

    <section class="leaderboard-header">

        <h1>🏆 Student Leaderboard</h1>

        <p>
            Recognizing top contributors of the
            AITAM Slack community.
        </p>

    </section>


    <!-- Top Three Students -->

    <section class="top-three">

        <?php

        $medals = [
            0 => ['🥇', 'first-place'],
            1 => ['🥈', 'second-place'],
            2 => ['🥉', 'third-place']
        ];

        // Display top three in rank order
        foreach ($topThree as $index => $student):

            $medal = $medals[$index][0];
            $class = $medals[$index][1];

            $initials = strtoupper(
                implode('', array_map(
                    fn($word) => $word[0],
                    preg_split('/\s+/', trim($student['name']))
                ))
            );

        ?>

        <div class="top-card <?php echo $class; ?>">

            <div class="rank-icon">
                <?php echo $medal; ?>
            </div>

            <?php if (!empty($student['profile_image'])): ?>

                <img
                    src="<?php echo htmlspecialchars($student['profile_image']); ?>"
                    alt="Student"
                    class="student-avatar"
                >

            <?php else: ?>

                <div class="student-avatar">
                    <?php echo htmlspecialchars($initials); ?>
                </div>

            <?php endif; ?>

            <h3>
                <?php echo htmlspecialchars($student['name']); ?>
            </h3>

            <p>
                <?php echo htmlspecialchars($student['department'] ?? 'N/A'); ?>
                -
                <?php echo htmlspecialchars($student['year'] ?? 'N/A'); ?>
            </p>

            <div class="points">
                <?php echo number_format((int)$student['total_points']); ?>
                Points
            </div>

        </div>

        <?php endforeach; ?>

    </section>


    <!-- Overall Rankings -->

    <section class="leaderboard-table-section">

        <h2>Overall Rankings</h2>

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

                        $initials = strtoupper(
                            implode('', array_map(
                                fn($word) => $word[0],
                                preg_split('/\s+/', trim($student['name']))
                            ))
                        );

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

                            <td>
                                <strong>
                                    <?php echo sprintf('%02d', $rank); ?>
                                </strong>
                            </td>

                            <td class="student-name">

                                <?php if (!empty($student['profile_image'])): ?>

                                    <img
                                        src="<?php echo htmlspecialchars($student['profile_image']); ?>"
                                        alt="Student"
                                        class="student-mini-avatar"
                                    >

                                <?php else: ?>

                                    <span class="student-mini-avatar">
                                        <?php echo htmlspecialchars($initials); ?>
                                    </span>

                                <?php endif; ?>

                                <?php echo htmlspecialchars($student['name']); ?>

                            </td>

                            <td>
                                <?php echo htmlspecialchars($student['department'] ?? 'N/A'); ?>
                            </td>

                            <td class="score">
                                <?php echo number_format((int)$student['total_points']); ?>
                            </td>

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

    </section>

</main>
<?php include 'footer.php'; ?>

</body>
</html>
