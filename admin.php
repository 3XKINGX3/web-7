<?php
if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $auth_data = explode(':', base64_decode(substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 6)));
    $_SERVER['PHP_AUTH_USER'] = $auth_data[0];
    $_SERVER['PHP_AUTH_PW'] = $auth_data[1];
}

$db_user = 'u82373';
$db_pass = '4362231';
$pdo = new PDO('mysql:host=localhost;dbname=u82373;charset=utf8', $db_user, $db_pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$auth_success = false;
if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
    $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE login = ?");
    $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
    $admin = $stmt->fetch();

    if ($admin && md5($_SERVER['PHP_AUTH_PW']) == $admin['password_hash']) {
        $auth_success = true;
    }
}

if (!$auth_success) {
  header('HTTP/1.1 401 Unauthorized');
  header('WWW-Authenticate: Basic realm="Admin Panel"');
  print('<h1>401 Требуется авторизация</h1>');
  exit();
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM applications WHERE id = ?")->execute([$id]);
    header('Location: admin.php');
    exit();
}

$stats = $pdo->query("SELECT l.name, COUNT(al.application_id) as count FROM languages l LEFT JOIN application_languages al ON l.id = al.language_id GROUP BY l.id")->fetchAll();
$users = $pdo->query("SELECT * FROM applications")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админка</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background-color: #eee; }
        .stats { margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Панель администратора</h1>
    <div class="stats">
        <h3>Статистика по языкам:</h3>
        <?php foreach ($stats as $s): ?>
            <div><?= htmlspecialchars($s['name']) ?>: <?= $s['count'] ?></div>
        <?php endforeach; ?>
    </div>
    <table>
        <tr>
            <th>ID</th>
            <th>ФИО</th>
            <th>Email</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['fio']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
                <a href="index.php?edit_id=<?= $u['id'] ?>">Редактировать</a> | 
                <a href="?delete=<?= $u['id'] ?>" onclick="return confirm('Удалить?')">Удалить</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <br>
    <a href="index.php">К форме</a>
</body>
</html>
