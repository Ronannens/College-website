<?php
require 'db.php';

$student_id = $_GET['id'] ?? 0;
$page = 'student.php?id=' . (int) $student_id;

$stmt = $db->prepare('SELECT students.*, courses.name AS course_name
    FROM students
    LEFT JOIN courses ON courses.id = students.course_id
    WHERE students.id = ?');
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    go('students.php', 'Ученик не найден', 'error');
}

$course_id = $student['course_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'add_grade') {
        $subject_id = $_POST['subject_id'] ?? '';
        $grade = (int) ($_POST['grade'] ?? 0);

        if ($grade < 2 || $grade > 5) {
            go($page, 'Оценка должна быть от 2 до 5', 'error');
        }

        $stmt = $db->prepare('SELECT id FROM subjects WHERE id = ? AND course_id = ?');
        $stmt->execute([$subject_id, $course_id]);
        $found = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$found) {
            go($page, 'Выберите предмет из курса ученика', 'error');
        }

        $stmt = $db->prepare('INSERT INTO grades (student_id, subject_id, grade, grade_date) VALUES (?, ?, ?, ?)');
        $stmt->execute([$student_id, $subject_id, $grade, date('Y-m-d')]);

        go($page, 'Оценка добавлена');
    }

    if ($action == 'delete_grade') {
        $grade_id = $_POST['grade_id'] ?? 0;

        $stmt = $db->prepare('DELETE FROM grades WHERE id = ? AND student_id = ?');
        $stmt->execute([$grade_id, $student_id]);

        go($page, 'Оценка удалена');
    }
}

$subject_averages = [];
$grades_list = [];
$total_average = null;

if ($course_id != null) {
    $stmt = $db->prepare('SELECT subjects.id, subjects.name, AVG(grades.grade) AS average, COUNT(grades.id) AS grades_count
        FROM subjects
        LEFT JOIN grades ON grades.subject_id = subjects.id AND grades.student_id = ?
        WHERE subjects.course_id = ?
        GROUP BY subjects.id
        ORDER BY subjects.name');
    $stmt->execute([$student_id, $course_id]);
    $subject_averages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare('SELECT AVG(grades.grade)
        FROM grades
        JOIN subjects ON subjects.id = grades.subject_id
        WHERE grades.student_id = ? AND subjects.course_id = ?');
    $stmt->execute([$student_id, $course_id]);
    $total_average = $stmt->fetchColumn();
    if ($total_average === false) {
        $total_average = null;
    }

    $stmt = $db->prepare('SELECT grades.id, grades.grade, grades.grade_date, subjects.name
        FROM grades
        JOIN subjects ON subjects.id = grades.subject_id
        WHERE grades.student_id = ? AND subjects.course_id = ?
        ORDER BY grades.id DESC');
    $stmt->execute([$student_id, $course_id]);
    $grades_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

require 'header.php';
?>

<h2>Ученик: <?php echo h($student['fio']); ?></h2>

<section class="box">
    <p>Дата рождения: <?php echo h($student['birth_date']); ?></p>
    <p>Контакты: <?php echo h($student['contacts']); ?></p>
    <p>Пол: <?php echo h($student['gender']); ?></p>
    <p>
        Курс:
        <?php
        if ($student['course_name'] == null) {
            echo 'не зачислен';
        } else {
            echo h($student['course_name']);
        }
        ?>
    </p>
</section>

<?php if ($course_id == null) { ?>
    <p class="message error">Ученик не зачислен на курс, поэтому оценок пока нет. Зачислить можно на странице курса.</p>
<?php } else { ?>

    <section class="box">
        <h3>Средние оценки по предметам</h3>
        <?php if (count($subject_averages) == 0) { ?>
            <p>У курса пока нет предметов.</p>
        <?php } else { ?>
            <table>
                <tr>
                    <th>Предмет</th>
                    <th>Количество оценок</th>
                    <th>Средняя оценка</th>
                </tr>
                <?php foreach ($subject_averages as $row) { ?>
                    <tr>
                        <td><?php echo h($row['name']); ?></td>
                        <td><?php echo $row['grades_count']; ?></td>
                        <td><?php echo show_average($row['average']); ?></td>
                    </tr>
                <?php } ?>
            </table>
            <p class="total">Общая средняя оценка: <b><?php echo show_average($total_average); ?></b></p>
        <?php } ?>
    </section>

    <?php if (count($subject_averages) > 0) { ?>
        <section class="box">
            <h3>Поставить оценку</h3>
            <form method="post">
                <input type="hidden" name="action" value="add_grade">
                <label>Предмет
                    <select name="subject_id" required>
                        <option value="">Выберите предмет</option>
                        <?php foreach ($subject_averages as $row) { ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo h($row['name']); ?></option>
                        <?php } ?>
                    </select>
                </label>
                <label>Оценка
                    <select name="grade" required>
                        <option value="">Выберите</option>
                        <option value="5">5</option>
                        <option value="4">4</option>
                        <option value="3">3</option>
                        <option value="2">2</option>
                    </select>
                </label>
                <button type="submit">Сохранить оценку</button>
            </form>
        </section>
    <?php } ?>

    <?php if (count($grades_list) > 0) { ?>
        <section class="box">
            <h3>Все оценки</h3>
            <table>
                <tr>
                    <th>Дата</th>
                    <th>Предмет</th>
                    <th>Оценка</th>
                    <th>Действие</th>
                </tr>
                <?php foreach ($grades_list as $item) { ?>
                    <tr>
                        <td><?php echo h($item['grade_date']); ?></td>
                        <td><?php echo h($item['name']); ?></td>
                        <td><?php echo $item['grade']; ?></td>
                        <td>
                            <form method="post" class="inline">
                                <input type="hidden" name="action" value="delete_grade">
                                <input type="hidden" name="grade_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </section>
    <?php } ?>

<?php } ?>

<p><a href="students.php">&larr; Ко всем ученикам</a></p>

<?php require 'footer.php'; ?>
