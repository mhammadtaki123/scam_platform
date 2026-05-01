<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$errors = [];
$success = '';

// Add shop
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_shop') {
    $name     = trim($_POST['shop_name'] ?? '');
    $url      = trim($_POST['website_url'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');

    if (strlen($name) < 2) $errors[] = 'Shop name too short.';

    if (!$errors) {
        $pdo->prepare("INSERT INTO shops (shop_name,website_url,description,category) VALUES (?,?,?,?)")
            ->execute([$name, $url, $desc, $category]);
        $newId = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO risk_scores (shop_id,risk_score) VALUES (?,0)")->execute([$newId]);
        setFlash('success', 'Shop "' . $name . '" added.');
        header('Location: ' . APP_URL . '/admin/shops.php'); exit;
    }
}

// Delete shop
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM shops WHERE shop_id=?")->execute([(int)$_GET['delete']]);
    setFlash('success', 'Shop deleted.');
    header('Location: ' . APP_URL . '/admin/shops.php'); exit;
}

$shops = $pdo->query("
    SELECT s.*, COALESCE(rs.risk_score,0) as risk_score,
           (SELECT COUNT(*) FROM reviews r WHERE r.shop_id=s.shop_id) as review_count,
           (SELECT COUNT(*) FROM reports rp WHERE rp.shop_id=s.shop_id) as report_count
    FROM shops s LEFT JOIN risk_scores rs ON s.shop_id=rs.shop_id
    ORDER BY s.shop_name
")->fetchAll();

$pageTitle = 'Manage Shops';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="fade-up">
  <div class="flex items-center gap-4 mb-8">
    <a href="<?= APP_URL ?>/admin/dashboard.php" class="text-sm text-slate-500 hover:text-slate-300">← Dashboard</a>
    <h1 class="text-3xl font-bold text-white" style="font-family:'Syne',sans-serif;">Manage Shops</h1>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Add Shop form -->
    <div class="card p-6">
      <h2 class="text-lg font-bold text-white mb-5" style="font-family:'Syne',sans-serif;">Add New Shop</h2>
      <?php if ($errors): ?>
        <div class="border border-red-500/30 bg-red-500/10 rounded-lg p-3 mb-4">
          <?php foreach($errors as $e): ?><p class="text-red-300 text-sm"><?= h($e) ?></p><?php endforeach; ?>
        </div>
      <?php endif; ?>
      <form method="POST" class="space-y-3">
        <input type="hidden" name="action" value="add_shop">
        <div>
          <label class="block text-xs font-medium text-slate-400 mb-1">Shop Name *</label>
          <input type="text" name="shop_name" class="input-field text-sm" required placeholder="e.g. TechDeals Pro">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-400 mb-1">Website URL</label>
          <input type="url" name="website_url" class="input-field text-sm" placeholder="https://...">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-400 mb-1">Category</label>
          <input type="text" name="category" class="input-field text-sm" placeholder="Electronics, Fashion…">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-400 mb-1">Description</label>
          <textarea name="description" class="input-field text-sm resize-none" rows="3" placeholder="Brief shop description…"></textarea>
        </div>
        <button type="submit" class="btn-primary w-full text-sm">+ Add Shop</button>
      </form>
    </div>

    <!-- Shops list -->
    <div class="lg:col-span-2 card p-6">
      <h2 class="text-lg font-bold text-white mb-5" style="font-family:'Syne',sans-serif;">All Shops (<?= count($shops) ?>)</h2>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-800 text-left">
              <th class="pb-3 font-semibold text-slate-400">Shop</th>
              <th class="pb-3 font-semibold text-slate-400 text-center">Risk</th>
              <th class="pb-3 font-semibold text-slate-400 text-center">Reviews</th>
              <th class="pb-3 font-semibold text-slate-400 text-center">Reports</th>
              <th class="pb-3 font-semibold text-slate-400"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800">
            <?php foreach ($shops as $s):
              $risk = riskLabel($s['risk_score']);
            ?>
            <tr class="hover:bg-white/2 transition-colors">
              <td class="py-3 pr-4">
                <div class="font-medium text-slate-200"><?= h($s['shop_name']) ?></div>
                <div class="text-xs text-slate-600"><?= h($s['category'] ?? '') ?></div>
              </td>
              <td class="py-3 text-center">
                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold <?= $risk['bg'] ?> <?= $risk['text'] ?> border <?= $risk['border'] ?>">
                  <?= round($s['risk_score']) ?>
                </span>
              </td>
              <td class="py-3 text-center text-slate-400"><?= $s['review_count'] ?></td>
              <td class="py-3 text-center text-slate-400"><?= $s['report_count'] ?></td>
              <td class="py-3 text-right">
                <div class="flex justify-end gap-2">
                  <a href="<?= APP_URL ?>/shops/view.php?id=<?= $s['shop_id'] ?>" class="btn-ghost text-xs py-1 px-2">View</a>
                  <a href="?delete=<?= $s['shop_id'] ?>" onclick="return confirm('Delete <?= h(addslashes($s['shop_name'])) ?>?')"
                     class="btn-danger text-xs py-1 px-2">Del</a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
