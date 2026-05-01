<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']  = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];
        setFlash('success', 'Welcome back, ' . $user['username'] . '!');
        header('Location: ' . APP_URL . '/index.php');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}

$pageTitle = 'Sign In';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
.pw-wrap { position: relative; }
.pw-wrap input { padding-right: 2.75rem; }
.pw-toggle {
  position: absolute; right: .75rem; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer; padding: 0;
  color: #475569; transition: color .2s; line-height: 0;
}
.pw-toggle:hover { color: #818cf8; }
</style>

<div class="max-w-md mx-auto fade-up">
  <a href="<?= APP_URL ?>/index.php" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-300 mb-8 transition-colors">
    ← Back to Home
  </a>

  <div class="card p-8">
    <div class="text-center mb-8">
      <div class="w-12 h-12 rounded-xl mx-auto mb-4 flex items-center justify-center"
           style="background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.35);">
        <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-white" style="font-family:'Syne',sans-serif;">Welcome Back</h1>
      <p class="text-slate-500 text-sm mt-1">Sign in to your ScamGuard account</p>
    </div>

    <?php if ($error): ?>
      <div class="border border-red-500/30 bg-red-500/10 rounded-lg p-3 mb-6 text-red-300 text-sm"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-slate-300 mb-1.5">Email</label>
        <input type="email" name="email" class="input-field"
               value="<?= h($_POST['email'] ?? '') ?>"
               placeholder="you@email.com" required autofocus>
      </div>

      <div>
        <div class="flex justify-between items-center mb-1.5">
          <label class="block text-sm font-medium text-slate-300">Password</label>
          <a href="<?= APP_URL ?>/auth/forgot_password.php" class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors">Forgot?</a>
        </div>
        <div class="pw-wrap">
          <input type="password" name="password" id="pw-login"
                 class="input-field" placeholder="••••••••" required>
          <button type="button" class="pw-toggle" onclick="togglePw('pw-login', this)" aria-label="Show password">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-primary w-full py-2.5 text-base mt-1">Sign In</button>
    </form>

    <div class="mt-6 pt-6 border-t border-slate-800 text-center">
      <p class="text-sm text-slate-500">
        No account yet?
        <a href="<?= APP_URL ?>/auth/register.php" class="text-indigo-400 hover:text-indigo-300 transition-colors">Register free</a>
      </p>
    </div>
  </div>
</div>

<script>
const EYE_OPEN = `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
</svg>`;

const EYE_CLOSED = `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
</svg>`;

function togglePw(inputId, btn) {
  const input   = document.getElementById(inputId);
  const showing = input.type === 'text';
  input.type    = showing ? 'password' : 'text';
  btn.innerHTML = showing ? EYE_OPEN : EYE_CLOSED;
  btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
