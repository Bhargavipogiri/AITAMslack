
<?php
$developers = [
    [
        "name" => "Developer One",
        "role" => "Full Stack Developer",
        "photo" => "https://media.istockphoto.com/id/1437816897/photo/business-woman-manager-or-human-resources-portrait-for-career-success-company-we-are-hiring.jpg?s=612x612&w=0&k=20&c=tyLvtzutRh22j9GqSGI33Z4HpIwv9vL_MZw_xOE19NQ=",
        "description" => "Responsible for frontend development, backend functionality and database management.",
        "linkedin" => "https://www.linkedin.com/in/your-profile-1/"
    ],
    [
        "name" => "Developer Two",
        "role" => "Frontend Developer",
        "photo" => "https://i.pinimg.com/1200x/8c/d2/8a/8cd28a5e72c45fb4b9695fdde3722ede.jpg",
        "description" => "Responsible for designing responsive interfaces and improving user experience.",
        "linkedin" => "https://www.linkedin.com/in/your-profile-2/"
    ],
    [
        "name" => "Developer Three",
        "role" => "Backend Developer",
        "photo" => "https://i.etsystatic.com/31548528/r/il/ffde13/5804742914/il_fullxfull.5804742914_ap2d.jpg",
        "description" => "Responsible for PHP development, database integration and application security.",
        "linkedin" => "https://www.linkedin.com/in/your-profile-3/"
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Developers</title>
    <link rel="stylesheet" href="assets/css/developers.css">
    <style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #f4f7fb;
    color: #1e293b;
}

.dev-header {
    text-align: center;
    padding: 65px 20px;
    background: linear-gradient(90deg, #141e30, #243b55);
    color: white;
}

.dev-header h1 {
    font-size: 36px;
    margin: 20px 0 12px;
}

.dev-header p {
    color: #cbd5e1;
}

.back-link {
    color: white;
    text-decoration: none;
    display: inline-block;
}

.developers-container {
    max-width: 1150px;
    margin: 55px auto;
    padding: 0 20px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 30px;
}

.developer-card {
    background: white;
    padding: 30px 24px;
    text-align: center;
    border-radius: 16px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.07);
    transition: transform 0.3s ease;
}

.developer-card:hover {
    transform: translateY(-8px);
}

.developer-photo {
    width: 145px;
    height: 145px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #243b55;
    margin-bottom: 20px;
}

.developer-card h2 {
    font-size: 21px;
    margin-bottom: 8px;
}

.developer-role {
    display: inline-block;
    color: #2563eb;
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 15px;
}

.developer-card p {
    color: #64748b;
    font-size: 14px;
    line-height: 1.7;
    min-height: 72px;
}

.linkedin-btn {
    display: inline-block;
    margin-top: 20px;
    padding: 11px 22px;
    background: #0a66c2;
    color: white;
    text-decoration: none;
    border-radius: 7px;
    font-size: 14px;
    font-weight: bold;
}

.linkedin-btn:hover {
    background: #084d91;
}

@media(max-width: 600px) {
    .dev-header h1 {
        font-size: 28px;
    }

    .developers-container {
        margin: 30px auto;
    }
}

    </style>
</head>
<body>

<header class="dev-header">
    <a href="index.php" class="back-link">← Back to Home</a>
    <h1>Meet Our Developers</h1>
    <p>The talented team behind our platform.</p>
</header>

<main class="developers-container">

    <?php foreach ($developers as $developer): ?>
        <div class="developer-card">

            <img
                src="<?= htmlspecialchars($developer['photo']) ?>"
                alt="<?= htmlspecialchars($developer['name']) ?>"
                class="developer-photo"
            >

            <h2><?= htmlspecialchars($developer['name']) ?></h2>

            <span class="developer-role">
                <?= htmlspecialchars($developer['role']) ?>
            </span>

            <p>
                <?= htmlspecialchars($developer['description']) ?>
            </p>

            <a
                href="<?= htmlspecialchars($developer['linkedin']) ?>"
                target="_blank"
                rel="noopener noreferrer"
                class="linkedin-btn"
            >
                LinkedIn Profile ↗
            </a>

        </div>
    <?php endforeach; ?>

</main>

</body>
</html>
