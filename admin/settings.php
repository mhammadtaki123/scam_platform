<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wRating = (float)($_POST['weight_rating'] ?? 0.4);
    $wReports = (float)($_POST['weight_reports'] ?? 0.3);
    $wAi = (float)($_POST['weight_ai'] ?? 0.3);

    // Validate weights equal 1.0
    if (round($wRating + $wReports + $wAi, 2) !== 1.00) {
        $error = 'The sum of all weights must equal 1.0.';
    } else {
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'weight_rating'")->execute([$wRating]);
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'weight_reports'")->execute([$wReports]);
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'weight_ai'")->execute([$wAi]);
        
        $success = 'Settings updated successfully.';
    }
}

// Fetch current settings
$settingsMap = [];
$settingsRows = $pdo->query("SELECT * FROM settings")->fetchAll();
foreach ($settingsRows as $row) {
    $settingsMap[$row['setting_key']] = $row['setting_value'];
}

$wRating = (float)($settingsMap['weight_rating'] ?? 0.4);
$wReports = (float)($settingsMap['weight_reports'] ?? 0.3);
$wAi = (float)($settingsMap['weight_ai'] ?? 0.3);

$pageTitle = 'Platform Settings';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="fade-up">
  <div class="flex items-center gap-4 mb-8">
    <a href="<?= APP_URL ?>/admin/dashboard.php" class="text-sm text-slate-500 hover:text-slate-300">← Dashboard</a>
    <h1 class="text-3xl font-bold text-white" style="font-family:'Syne',sans-serif;">Platform Settings</h1>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Risk Score Weights -->
    <div class="card p-6">
      <h2 class="text-lg font-bold text-white mb-2" style="font-family:'Syne',sans-serif;">Risk Score Algorithm Weights</h2>
      <p class="text-sm text-slate-400 mb-6">Configure how heavily each factor influences a shop's final risk score out of 100. The values must add up to exactly 1.0.</p>

      <?php if ($error): ?>
        <div class="border border-red-500/30 bg-red-500/10 rounded-lg p-3 mb-6 text-red-300 text-sm"><?= h($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="border border-green-500/30 bg-green-500/10 rounded-lg p-3 mb-6 text-green-300 text-sm"><?= h($success) ?></div>
      <?php endif; ?>

      <form method="POST" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1.5">Rating Weight (e.g. 0.4 = 40%)</label>
          <input type="number" step="0.05" min="0" max="1" name="weight_rating" class="input-field" value="<?= $wRating ?>" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1.5">Reports Weight (e.g. 0.3 = 30%)</label>
          <input type="number" step="0.05" min="0" max="1" name="weight_reports" class="input-field" value="<?= $wReports ?>" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1.5">AI Analysis Weight (e.g. 0.3 = 30%)</label>
          <input type="number" step="0.05" min="0" max="1" name="weight_ai" class="input-field" value="<?= $wAi ?>" required>
        </div>

        <button type="submit" class="btn-primary w-full mt-2">Save Settings</button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
