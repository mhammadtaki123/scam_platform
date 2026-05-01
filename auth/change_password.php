<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!password_verify($current, $user['password']))
        $errors[] = 'Current password is incorrect.';
    if (strlen($new) < 8)
        $errors[] = 'New password must be at least 8 characters.';
    if ($new === $current)
        $errors[] = 'New password must be different from your current password.';
    if ($new !== $confirm)
        $errors[] = 'New passwords do not match.';

    if (!$errors) {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?")
            ->execute([$hash, $_SESSION['user_id']]);
        $success = true;
    }
}

$pageTitle = 'Change Password';
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
                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0
                   01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-white" style="font-family:'Syne',sans-serif;">Change Password</h1>
      <p class="text-slate-500 text-sm mt-1">
        Signed in as <span class="text-slate-300 font-medium"><?= h($_SESSION['username']) ?></span>
      </p>
    </div>

    <?php if ($success): ?>
      <div class="border border-green-500/30 bg-green-500/10 rounded-lg p-4 text-center">
        <div class="text-3xl mb-2">✅</div>
        <p class="text-green-300 font-medium">Password updated successfully!</p>
        <a href="<?= APP_URL ?>/index.php" class="inline-block mt-4 btn-primary text-sm">Back to Home</a>
      </div>
    <?php else: ?>

      <?php if ($errors): ?>
        <div class="border border-red-500/30 bg-red-500/10 rounded-lg p-4 mb-6">
          <?php foreach ($errors as $e): ?>
            <p class="text-red-300 text-sm"><?= h($e) ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-4">

        <!-- Current password -->
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1.5">Current Password</label>
          <div class="pw-wrap">
            <input type="password" name="current_password" id="pw-current"
                   class="input-field" placeholder="••••••••" required autofocus>
            <button type="button" class="pw-toggle" onclick="togglePw('pw-current', this)" aria-label="Show password">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- New password -->
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1.5">New Password</label>
          <div class="pw-wrap">
            <input type="password" name="new_password" id="pw-new"
                   class="input-field" placeholder="Min. 8 characters" required minlength="8">
            <button type="button" class="pw-toggle" onclick="togglePw('pw-new', this)" aria-label="Show password">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </button>
          </div>
          <!-- Strength bar -->
          <div class="mt-2 h-1 rounded-full bg-slate-700 overflow-hidden">
            <div id="strength-bar" class="h-1 rounded-full transition-all duration-300" style="width:0%"></div>
          </div>
          <p id="strength-label" class="text-xs text-slate-600 mt-1 h-4"></p>
        </div>

        <!-- Confirm password -->
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1.5">Confirm New Password</label>
          <div class="pw-wrap">
            <input type="password" name="confirm_password" id="pw-confirm"
                   class="input-field" placeholder="Repeat new password" required>
            <button type="button" class="pw-toggle" onclick="togglePw('pw-confirm', this)" aria-label="Show password">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </button>
          </div>
          <p id="match-label" class="text-xs mt-1 h-4"></p>
        </div>

        <button type="submit" class="btn-primary w-full py-2.5 text-base mt-2">Update Password</button>
      </form>

    <?php endif; ?>
  </div>
</div>

<script>
// ── SVG templates ────────────────────────────────────────────
const EYE_OPEN = `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
</svg>`;

const EYE_CLOSED = `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
</svg>`;

// ── Toggle function (shared by all fields) ────────────────────
function togglePw(inputId, btn) {
  const input   = document.getElementById(inputId);
  const showing = input.type === 'text';
  input.type    = showing ? 'password' : 'text';
  btn.innerHTML = showing ? EYE_OPEN : EYE_CLOSED;
  btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
}

// ── Strength meter ────────────────────────────────────────────
const pwNew    = document.getElementById('pw-new');
const pwConf   = document.getElementById('pw-confirm');
const bar      = document.getElementById('strength-bar');
const barLbl   = document.getElementById('strength-label');
const matchLbl = document.getElementById('match-label');

pwNew.addEventListener('input', () => {
  const v = pwNew.value;
  let score = 0;
  if (v.length >= 8)          score++;
  if (/[A-Z]/.test(v))        score++;
  if (/[0-9]/.test(v))        score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;

  const levels = [
    { pct: '0%',   color: 'transparent', label: ''        },
    { pct: '25%',  color: '#ef4444',     label: 'Weak'    },
    { pct: '50%',  color: '#f59e0b',     label: 'Fair'    },
    { pct: '75%',  color: '#3b82f6',     label: 'Good'    },
    { pct: '100%', color: '#22c55e',     label: 'Strong'  },
  ];
  const lvl = levels[score] || levels[0];
  bar.style.width      = lvl.pct;
  bar.style.background = lvl.color;
  barLbl.textContent   = lvl.label;
  barLbl.style.color   = lvl.color;

  checkMatch();
});

pwConf.addEventListener('input', checkMatch);

function checkMatch() {
  if (!pwConf.value) { matchLbl.textContent = ''; return; }
  if (pwNew.value === pwConf.value) {
    matchLbl.textContent = '✓ Passwords match';
    matchLbl.style.color = '#22c55e';
  } else {
    matchLbl.textContent = '✗ Passwords do not match';
    matchLbl.style.color = '#ef4444';
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
