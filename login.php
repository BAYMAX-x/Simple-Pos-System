<?php
session_start();
include 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $stored = $user['password'];
        $info = password_get_info($stored);
        $isHashed = ($info['algo'] ?? 0) !== 0; // 0 means unknown / not a password_hash

        // Case 1: Already hashed passwords
        if ($isHashed) {
            if (password_verify($password, $stored)) {
                // Upgrade hash if needed
                if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upd->bind_param("si", $newHash, $user['id']);
                    $upd->execute();
                }
                $_SESSION['user_id'] = $user['id'];
                header("Location: dashboard.php");
                exit();
            }
        } else {
            // Case 2: Legacy plaintext stored password — migrate on successful login
            if ($password === $stored) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->bind_param("si", $newHash, $user['id']);
                $upd->execute();
                $_SESSION['user_id'] = $user['id'];
                header("Location: dashboard.php");
                exit();
            }
        }

        // If neither hashed verify nor legacy match succeeded
        $error = "Invalid username or password.";
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
</head>
<body class="auth-page">
    <div class="login-container">
        <div class="login-card-body card">
            <div class="text-center mb-2">
                <div class="rounded-circle bg-primary bg-gradient d-inline-flex align-items-center justify-content-center" style="width:52px;height:52px;">
                    <i class="bi bi-shield-lock-fill text-white fs-4"></i>
                </div>
            </div>
            <h2 class="card-title mb-1 text-center">Welcome back</h2>
            <p class="text-muted text-center mb-3">Sign in to your account</p>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" class="mt-2">
                <div class="input-group mb-2">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Username" autocomplete="username" required>
                </div>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input id="password" type="password" name="password" class="form-control" placeholder="Password" autocomplete="current-password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePw" tabindex="-1" aria-label="Show password"><i class="bi bi-eye"></i></button>
                </div>
                <button type="submit" class="btn btn-primary w-100">Sign In</button>
            </form>
        </div>
    </div>
    <script>
        const pw = document.getElementById('password');
        const btn = document.getElementById('togglePw');
        btn?.addEventListener('click', () => {
            const is = pw.type === 'password';
            pw.type = is ? 'text' : 'password';
            btn.innerHTML = is ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
            btn.setAttribute('aria-label', is ? 'Hide password' : 'Show password');
        });
    </script>
</body>
</html>
