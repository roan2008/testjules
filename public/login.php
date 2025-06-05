<?php
session_start();

require_once __DIR__ . '/../src/Database.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $pdo = Database::connect();
    $stmt = $pdo->prepare('SELECT UserID, PasswordHash, Role FROM Users WHERE Username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['PasswordHash'])) {
        $_SESSION['UserID'] = $user['UserID'];
        $_SESSION['Role'] = $user['Role'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Login</h1>
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="post">
        <label>Username: <input type="text" name="username" required></label><br><br>
        <label>Password: <input type="password" name="password" required></label><br><br>
        <button type="submit">Login</button>
    </form>
</body>
</html>
