<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$error = '';
$success = '';
$testLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Clear old tokens for this email
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

            // Insert new token
            $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)")
                ->execute([$email, $token, $expires]);

            // Simulate email send
            $resetUrl = APP_URL . "/auth/reset_password.php?token=$token&email=" . urlencode($email);
            
            $success = 'Password reset instructions have been sent to your email.';
            $testLink = $resetUrl; // For local testing
        } else {
            // To prevent email enumeration, show success even if email not found
            $success = 'If that email exists, instructions have been sent.';
        }
    }
}

$pageTitle = 'Forgot Password';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-md mx-auto fade-up">
  <a href="<?= APP_URL ?>/auth/login.php" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-300 mb-8 transition-colors">
    ← Back to Sign In
  </a>

  <div class="card p-8">
    <div class="text-center mb-8">
      <div class="w-12 h-12 rounded-xl mx-auto mb-4 flex items-center justify-center"
           style="background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.35);">
        <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-white" style="font-family:'Syne',sans-serif;">Reset Password</h1>
      <p class="text-slate-500 text-sm mt-1">Enter your email to receive reset instructions.</p>
    </div>

    <?php if ($error): ?>
      <div class="border border-red-500/30 bg-red-500/10 rounded-lg p-3 mb-6 text-red-300 text-sm"><?= h($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
      <div class="border border-green-500/30 bg-green-500/10 rounded-lg p-4 mb-6">
        <p class="text-green-300 text-sm font-medium"><?= h($success) ?></p>
        <?php if ($testLink): ?>
          <div class="mt-3 p-3 bg-slate-800/50 border border-slate-700 rounded text-xs break-all">
            <span class="text-amber-400 font-bold block mb-1">Local Testing Link:</span>
            <a href="<?= $testLink ?>" class="text-indigo-400 hover:underline"><?= $testLink ?></a>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>

      <form method="POST" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1.5">Email Address</label>
          <input type="email" name="email" class="input-field"
                 value="<?= h($_POST['email'] ?? '') ?>"
                 placeholder="you@email.com" required autofocus>
        </div>

        <button type="submit" class="btn-primary w-full py-2.5 text-base mt-2">Send Reset Link</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
