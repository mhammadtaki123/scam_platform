<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$preselected_shop = (int)($_GET['shop_id'] ?? 0);
$shops = $pdo->query("SELECT shop_id, shop_name FROM shops ORDER BY shop_name")->fetchAll();

$errors = [];
$aiResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_id    = (int)($_POST['shop_id']     ?? 0);
    $description = trim($_POST['description'] ?? '');

    // Validate
    if (!$shop_id)                $errors[] = 'Please select a shop.';
    if (strlen($description) < 30) $errors[] = 'Description must be at least 30 characters.';

    // File upload
    $evidence_path = null;
    if (!empty($_FILES['evidence']['name'])) {
        $file    = $_FILES['evidence'];
        $allowed = ['image/jpeg','image/png','image/gif','image/webp','application/pdf'];
        $maxSize = MAX_FILE_MB * 1024 * 1024;

        if (!in_array($file['type'], $allowed))
            $errors[] = 'Evidence must be an image (JPG, PNG, GIF, WEBP) or PDF.';
        elseif ($file['size'] > $maxSize)
            $errors[] = 'Evidence file must be under ' . MAX_FILE_MB . ' MB.';
        else {
            $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
            $name = uniqid('ev_', true) . '.' . strtolower($ext);
            if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) {
                $errors[] = 'File upload failed. Check uploads/ directory permissions.';
            } else {
                $evidence_path = $name;
            }
        }
    }

    if (!$errors) {
        // ── Run AI Analysis ──────────────────────────────────
        $aiResult = detectScamText($description);
        $aiScore  = $aiResult['score'];

        // Insert report
        $pdo->prepare("INSERT INTO reports (user_id,shop_id,description,evidence_path,ai_score) VALUES (?,?,?,?,?)")
            ->execute([$_SESSION['user_id'], $shop_id, $description, $evidence_path, $aiScore]);

        // Recalculate risk score
        computeRiskScore($pdo, $shop_id);

        // Notify all admins about the new report
        $admins = $pdo->query("SELECT user_id FROM users WHERE role = 'admin'")->fetchAll();
        $shopStmt = $pdo->prepare("SELECT shop_name FROM shops WHERE shop_id = ?");
        $shopStmt->execute([$shop_id]);
        $shop = $shopStmt->fetch();
        $shopName = h($shop['shop_name']);
        foreach ($admins as $admin) {
            createNotification(
                $pdo,
                $admin['user_id'],
                '🚨 New Report Submitted',
                "A new scam report was filed for \"{$shopName}\". Review it in the moderation queue.",
                APP_URL . '/admin/moderate.php'
            );
        }

        setFlash('success', 'Report submitted! It will be reviewed by an admin shortly.');
        header('Location: ' . APP_URL . '/shops/view.php?id=' . $shop_id);
        exit;
    }
}

$pageTitle = 'Submit Scam Report';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-2xl mx-auto fade-up">
  <a href="<?= APP_URL ?>/index.php" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-300 mb-8 transition-colors">
    ← Back
  </a>

  <div class="card p-8">
    <div class="mb-8">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
             style="background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);">
          <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
        </div>
        <div>
          <h1 class="text-2xl font-bold text-white" style="font-family:'Syne',sans-serif;">Report a Scam</h1>
          <p class="text-slate-500 text-sm">Your report will be analyzed by AI and reviewed by an admin</p>
        </div>
      </div>
    </div>

    <?php if ($errors): ?>
      <div class="border border-red-500/30 bg-red-500/10 rounded-lg p-4 mb-6">
        <?php foreach ($errors as $e): ?>
          <p class="text-red-300 text-sm"><?= h($e) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-5">

      <div>
        <label class="block text-sm font-medium text-slate-300 mb-1.5">Shop *</label>
        <select name="shop_id" class="input-field" required>
          <option value="">— Select a shop —</option>
          <?php foreach ($shops as $s): ?>
            <option value="<?= $s['shop_id'] ?>"
              <?= (($_POST['shop_id'] ?? $preselected_shop) == $s['shop_id']) ? 'selected' : '' ?>>
              <?= h($s['shop_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-300 mb-1.5">
          Report Description *
          <span class="text-slate-600 font-normal ml-1">(min. 30 characters)</span>
        </label>
        <textarea name="description" class="input-field resize-none" rows="6"
                  placeholder="Describe the scam in detail: what happened, what was promised, what you received, any suspicious behaviour…"
                  required minlength="30"><?= h($_POST['description'] ?? '') ?></textarea>
        <p class="text-xs text-slate-600 mt-1">Your description will be analyzed by our AI scam detector.</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-300 mb-1.5">
          Evidence <span class="text-slate-600 font-normal">(optional — image or PDF, max <?= MAX_FILE_MB ?>MB)</span>
        </label>
        <div class="border border-dashed border-slate-700 rounded-lg p-5 text-center hover:border-indigo-500/40 transition-colors">
          <input type="file" name="evidence" id="evidence"
                 accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" class="sr-only">
          <label for="evidence" class="cursor-pointer">
            <div class="text-3xl mb-2">📎</div>
            <p class="text-sm text-slate-400">Click to upload evidence</p>
            <p class="text-xs text-slate-600 mt-1">JPG, PNG, PDF up to <?= MAX_FILE_MB ?>MB</p>
          </label>
          <p id="file-name" class="text-xs text-indigo-400 mt-2 hidden"></p>
        </div>
      </div>

      <!-- AI notice -->
      <div class="flex items-start gap-3 p-4 rounded-lg border border-indigo-500/20 bg-indigo-500/5">
        <span class="text-xl">🤖</span>
        <div>
          <p class="text-sm font-medium text-indigo-300">AI-Powered Analysis</p>
          <p class="text-xs text-slate-500 mt-0.5">
            When you submit, our AI will instantly analyze your description for scam indicators.
            The result contributes to this shop's overall risk score.
          </p>
        </div>
      </div>

      <button type="submit" class="btn-danger w-full py-3 text-base">
        🚨 Submit Report
      </button>
    </form>
  </div>
</div>

<script>
document.getElementById('evidence').addEventListener('change', function() {
  const label = document.getElementById('file-name');
  if (this.files[0]) {
    label.textContent = '✓ ' + this.files[0].name;
    label.classList.remove('hidden');
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
