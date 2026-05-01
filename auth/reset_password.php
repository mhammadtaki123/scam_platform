<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$error = '';
$success = '';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

if (!$token || !$email) {
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}

// Validate token
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW()");
$stmt->execute([$email, $token]);
$reset = $stmt->fetch();

if (!$reset) {
    $error = 'Invalid or expired password reset link. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Update password
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")->execute([$hash, $email]);

        // Delete token
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

        $success = 'Password successfully updated! You can now sign in.';
    }
}

$pageTitle = 'Reset Password';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-md mx-auto fade-up">
  <div class="card p-8">
    <div class="text-center mb-8">
      <h1 class="text-2xl font-bold text-white" style="font-family:'Syne',sans-serif;">Set New Password</h1>
      <p class="text-slate-500 text-sm mt-1">For <?= h($email) ?></p>
    </div>

    <?php if ($error): ?>
      <div class="border border-red-500/30 bg-red-500/10 rounded-lg p-3 mb-6 text-red-300 text-sm"><?= h($error) ?></div>
      <?php if (strpos($error, 'Invalid') !== false): ?>
        <a href="<?= APP_URL ?>/auth/forgot_password.php" class="btn-primary w-full text-center block">Request New Link</a>
      <?php endif; ?>
    <?php endif; ?>
    
    <?php if ($success): ?>
      <div class="border border-green-500/30 bg-green-500/10 rounded-lg p-4 mb-6 text-center">
        <p class="text-green-300 text-sm font-medium mb-4"><?= h($success) ?></p>
        <a href="<?= APP_URL ?>/auth/login.php" class="btn-primary inline-block">Go to Login</a>
      </div>
    <?php elseif ($reset && !$success): ?>

      <form method="POST" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1.5">New Password</label>
          <input type="password" name="password" class="input-field" required minlength="8" autofocus>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1.5">Confirm Password</label>
          <input type="password" name="confirm_password" class="input-field" required minlength="8">
        </div>

        <button type="submit" class="btn-primary w-full py-2.5 text-base mt-2">Update Password</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
