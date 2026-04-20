<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_GET['edit_id'])) {
    $_SESSION['user_id'] = $_GET['edit_id'];
    $_SESSION['admin_mode'] = true;
}

$db_user = 'u82373';
$db_pass = '4362231';

try {
    $pdo = new PDO('mysql:host=localhost;dbname=u82373;charset=utf8', $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Ошибка сервера. Попробуйте позже.");
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $messages = [];
    if (!empty($_COOKIE['save_success'])) {
        $messages[] = $_COOKIE['save_success'];
        setcookie('save_success', '', 100000);
    }

    $errors = [];
    foreach (['fio','phone','email','birth','gender','languages','bio','contract'] as $f) {
        if (!empty($_COOKIE[$f.'_error'])) {
            $errors[$f] = $_COOKIE[$f.'_error'];
            setcookie($f.'_error', '', 100000);
        }
    }

    $values = [];
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM applications WHERE id=?");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();
        
        $values['fio'] = $_COOKIE['fio_value'] ?? ($row['fio'] ?? '');
        $values['phone'] = $_COOKIE['phone_value'] ?? ($row['phone'] ?? '');
        $values['email'] = $_COOKIE['email_value'] ?? ($row['email'] ?? '');
        $values['birth_date'] = $_COOKIE['birth_value'] ?? ($row['birth_date'] ?? '');
        $values['gender'] = $_COOKIE['gender_value'] ?? ($row['gender'] ?? '');
        $values['biography'] = $_COOKIE['bio_value'] ?? ($row['biography'] ?? '');
        $values['contract'] = 1;
        
        if (isset($_COOKIE['languages_value'])) {
            $values['languages'] = explode(',', $_COOKIE['languages_value']);
        } else {
            $stmt_l = $pdo->prepare("SELECT language_id FROM application_languages WHERE application_id=?");
            $stmt_l->execute([$_SESSION['user_id']]);
            $values['languages'] = $stmt_l->fetchAll(PDO::FETCH_COLUMN);
        }
    } else {
        $values['fio'] = $_COOKIE['fio_value'] ?? '';
        $values['phone'] = $_COOKIE['phone_value'] ?? '';
        $values['email'] = $_COOKIE['email_value'] ?? '';
        $values['birth_date'] = $_COOKIE['birth_value'] ?? '';
        $values['gender'] = $_COOKIE['gender_value'] ?? '';
        $values['biography'] = $_COOKIE['bio_value'] ?? '';
        $values['contract'] = $_COOKIE['contract_value'] ?? '';
        $values['languages'] = isset($_COOKIE['languages_value']) ? explode(',', $_COOKIE['languages_value']) : [];
    }

    include 'form.php';
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Ошибка безопасности: неверный CSRF токен');
}

$errors = false;
$fio = $_POST['fio'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$birth = $_POST['birth_date'] ?? '';
$gender = $_POST['gender'] ?? '';
$languages = $_POST['languages'] ?? [];
$bio = $_POST['biography'] ?? '';
$contract = isset($_POST['contract']);

if (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $fio)) {
    setcookie('fio_error', 'Заполните ФИО (только буквы)', time() + 24*3600);
    $errors = true;
}
if (!preg_match('/^[0-9+\-\s()]+$/', $phone)) {
    setcookie('phone_error', 'Неверный телефон', time() + 24*3600);
    $errors = true;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setcookie('email_error', 'Неверный email', time() + 24*3600);
    $errors = true;
}

$birth_ts = strtotime($birth);
$current_year = (int)date('Y');
if (!$birth_ts || $birth_ts > time() || (int)date('Y', $birth_ts) < 1900 || (int)date('Y', $birth_ts) > $current_year) {
    setcookie('birth_error', 'Укажите реальную дату рождения', time() + 24*3600);
    $errors = true;
}

if (empty($gender)) {
    setcookie('gender_error', 'Выберите пол', time() + 24*3600);
    $errors = true;
}
if (empty($languages)) {
    setcookie('languages_error', 'Выберите языки', time() + 24*3600);
    $errors = true;
}
if (empty($bio)) {
    setcookie('bio_error', 'Заполните биографию', time() + 24*3600);
    $errors = true;
}
if (!$contract) {
    setcookie('contract_error', 'Нужно согласие', time() + 24*3600);
    $errors = true;
}

setcookie('fio_value', $fio, time() + 365*24*3600);
setcookie('phone_value', $phone, time() + 365*24*3600);
setcookie('email_value', $email, time() + 365*24*3600);
setcookie('birth_value', $birth, time() + 365*24*3600);
setcookie('gender_value', $gender, time() + 365*24*3600);
setcookie('languages_value', implode(',', $languages), time() + 365*24*3600);
setcookie('bio_value', $bio, time() + 365*24*3600);

if ($errors) {
    $redir = isset($_SESSION['admin_mode']) ? "index.php?edit_id=".$_SESSION['user_id'] : "index.php";
    header("Location: $redir");
    exit();
}

foreach(['fio','phone','email','birth','gender','languages','bio','contract'] as $f) {
    setcookie($f.'_value', '', 100000);
}

if (isset($_SESSION['user_id'])) {
    $id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("UPDATE applications SET fio=?, phone=?, email=?, birth_date=?, gender=?, biography=? WHERE id=?");
    $stmt->execute([$fio, $phone, $email, $birth, $gender, $bio, $id]);

    $pdo->prepare("DELETE FROM application_languages WHERE application_id=?")->execute([$id]);
    $stmt_l = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
    foreach ($languages as $l_id) { 
        $stmt_l->execute([$id, $l_id]); 
    }
    
    if (isset($_SESSION['admin_mode'])) {
        setcookie('save_success', 'Данные обновлены админом', time() + 24*3600);
        unset($_SESSION['admin_mode']);
        unset($_SESSION['user_id']);
        header("Location: admin.php");
    } else {
        setcookie('save_success', 'Данные успешно обновлены!', time() + 24*3600);
        header("Location: index.php");
    }
} else {
    $login = 'user' . rand(1000, 9999);
    $pass = bin2hex(random_bytes(4));
    $hash = password_hash($pass, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO applications (fio, phone, email, birth_date, gender, biography, login, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$fio, $phone, $email, $birth, $gender, $bio, $login, $hash]);
    $id = $pdo->lastInsertId();

    $stmt_l = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
    foreach ($languages as $l_id) {
        $stmt_l->execute([$id, $l_id]);
    }

    setcookie('save_success', 'Данные сохранены! Логин: ' . $login . ' Пароль: ' . $pass, time() + 24*3600);
    header("Location: index.php");
}
exit();
