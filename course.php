<?php
require 'db.php';

$course_id = $_GET['id'] ?? 0;
$page = 'course.php?id=' . (int) $course_id;

$stmt = $db->prepare('SELECT * FROM courses WHERE id = ?');
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    go('courses.php', 'Курс не найден', 'error');
}

$is_active = course_is_active($course);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if (!$is_active) {
        go($page, 'Курс в архиве, изменения запрещены', 'error');
    }

    if ($action == 'add_subject') {
        $name = trim($_POST['name'] ?? '');
        $teacher_id = $_POST['teacher_id'] ?? '';

        if ($name == '') {
            go($page, 'Введите название предмета', 'error');
        }

        if ($teacher_id == '') {
            $teacher_id = null;
        }

        $stmt = $db->prepare('INSERT INTO subjects (name, course_id, teacher_id) VALUES (?, ?, ?)');
        $stmt->execute([$name, $course_id, $teacher_id]);

        go($page, 'Предмет добавлен');
    }

    if ($action == 'enroll') {
        $student_id = $_POST['student_id'] ?? '';

        if ($student_id == '') {
            go($page, 'Выберите ученика', 'error');
        }

        $stmt = $db->prepare('UPDATE students SET course_id = ? WHERE id = ?');
        $stmt->execute([$course_id, $student_id]);

        go($page, 'Ученик зачислен на курс');
    }
}

$stmt = $db->prepare('SELECT subjects.*, teachers.fio AS teacher_name
    FROM subjects
    LEFT JOIN teachers ON teachers.id = subjects.teacher_id
    WHERE subjects.course_id = ?
    ORDER BY subjects.name');
$stmt->execute([$course_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$teachers = $db->query('SELECT * FROM teachers ORDER BY fio')->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare('SELECT * FROM students WHERE course_id IS NULL OR course_id != ? ORDER BY fio');
$stmt->execute([$course_id]);
$other_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare('SELECT students.id, students.fio, AVG(grades.grade) AS average, COUNT(grades.id) AS grades_count
    FROM students
    LEFT JOIN subjects ON subjects.course_id = students.course_id
    LEFT JOIN grades ON grades.subject_id = subjects.id AND grades.student_id = students.id
    WHERE students.course_id = ?
    GROUP BY students.id
    ORDER BY students.fio');
$stmt->execute([$course_id]);
$course_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare('SELECT AVG(grades.grade)
    FROM grades
    JOIN subjects ON subjects.id = grades.subject_id
    JOIN students ON students.id = grades.student_id
    WHERE subjects.course_id = ? AND students.course_id = ?');
$stmt->execute([$course_id, $course_id]);
$course_average = $stmt->fetchColumn();
if ($course_average === false) {
    $course_average = null;
}

require 'header.php';
?>

<h2>Курс: <?php echo h($course['name']); ?></h2>

<p>
    Начало: <?php echo h($course['start_date']); ?>,
    актуален до: <?php echo h($course['actual_date']); ?>
    <?php if ($is_active) { ?>
        <span class="status active">Действует</span>
    <?php } else { ?>
        <span class="status archive">В архиве</span>
    <?php } ?>
</p>

<?php if (!$is_active) { ?>
    <p class="message error">Курс в архиве. Добавлять предметы и зачислять учеников нельзя.</p>
<?php } ?>

<section class="box">
    <h3>Предметы курса</h3>
    <?php if (count($subjects) == 0) { ?>
        <p>Предметов пока нет.</p>
    <?php } else { ?>
        <table>
            <tr>
                <th>Предмет</th>
                <th>Преподаватель</th>
            </tr>
            <?php foreach ($subjects as $subject) { ?>
                <tr>
                    <td><?php echo h($subject['name']); ?></td>
                    <td>
                        <?php
                        if ($subject['teacher_name'] == null) {
                            echo 'не назначен';
                        } else {
                            echo h($subject['teacher_name']);
                        }
                        ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
    <?php } ?>

    <?php if ($is_active) { ?>
        <h4>Добавить предмет</h4>
        <form method="post">
            <input type="hidden" name="action" value="add_subject">
            <label>Название предмета
                <input type="text" name="name" required>
            </label>
            <label>Преподаватель
                <select name="teacher_id">
                    <option value="">Не назначен</option>
                    <?php foreach ($teachers as $teacher) { ?>
                        <option value="<?php echo $teacher['id']; ?>"><?php echo h($teacher['fio']); ?></option>
                    <?php } ?>
                </select>
            </label>
            <button type="submit">Добавить предмет</button>
        </form>
    <?php } ?>
</section>

<section class="box">
    <h3>Ученики курса и их средние оценки</h3>
    <?php if (count($course_students) == 0) { ?>
        <p>На курсе пока нет учеников.</p>
    <?php } else { ?>
        <table>
            <tr>
                <th>ФИО</th>
                <th>Количество оценок</th>
                <th>Средняя оценка</th>
                <th>Подробнее</th>
            </tr>
            <?php foreach ($course_students as $student) { ?>
                <tr>
                    <td><?php echo h($student['fio']); ?></td>
                    <td><?php echo $student['grades_count']; ?></td>
                    <td><?php echo show_average($student['average']); ?></td>
                    <td><a href="student.php?id=<?php echo $student['id']; ?>">Оценки по предметам</a></td>
                </tr>
            <?php } ?>
        </table>
        <p class="total">Средняя оценка по всему курсу: <b><?php echo show_average($course_average); ?></b></p>
    <?php } ?>

    <?php if ($is_active) { ?>
        <h4>Зачислить ученика</h4>
        <?php if (count($other_students) == 0) { ?>
            <p>Нет учеников, которых можно зачислить.</p>
        <?php } else { ?>
            <form method="post">
                <input type="hidden" name="action" value="enroll">
                <label>Ученик
                    <select name="student_id" required>
                        <option value="">Выберите ученика</option>
                        <?php foreach ($other_students as $student) { ?>
                            <option value="<?php echo $student['id']; ?>"><?php echo h($student['fio']); ?></option>
                        <?php } ?>
                    </select>
                </label>
                <button type="submit">Зачислить</button>
            </form>
        <?php } ?>
    <?php } ?>
</section>

<p><a href="courses.php">&larr; Ко всем курсам</a></p>

<?php require 'footer.php'; ?>
