<?php
<<<<<<< HEAD
=======
session_start();

>>>>>>> fa50bb2b2a8341826462e66e39e8937cd9d3912f
require_once 'lesder-db.php';

/* Escape output safely */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

try {
    /*
     * Count questions and answers separately.
     * Points = number of questions asked.
     */
    $sql = "
        SELECT
            s.id,
            s.name,
            s.department,
            s.year,
            s.profile_image,

            COALESCE(q.questions_asked, 0) AS questions_asked,
            COALESCE(a.answers_given, 0) AS answers_given,

            COALESCE(q.questions_asked, 0) AS total_points

        FROM students s

        LEFT JOIN (
            SELECT user_id, COUNT(*) AS questions_asked
            FROM questions
            GROUP BY user_id
        ) q ON q.user_id = s.id

        LEFT JOIN (
            SELECT user_id, COUNT(*) AS answers_given
            FROM answers
            GROUP BY user_id
        ) a ON a.user_id = s.id

        ORDER BY
            total_points DESC,
            answers_given DESC,
            s.name ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Unable to load leaderboard. Please try again later.");
}

/* Top three students */
$topThree = array_slice($students, 0, 3);

$totalStudents = count($students);
$totalQuestions = array_sum(array_column($students, 'questions_asked'));
$totalAnswers = array_sum(array_column($students, 'answers_given'));

function getRank($rank) {
    if ($rank == 1) return "🥇";
    if ($rank == 2) return "🥈";
    if ($rank == 3) return "🥉";
    return $rank;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Leaderboard | AITAM Slack</title>

    <link rel="stylesheet" href="./assets/css/root.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: var(--font-family-base, Arial, sans-serif);
            background: var(--bg-surface, #f5f7fb);
            color: var(--text-primary, #172033);
        }

        .leaderboard-container {
            width: 92%;
            max-width: 1200px;
            margin: 35px auto;
        }

        .leaderboard-heading {
            text-align: center;
            margin-bottom: 30px;
        }

        .leaderboard-heading h1 {
            font-size: 32px;
            margin-bottom: 8px;
            color: var(--text-primary, #172033);
        }

        .leaderboard-heading p {
            color: var(--text-secondary, #687386);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--bg-surface, #fff);
            padding: 22px;
            border-radius: 12px;
            border: 1px solid var(--border-strong, #e1e5ec);
            text-align: center;
        }

        .stat-card h3 {
            margin: 0 0 10px;
            font-size: 15px;
            color: var(--text-secondary, #687386);
        }

        .stat-card p {
            margin: 0;
            font-size: 27px;
            font-weight: 700;
            color: var(--color-accent, #2563eb);
        }

        .podium-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 35px;
        }

        .podium-card {
            background: var(--bg-surface, #fff);
            border: 1px solid var(--border-strong, #e1e5ec);
            border-radius: 14px;
            padding: 25px 15px;
            text-align: center;
        }

        .podium-card.first {
            border: 2px solid #eab308;
        }

        .podium-medal {
            font-size: 35px;
            margin-bottom: 10px;
        }

        .podium-card h3 {
            margin: 8px 0;
            font-size: 18px;
        }

        .podium-card p {
            margin: 5px 0;
            color: var(--text-secondary, #687386);
            font-size: 14px;
        }

        .points {
            color: var(--color-accent, #2563eb) !important;
            font-weight: 700;
        }

        .table-wrapper {
            overflow-x: auto;
            background: var(--bg-surface, #fff);
            border-radius: 12px;
            border: 1px solid var(--border-strong, #e1e5ec);
        }

        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        .leaderboard-table th,
        .leaderboard-table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-strong, #e1e5ec);
        }

        .leaderboard-table th {
            background: var(--color-navy-950, #0f2345);
            color: white;
            font-size: 14px;
        }

        .leaderboard-table td {
            font-size: 14px;
            color: var(--text-primary, #172033);
        }

        .leaderboard-table tr:last-child td {
            border-bottom: none;
        }

        .leaderboard-table tbody tr:hover {
            background: var(--surface-secondary, #f1f5f9);
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .student-info img,
        .student-initial {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
        }

        .student-initial {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--color-navy-950, #0f2345);
            color: white;
            font-weight: 700;
        }

        .student-name {
            font-weight: 600;
        }

        .rank {
            font-size: 18px;
            font-weight: 700;
        }

        .achievement {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            background: var(--surface-secondary, #e8eef8);
            color: var(--text-primary, #172033);
            font-size: 12px;
            font-weight: 600;
        }

        .empty-message {
            text-align: center;
            padding: 35px;
            color: var(--text-secondary, #687386);
        }

        @media (max-width: 700px) {
            .stats-container {
                grid-template-columns: 1fr;
            }

            .podium-container {
                grid-template-columns: 1fr;
            }

            .leaderboard-heading h1 {
                font-size: 25px;
            }
        }
    </style>
</head>

<body>

<?php include 'nav.php'; ?>

<main class="leaderboard-container">

    <div class="leaderboard-heading">
        <h1>🏆 Student Leaderboard</h1>
        <p>Recognizing students who actively participate in AITAM Slack.</p>
    </div>

    <!-- Statistics -->
    <section class="stats-container">

        <div class="stat-card">
            <h3>Total Students</h3>
            <p><?= e($totalStudents) ?></p>
        </div>

        <div class="stat-card">
            <h3>Total Questions</h3>
            <p><?= e($totalQuestions) ?></p>
        </div>

        <div class="stat-card">
            <h3>Total Answers</h3>
            <p><?= e($totalAnswers) ?></p>
        </div>

    </section>

    <!-- Top Three Students -->
    <?php if (!empty($topThree)): ?>

        <section class="podium-container">

            <?php
            // Display second, first, third
            $podiumOrder = [];

            if (isset($topThree[1])) {
                $podiumOrder[] = [2, $topThree[1]];
            }

            $podiumOrder[] = [1, $topThree[0]];

            if (isset($topThree[2])) {
                $podiumOrder[] = [3, $topThree[2]];
            }
            ?>

            <?php foreach ($podiumOrder as [$rank, $student]): ?>

                <div class="podium-card <?= $rank == 1 ? 'first' : '' ?>">

                    <div class="podium-medal">
                        <?= getRank($rank) ?>
                    </div>

                    <h3><?= e($student['name']) ?></h3>

                    <p>
                        <?= e($student['department'] ?? 'N/A') ?>
                    </p>

                    <p>
                        Questions:
                        <?= e($student['questions_asked']) ?>
                    </p>

                    <p>
                        Answers:
                        <?= e($student['answers_given']) ?>
                    </p>

                    <p class="points">
                        <?= e($student['total_points']) ?> Points
                    </p>

                </div>

            <?php endforeach; ?>

        </section>

    <?php endif; ?>

    <!-- Dynamic Leaderboard Table -->
    <section class="table-wrapper">

        <table class="leaderboard-table">

            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Student</th>
                    <th>Department</th>
                    <th>Year</th>
                    <th>Questions Asked</th>
                    <th>Answers Given</th>
                    <th>Points</th>
                    <th>Achievement</th>
                </tr>
            </thead>

            <tbody>

                <?php if (!empty($students)): ?>

                    <?php foreach ($students as $index => $student): ?>

                        <?php
                        $rank = $index + 1;

                        if ($rank == 1) {
                            $achievement = "Top Contributor";
                        } elseif ($rank == 2) {
                            $achievement = "Excellent";
                        } elseif ($rank == 3) {
                            $achievement = "Outstanding";
                        } else {
                            $achievement = "Active Member";
                        }

                        $initial = strtoupper(
                            substr($student['name'] ?? 'S', 0, 1)
                        );
                        ?>

                        <tr>

                            <td class="rank">
                                <?= getRank($rank) ?>
                            </td>

                            <td>
                                <div class="student-info">

                                    <?php if (!empty($student['profile_image'])): ?>

                                        <img
                                            src="<?= e($student['profile_image']) ?>"
                                            alt="Student profile">

                                    <?php else: ?>

                                        <div class="student-initial">
                                            <?= e($initial) ?>
                                        </div>

                                    <?php endif; ?>

                                    <span class="student-name">
                                        <?= e($student['name']) ?>
                                    </span>

                                </div>
                            </td>

                            <td>
                                <?= e($student['department'] ?? 'N/A') ?>
                            </td>

                            <td>
                                <?= e($student['year'] ?? 'N/A') ?>
                            </td>

                            <td>
                                <?= e($student['questions_asked']) ?>
                            </td>

                            <td>
                                <?= e($student['answers_given']) ?>
                            </td>

                            <td>
                                <strong>
                                    <?= e($student['total_points']) ?>
                                </strong>
                            </td>

                            <td>
                                <span class="achievement">
                                    <?= e($achievement) ?>
                                </span>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8" class="empty-message">
                            No student records found.
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
