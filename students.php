<?php
require 'db.php';

$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'add') {
        $fio = trim($_POST['fio'] ?? '');
        $birth_date = $_POST['birth_date'] ?? '';
        $contacts = trim($_POST['contacts'] ?? '');
        $gender = $_POST['gender'] ?? '';
        $course_id = $_POST['course_id'] ?? '';

        if ($fio == '' || $birth_date == '') {
            go('students.php', 'Заполните ФИО и дату рождения', 'error');
        }

        if ($gender != 'М' && $gender != 'Ж') {
            go('students.php', 'Выберите пол', 'error');
        }

        if ($course_id == '') {
            $course_id = null;
        }

        $stmt = $db->prepare('INSERT INTO students (fio, birth_date, contacts, gender, course_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$fio, $birth_date, $contacts, $gender, $course_id]);

        go('students.php', 'Ученик добавлен');
    }

    if ($action == 'delete') {
        $id = $_POST['id'] ?? 0;

        $stmt = $db->prepare('DELETE FROM students WHERE id = ?');
        $stmt->execute([$id]);

        go('students.php', 'Ученик удалён');
    }
}

$stmt = $db->prepare('SELECT * FROM courses WHERE actual_date > ? ORDER BY name');
$stmt->execute([$today]);
$active_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$students = $db->query('SELECT students.*, courses.name AS course_name
    FROM students
    LEFT JOIN courses ON courses.id = students.course_id
    ORDER BY students.fio')->fetchAll(PDO::FETCH_ASSOC);

require 'header.php';
?>

<h2>Ученики</h2>

<section class="box">
    <h3>Добавить ученика</h3>
    <form method="post">
        <input type="hidden" name="action" value="add">
        <label>ФИО
            <input type="text" name="fio" required>
        </label>
        <label>Дата рождения
            <input type="date" name="birth_date" required>
        </label>
        <label>Контактная информация
            <input type="text" name="contacts" placeholder="Телефон или email">
        </label>
        <label>Пол
            <select name="gender" required>
                <option value="">Выберите</option>
                <option value="М">Мужской</option>
                <option value="Ж">Женский</option>
            </select>
        </label>
        <label>Курс (можно выбрать позже)
            <select name="course_id">
                <option value="">Без курса</option>
                <?php foreach ($active_courses as $course) { ?>
                    <option value="<?php echo $course['id']; ?>"><?php echo h($course['name']); ?></option>
                <?php } ?>
            </select>
        </label>
        <button type="submit">Добавить</button>
    </form>
</section>

<section class="box">
    <h3>Список учеников</h3>
    <?php if (count($students) == 0) { ?>
        <p>Учеников пока нет.</p>
    <?php } else { ?>
        <table>
            <tr>
                <th>ФИО</th>
                <th>Дата рождения</th>
                <th>Контакты</th>
                <th>Пол</th>
                <th>Курс</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($students as $student) { ?>
                <tr>
                    <td><?php echo h($student['fio']); ?></td>
                    <td><?php echo h($student['birth_date']); ?></td>
                    <td><?php echo h($student['contacts']); ?></td>
                    <td><?php echo h($student['gender']); ?></td>
                    <td>
                        <?php
                        if ($student['course_name'] == null) {
                            echo 'не зачислен';
                        } else {
                            echo h($student['course_name']);
                        }
                        ?>
                    </td>
                    <td class="actions">
                        <a href="student.php?id=<?php echo $student['id']; ?>">Оценки</a>
                        <form method="post" class="inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                            <button type="submit" class="danger">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </table>
    <?php } ?>
</section>

<?php require 'footer.php'; ?>
