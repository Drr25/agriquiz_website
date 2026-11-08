<?php
session_set_cookie_params([
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
$_SESSION['started'] = true;

$logoUrl = "asset/agriquiz-logo.png";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AgriQuiz is an educational web application for Filipino agriculture students preparing for the Agriculturist licensure exam. It offers reviewers, quizzes, and performance analytics.">
    <title>AGRIQUIZ – Smart Agricultural Learning</title>
    <link rel="icon" href="<?= $logoUrl ?>">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/all.min.css">
    <link rel="stylesheet" href="css/getstarted.css">
</head>

<body>
    <section class="hero">
        <div class="hero-inner">
            <div class="hero-badge">
                <i class="fas fa-seedling"></i>
                Agricultural Learning Platform
            </div>
            <h1>
                Learn Smarter.<br>
                <span>Grow Further.</span>
            </h1>
            <p>
                AgriQuiz helps educators import reviewers and quizzes,
                track student performance, and identify weak areas —
                all in one clean dashboard.
            </p>
            <a href="index.php" class="btn-primary">
                <span>Start Learning</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </section>
</body>

</html>