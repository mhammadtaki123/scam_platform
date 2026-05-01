<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

// Stats
$stats = [
    'total_users'    => $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
    'total_shops'    => $pdo->query("SELECT COUNT(*) FROM shops")->fetchColumn(),
    'pending_reports'=> $pdo->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn(),
    'approved_reports'=> $pdo->query("SELECT COUNT(*) FROM reports WHERE status='approved'")->fetchColumn(),
];

// Recent pending reports
$pending = $pdo->query("
    SELECT r.*, u.username, s.shop_name
    FROM reports r
    JOIN users u ON r.user_id=u.user_id
    JOIN shops s ON r.shop_id=s.shop_id
    WHERE r.status='pending'
    ORDER BY r.created_at DESC
    LIMIT 10
")->fetchAll();

// High risk shops
$highRisk = $pdo->query("
    SELECT s.*, rs.risk_score FROM shops s
    JOIN risk_scores rs ON s.shop_id=rs.shop_id
    WHERE rs.risk_score > 60
    ORDER BY rs.risk_score DESC
")->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="fade-up">
  <div class="flex items-center justify-between mb-8">
    <div>
      <h1 class="text-3xl font-bold text-white" style="font-family:'Syne',sans-serif;">⚡ Admin Dashboard</h1>
      <p class="text-slate-500 mt-1">Platform overview and moderation queue</p>
    </div>
    <div class="flex gap-2">
      <a href="<?= APP_URL ?>/admin/analytics.php" class="btn-ghost text-sm">Analytics</a>
      <a href="<?= APP_URL ?>/admin/settings.php" class="btn-ghost text-sm">Settings</a>
      <a href="<?= APP_URL ?>/admin/claims.php" class="btn-ghost text-sm">Manage Claims</a>
      <a href="<?= APP_URL ?>/admin/shops.php" class="btn-ghost text-sm">Manage Shops</a>
    </div>
  </div>

  <!-- Stats grid -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <?php foreach ([
        ['Users',            $stats['total_users'],     'text-indigo-400', 'bg-indigo-500/15', '👥'],
        ['Shops',            $stats['total_shops'],     'text-blue-400',   'bg-blue-500/15',   '🏪'],
        ['Pending Reports',  $stats['pending_reports'], 'text-amber-400',  'bg-amber-500/15',  '⏳'],
        ['Approved Reports', $stats['approved_reports'],'text-green-400',  'bg-green-500/15',  '✅'],
    ] as [$label, $value, $textCls, $bgCls, $icon]): ?>
      <div class="card p-5">
        <div class="flex items-center justify-between mb-3">
          <span class="text-sm text-slate-500"><?= $label ?></span>
          <div class="w-8 h-8 rounded-lg <?= $bgCls ?> flex items-center justify-center text-sm"><?= $icon ?></div>
        </div>
        <div class="text-3xl font-bold <?= $textCls ?>" style="font-family:'Syne',sans-serif;"><?= number_format((int)$value) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Pending reports -->
    <div class="lg:col-span-2 card p-6">
      <div class="flex items-center justify-between mb-5">
        <h2 class="text-lg font-bold text-white" style="font-family:'Syne',sans-serif;">
          Pending Reports
          <?php if ($stats['pending_reports']): ?>
            <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-amber-500/20 text-amber-400 border border-amber-500/30"><?= $stats['pending_reports'] ?></span>
          <?php endif; ?>
        </h2>
        <a href="<?= APP_URL ?>/admin/moderate.php" class="text-sm text-indigo-400 hover:text-indigo-300 transition-colors">View All →</a>
      </div>

      <?php if (!$pending): ?>
        <div class="text-center py-10 text-slate-600">
          <div class="text-4xl mb-2">✅</div>
          <p>No pending reports — you're all caught up!</p>
        </div>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($pending as $rep): ?>
            <div class="border border-slate-700 rounded-lg p-4 hover:border-slate-600 transition-colors">
              <div class="flex items-start justify-between gap-2 mb-2">
                <div>
                  <span class="text-sm font-medium text-slate-200"><?= h($rep['shop_name']) ?></span>
                  <span class="text-slate-600 text-xs"> by <?= h($rep['username']) ?></span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <?php if ($rep['ai_score'] !== null): ?>
                    <?php $aiColor = $rep['ai_score'] > .7 ? 'text-red-400 border-red-500/30 bg-red-500/10'
                                  : ($rep['ai_score'] > .4  ? 'text-amber-400 border-amber-500/30 bg-amber-500/10'
                                  :                          'text-green-400 border-green-500/30 bg-green-500/10'); ?>
                    <span class="text-xs px-2 py-0.5 rounded-full border <?= $aiColor ?>">
                      AI: <?= round($rep['ai_score']*100) ?>%
                    </span>
                  <?php endif; ?>
                  <span class="text-xs text-slate-600"><?= timeAgo($rep['created_at']) ?></span>
                </div>
              </div>
              <p class="text-slate-400 text-sm line-clamp-2 mb-3"><?= h($rep['description']) ?></p>
              <div class="flex gap-2">
                <a href="<?= APP_URL ?>/admin/moderate.php?action=approve&id=<?= $rep['report_id'] ?>&token=<?= $_SESSION['user_id'] ?>"
                   class="btn-primary text-xs py-1 px-3">✓ Approve</a>
                <a href="<?= APP_URL ?>/admin/moderate.php?action=reject&id=<?= $rep['report_id'] ?>&token=<?= $_SESSION['user_id'] ?>"
                   class="btn-ghost text-xs py-1 px-3 text-red-400 border-red-500/30">✗ Reject</a>
                <a href="<?= APP_URL ?>/shops/view.php?id=<?= $rep['shop_id'] ?>"
                   class="btn-ghost text-xs py-1 px-3 ml-auto">View Shop</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- High risk shops -->
    <div class="card p-6">
      <h2 class="text-lg font-bold text-white mb-5" style="font-family:'Syne',sans-serif;">🔴 High Risk Shops</h2>
      <?php if (!$highRisk): ?>
        <p class="text-slate-600 text-sm text-center py-6">No high-risk shops currently.</p>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($highRisk as $s): ?>
            <a href="<?= APP_URL ?>/shops/view.php?id=<?= $s['shop_id'] ?>"
               class="flex items-center justify-between p-3 rounded-lg border border-slate-700 hover:border-red-500/30 transition-colors block">
              <span class="text-sm text-slate-300 truncate"><?= h($s['shop_name']) ?></span>
              <span class="text-sm font-bold text-red-400 shrink-0 ml-2"><?= round($s['risk_score']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
