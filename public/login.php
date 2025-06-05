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

$page_title = 'Login';
include 'templates/header.php';
?>
<h1>Login</h1>
<?php if ($error): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>
<form method="post" class="mt-3" style="max-width:400px;">
    <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Login</button>
</form>
<?php include 'templates/footer.php'; ?>
