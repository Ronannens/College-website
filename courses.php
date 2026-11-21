<?php
require 'db.php';

$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'add') {
        $name = trim($_POST['name'] ?? '');
        $start_date = $_POST['start_date'] ?? '';
        $actual_date = $_POST['actual_date'] ?? '';

        if ($name == '' || $start_date == '' || $actual_date == '') {
            go('courses.php', 'Заполните все поля курса', 'error');
        }

        if ($actual_date <= $start_date) {
            go('courses.php', 'Дата актуальности должна быть позже даты начала', 'error');
        }

        $stmt = $db->prepare('INSERT INTO courses (name, start_date, actual_date) VALUES (?, ?, ?)');
        $stmt->execute([$name, $start_date, $actual_date]);

        go('courses.php', 'Курс добавлен');
    }

    if ($action == 'archive') {
        $id = $_POST['id'] ?? 0;

        $stmt = $db->prepare('UPDATE courses SET actual_date = ? WHERE id = ?');
        $stmt->execute([$today, $id]);

        go('courses.php', 'Курс перенесён в архив');
    }
}

$courses = $db->query('SELECT courses.*, COUNT(students.id) AS students_count
    FROM courses
    LEFT JOIN students ON students.course_id = courses.id
    GROUP BY courses.id
    ORDER BY courses.id DESC')->fetchAll(PDO::FETCH_ASSOC);

require 'header.php';
?>

<h2>Курсы</h2>

<section class="box">
    <h3>Добавить курс</h3>
    <form method="post">
        <input type="hidden" name="action" value="add">
        <label>Название курса
            <input type="text" name="name" required>
        </label>
        <label>Дата начала
            <input type="date" name="start_date" required>
        </label>
        <label>Актуален до
            <input type="date" name="actual_date" required>
        </label>
        <button type="submit">Добавить</button>
    </form>
</section>

<section class="box">
    <h3>Список курсов</h3>
    <?php if (count($courses) == 0) { ?>
        <p>Курсов пока нет.</p>
    <?php } else { ?>
        <table>
            <tr>
                <th>Название</th>
                <th>Начало</th>
                <th>Актуален до</th>
                <th>Учеников</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($courses as $course) { ?>
                <tr>
                    <td><?php echo h($course['name']); ?></td>
                    <td><?php echo h($course['start_date']); ?></td>
                    <td><?php echo h($course['actual_date']); ?></td>
                    <td><?php echo $course['students_count']; ?></td>
                    <td>
                        <?php if (course_is_active($course)) { ?>
                            <span class="status active">Действует</span>
                        <?php } else { ?>
                            <span class="status archive">В архиве</span>
                        <?php } ?>
                    </td>
                    <td class="actions">
                        <a href="course.php?id=<?php echo $course['id']; ?>">Открыть</a>
                        <?php if (course_is_active($course)) { ?>
                            <form method="post" class="inline">
                                <input type="hidden" name="action" value="archive">
                                <input type="hidden" name="id" value="<?php echo $course['id']; ?>">
                                <button type="submit" class="danger">В архив</button>
                            </form>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
    <?php } ?>
</section>

<?php require 'footer.php'; ?>
