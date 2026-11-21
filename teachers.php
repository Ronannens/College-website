<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? 'add';
    $fio = trim($_POST['fio'] ?? '');
    $contacts = trim($_POST['contacts'] ?? '');

    if ($fio == '') {
        go('teachers.php', 'Введите ФИО преподавателя', 'error');
    }

    if ($action == 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $db->prepare('UPDATE teachers SET fio = ?, contacts = ? WHERE id = ?');
        $stmt->execute([$fio, $contacts, $id]);
        go('teachers.php', 'Данные преподавателя обновлены');
    } else {
        $stmt = $db->prepare('INSERT INTO teachers (fio, contacts) VALUES (?, ?)');
        $stmt->execute([$fio, $contacts]);
        go('teachers.php', 'Преподаватель добавлен');
    }
}

$edit_teacher = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM teachers WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit_teacher = $stmt->fetch(PDO::FETCH_ASSOC);
}

$teachers = $db->query('SELECT * FROM teachers ORDER BY fio')->fetchAll(PDO::FETCH_ASSOC);

require 'header.php';
?>

<h2>Преподаватели</h2>

<section class="box">
    <h3><?php echo $edit_teacher ? 'Редактировать преподавателя' : 'Добавить преподавателя'; ?></h3>
    <form method="post">
        <input type="hidden" name="action" value="<?php echo $edit_teacher ? 'edit' : 'add'; ?>">
        <?php if ($edit_teacher) { ?>
            <input type="hidden" name="id" value="<?php echo $edit_teacher['id']; ?>">
        <?php } ?>

        <label>ФИО
            <input type="text" name="fio" value="<?php echo h($edit_teacher['fio'] ?? ''); ?>" required>
        </label>
        <label>Контакты
            <input type="text" name="contacts" value="<?php echo h($edit_teacher['contacts'] ?? ''); ?>" placeholder="Телефон или email">
        </label>
        <button type="submit"><?php echo $edit_teacher ? 'Сохранить' : 'Добавить'; ?></button>
        <?php if ($edit_teacher) { ?>
            <a href="teachers.php">Отмена</a>
        <?php } ?>
    </form>
</section>

<section class="box">
    <h3>Список преподавателей</h3>
    <?php if (count($teachers) == 0) { ?>
        <p>Преподавателей пока нет.</p>
    <?php } else { ?>
        <table>
            <tr>
                <th>ФИО</th>
                <th>Контакты</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($teachers as $teacher) { ?>
                <tr>
                    <td><?php echo h($teacher['fio']); ?></td>
                    <td><?php echo h($teacher['contacts']); ?></td>
                    <td><a href="teachers.php?edit=<?php echo $teacher['id']; ?>">Редактировать</a></td>
                </tr>
            <?php } ?>
        </table>
    <?php } ?>
</section>

<?php require 'footer.php'; ?>
