<?php
require 'db.php';

$today = date('Y-m-d');

$students_count = $db->query('SELECT COUNT(*) FROM students')->fetchColumn();
$teachers_count = $db->query('SELECT COUNT(*) FROM teachers')->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM courses WHERE actual_date > ?');
$stmt->execute([$today]);
$active_courses_count = $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM courses WHERE actual_date <= ?');
$stmt->execute([$today]);
$archive_courses_count = $stmt->fetchColumn();

require 'header.php';
?>

<h2>Главная</h2>

<div class="cards">
    <div class="card">
        <div class="number"><?php echo $students_count; ?></div>
        <div>Учеников</div>
        <a href="students.php">Открыть список</a>
    </div>
    <div class="card">
        <div class="number"><?php echo $active_courses_count; ?></div>
        <div>Действующих курсов</div>
        <a href="courses.php">Открыть список</a>
    </div>
    <div class="card">
        <div class="number"><?php echo $archive_courses_count; ?></div>
        <div>Курсов в архиве</div>
        <a href="courses.php">Открыть список</a>
    </div>
    <div class="card">
        <div class="number"><?php echo $teachers_count; ?></div>
        <div>Преподавателей</div>
        <a href="teachers.php">Открыть список</a>
    </div>
</div>

<?php require 'footer.php'; ?>
