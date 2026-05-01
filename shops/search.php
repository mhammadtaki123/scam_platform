<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$q        = trim($_GET['q']        ?? '');
$category = trim($_GET['category'] ?? '');
$sort     = $_GET['sort'] ?? 'risk_desc';

$pageTitle = 'Search Shops';

// Build query
$where  = [];
$params = [];

if ($q) {
    $where[]  = "(s.shop_name LIKE ? OR s.description LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($category) {
    $where[]  = "s.category = ?";
    $params[] = $category;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$orderMap = [
    'risk_desc'   => 'r.risk_score DESC',
    'risk_asc'    => 'r.risk_score ASC',
    'name_asc'    => 's.shop_name ASC',
    'rating_desc' => 'r.rating_avg DESC',
];
$orderBy = $orderMap[$sort] ?? 'r.risk_score DESC';

$shops = $pdo->prepare("
    SELECT s.*, COALESCE(r.risk_score,0) as risk_score,
           COALESCE(r.rating_avg,0) as rating_avg,
           COALESCE(r.report_count,0) as report_count
    FROM shops s LEFT JOIN risk_scores r ON s.shop_id=r.shop_id
    $whereSQL
    ORDER BY $orderBy
");
$shops->execute($params);
$shops = $shops->fetchAll();

// Categories for filter
$categories = $pdo->query("SELECT DISTINCT category FROM shops WHERE category IS NOT NULL ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="fade-up">
  <div class="mb-8">
    <h1 class="text-3xl font-bold text-white" style="font-family:'Syne',sans-serif;">Search Shops</h1>
    <p class="text-slate-500 mt-1">Find and verify online shops before you buy</p>
  </div>

  <!-- Filters -->
  <form method="GET" class="card p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-48">
      <label class="block text-xs font-medium text-slate-400 mb-1.5">Search</label>
      <input type="text" name="q" class="input-field" placeholder="Shop name or keyword…"
             value="<?= h($q) ?>">
    </div>
    <div class="min-w-40">
      <label class="block text-xs font-medium text-slate-400 mb-1.5">Category</label>
      <select name="category" class="input-field">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= h($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= h($cat) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="min-w-40">
      <label class="block text-xs font-medium text-slate-400 mb-1.5">Sort By</label>
      <select name="sort" class="input-field">
        <option value="risk_desc"   <?= $sort==='risk_desc'   ? 'selected':'' ?>>Highest Risk</option>
        <option value="risk_asc"    <?= $sort==='risk_asc'    ? 'selected':'' ?>>Lowest Risk</option>
        <option value="name_asc"    <?= $sort==='name_asc'    ? 'selected':'' ?>>Name (A-Z)</option>
        <option value="rating_desc" <?= $sort==='rating_desc' ? 'selected':'' ?>>Best Rated</option>
      </select>
    </div>
    <div class="flex gap-2">
      <button type="submit" class="btn-primary">Search</button>
      <a href="<?= APP_URL ?>/shops/search.php" class="btn-ghost">Clear</a>
    </div>
  </form>

  <!-- Results count -->
  <p class="text-slate-500 text-sm mb-4">
    <?= count($shops) ?> shop<?= count($shops) !== 1 ? 's' : '' ?> found
    <?= $q ? ' for <span class="text-slate-300">"' . h($q) . '"</span>' : '' ?>
  </p>

  <!-- Results grid -->
  <?php if (!$shops): ?>
    <div class="card p-12 text-center text-slate-500">
      <div class="text-4xl mb-3">🔍</div>
      <p class="font-medium text-slate-400">No shops found</p>
      <p class="text-sm mt-1">Try a different search term or category</p>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <?php foreach ($shops as $i => $shop):
        $risk = riskLabel((float)$shop['risk_score']);
      ?>
      <a href="<?= APP_URL ?>/shops/view.php?id=<?= $shop['shop_id'] ?>"
         class="card p-5 hover:border-indigo-500/30 transition-all duration-200 hover:-translate-y-0.5 block"
         style="animation-delay:<?= $i*40 ?>ms">

        <div class="flex items-start justify-between mb-3">
          <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-white truncate"><?= h($shop['shop_name']) ?></h3>
            <p class="text-slate-500 text-xs mt-0.5"><?= h($shop['category'] ?? 'General') ?></p>
          </div>
          <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-semibold <?= $risk['bg'] ?> <?= $risk['text'] ?> border <?= $risk['border'] ?> shrink-0">
            <?= $risk['label'] ?>
          </span>
        </div>

        <?php if ($shop['description']): ?>
          <p class="text-slate-400 text-xs leading-relaxed line-clamp-2 mb-3"><?= h($shop['description']) ?></p>
        <?php endif; ?>

        <!-- Risk bar -->
        <div class="mb-3">
          <div class="flex justify-between text-xs text-slate-500 mb-1.5">
            <span>Risk Score</span>
            <span class="font-semibold <?= $risk['text'] ?>"><?= round($shop['risk_score']) ?>/100</span>
          </div>
          <div class="h-1.5 rounded-full bg-slate-700">
            <?php $barColor = $shop['risk_score']>60?'#ef4444':($shop['risk_score']>30?'#f59e0b':'#22c55e'); ?>
            <div class="h-1.5 rounded-full" style="width:<?= min(100,max(0,$shop['risk_score'])) ?>%;background:<?= $barColor ?>;"></div>
          </div>
        </div>

        <div class="flex items-center justify-between text-xs text-slate-500 pt-3 border-t border-slate-800">
          <?php if ($shop['rating_avg'] > 0): ?>
            <div class="flex items-center gap-1">
              <?= starRating($shop['rating_avg']) ?>
              <span><?= round($shop['rating_avg'], 1) ?></span>
            </div>
          <?php else: ?>
            <span class="text-slate-600">No ratings</span>
          <?php endif; ?>
          <span><?= $shop['report_count'] ?> report<?= $shop['report_count'] != 1 ? 's' : '' ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
