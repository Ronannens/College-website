<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Платформа колледжа</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>Платформа колледжа</h1>
    <nav>
        <a href="index.php">Главная</a>
        <a href="students.php">Ученики</a>
        <a href="courses.php">Курсы</a>
        <a href="teachers.php">Преподаватели</a>
    </nav>
</header>
<main>
<?php
if (isset($_GET['msg'])) {
    $message_class = 'ok';
    if (isset($_GET['type']) && $_GET['type'] == 'error') {
        $message_class = 'error';
    }
    echo '<p class="message ' . $message_class . '">' . h($_GET['msg']) . '</p>';
}
?>
