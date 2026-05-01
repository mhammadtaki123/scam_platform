<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (strlen($username) < 3 || strlen($username) > 50)
        $errors[] = 'Username must be 3–50 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Invalid email address.';
    if (strlen($password) < 8)
        $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $password2)
        $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (username,email,password) VALUES (?,?,?)")
                ->execute([$username, $email, $hash]);
            setFlash('success', 'Account created! Please sign in.');
            header('Location: ' . APP_URL . '/auth/login.php');
            exit;
        }
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-md mx-auto fade-up">
  <!-- Back link -->
  <a href="<?= APP_URL ?>/index.php" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-300 mb-8 transition-colors">
    ← Back to Home
  </a>

  <div class="card p-8">
    <div class="text-center mb-8">
      <div class="w-12 h-12 rounded-xl mx-auto mb-4 flex items-center justify-center"
           style="background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.35);">
        <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-white" style="font-family:'Syne',sans-serif;">Create Account</h1>
      <p class="text-slate-500 text-sm mt-1">Join ScamGuard and help protect consumers</p>
    </div>

    <?php if ($errors): ?>
      <div class="border border-red-500/30 bg-red-500/10 rounded-lg p-3 mb-6">
        <?php foreach ($errors as $e): ?>
          <p class="text-red-300 text-sm"><?= h($e) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-slate-300 mb-1.5">Username</label>
        <input type="text" name="username" class="input-field"
               value="<?= h($_POST['username'] ?? '') ?>"
               placeholder="yourname" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-300 mb-1.5">Email</label>
        <input type="email" name="email" class="input-field"
               value="<?= h($_POST['email'] ?? '') ?>"
               placeholder="you@email.com" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-300 mb-1.5">Password</label>
        <input type="password" name="password" class="input-field"
               placeholder="Min. 8 characters" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-300 mb-1.5">Confirm Password</label>
        <input type="password" name="password2" class="input-field"
               placeholder="Repeat password" required>
      </div>

      <button type="submit" class="btn-primary w-full mt-2 py-2.5 text-base">Create Account</button>
    </form>

    <p class="text-center text-sm text-slate-500 mt-6">
      Already have an account?
      <a href="<?= APP_URL ?>/auth/login.php" class="text-indigo-400 hover:text-indigo-300 transition-colors">Sign in</a>
    </p>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
