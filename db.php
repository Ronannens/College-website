<?php

date_default_timezone_set('Europe/Moscow');

$db = new PDO('sqlite:' . __DIR__ . '/college.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('PRAGMA foreign_keys = ON');

$db->exec('CREATE TABLE IF NOT EXISTS teachers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    fio TEXT NOT NULL,
    contacts TEXT
)');

$db->exec('CREATE TABLE IF NOT EXISTS courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    start_date TEXT NOT NULL,
    actual_date TEXT NOT NULL
)');

$db->exec('CREATE TABLE IF NOT EXISTS students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    fio TEXT NOT NULL,
    birth_date TEXT NOT NULL,
    contacts TEXT,
    gender TEXT NOT NULL,
    course_id INTEGER REFERENCES courses(id) ON DELETE SET NULL
)');

$db->exec('CREATE TABLE IF NOT EXISTS subjects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    course_id INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    teacher_id INTEGER REFERENCES teachers(id) ON DELETE SET NULL
)');

$db->exec('CREATE TABLE IF NOT EXISTS grades (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    subject_id INTEGER NOT NULL REFERENCES subjects(id) ON DELETE CASCADE,
    grade INTEGER NOT NULL,
    grade_date TEXT NOT NULL
)');

function h($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function go($page, $message, $type = 'ok')
{
    if (strpos($page, '?') === false) {
        $page = $page . '?';
    } else {
        $page = $page . '&';
    }
    header('Location: ' . $page . 'msg=' . urlencode($message) . '&type=' . $type);
    exit;
}

function course_is_active($course)
{
    $today = date('Y-m-d');
    if ($course['actual_date'] > $today) {
        return true;
    }
    return false;
}

function show_average($value)
{
    if ($value === null) {
        return 'нет оценок';
    }
    return number_format($value, 2, ',', '');
}
