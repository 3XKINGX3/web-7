<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $pass = $_POST['password'] ?? '';

    $db_user = 'u82373';
    $db_pass = '4362231';
    $pdo = new PDO('mysql:host=localhost;dbname=u82373;charset=utf8', $db_user, $db_pass);

    $stmt = $pdo->prepare("SELECT id, password_hash FROM applications WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($pass, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: index.php");
        exit();
    }
    $error = "Неверный логин или пароль";
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 300px; }
        h2 { margin-top: 0; text-align: center; color: #333; }
        input { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer; }
        .error { color: #dc3545; font-size: 13px; margin-bottom: 10px; text-align: center; }
        .back { display: block; text-align: center; margin-top: 15px; font-size: 13px; color: #666; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Вход</h2>
        <?php if(isset($error)): ?><div class="error"><?= $error ?></div><?php endif; ?>
        <form method="POST">
            <input name="login" placeholder="Логин" required>
            <input name="password" type="password" placeholder="Пароль" required>
            <button type="submit">Войти</button>
        </form>
        <a href="index.php" class="back">← На главную</a>
    </div>
</body>
</html>
