<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Home';

// Quick stats for hero
$totalShops   = $pdo->query("SELECT COUNT(*) FROM shops")->fetchColumn();
$totalReports = $pdo->query("SELECT COUNT(*) FROM reports WHERE status='approved'")->fetchColumn();
$totalUsers   = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();

// Featured shops with risk scores
$shops = $pdo->query("
    SELECT s.*, COALESCE(r.risk_score,0) as risk_score,
           COALESCE(r.rating_avg,0) as rating_avg,
           COALESCE(r.report_count,0) as report_count
    FROM shops s LEFT JOIN risk_scores r ON s.shop_id=r.shop_id
    ORDER BY r.risk_score DESC LIMIT 6
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Hero ──────────────────────────────────────────────────── -->
<section class="text-center py-14 fade-up">
  <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium mb-6"
       style="background:rgba(99,102,241,.12);border:1px solid rgba(99,102,241,.25);color:#818cf8;">
    <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 inline-block"></span>
    AI-Powered Scam Detection
  </div>
  <h1 class="text-5xl sm:text-6xl font-extrabold tracking-tight mb-5 leading-tight" style="font-family:'Syne',sans-serif;">
    Shop Smarter.<br>
    <span style="background:linear-gradient(135deg,#6366f1,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">Stay Protected.</span>
  </h1>
  <p class="text-slate-400 text-lg max-w-xl mx-auto mb-8 leading-relaxed">
    Verify online shops before you buy. Our AI analyzes reports, ratings, and scam patterns
    to give you an instant <strong class="text-slate-300">risk assessment</strong>.
  </p>

  <!-- Search bar -->
  <form action="<?= APP_URL ?>/shops/search.php" method="GET" class="flex gap-2 max-w-lg mx-auto">
    <input type="text" name="q" placeholder="Search for a shop name…"
           class="input-field flex-1 text-base"
           value="<?= h($_GET['q'] ?? '') ?>">
    <button type="submit" class="btn-primary px-6">Search</button>
  </form>

  <!-- Stats -->
  <div class="flex justify-center gap-8 mt-10 text-center">
    <?php foreach ([
        [$totalShops,   'Shops Tracked'],
        [$totalReports, 'Scam Reports'],
        [$totalUsers,   'Users Protected'],
    ] as [$val, $label]): ?>
    <div>
      <div class="text-3xl font-bold text-white" style="font-family:'Syne',sans-serif;"><?= number_format((int)$val) ?></div>
      <div class="text-slate-500 text-sm mt-0.5"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Divider -->
<div class="border-t border-slate-800 my-4"></div>

<!-- ── Featured / At-Risk Shops ──────────────────────────────── -->
<section class="mt-10">
  <div class="flex items-center justify-between mb-6">
    <div>
      <h2 class="text-2xl font-bold text-white" style="font-family:'Syne',sans-serif;">Recently Flagged Shops</h2>
      <p class="text-slate-500 text-sm mt-1">Sorted by computed risk score (highest first)</p>
    </div>
    <a href="<?= APP_URL ?>/shops/search.php" class="btn-ghost text-sm">View All →</a>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($shops as $i => $shop):
      $risk = riskLabel((float)$shop['risk_score']);
    ?>
    <a href="<?= APP_URL ?>/shops/view.php?id=<?= $shop['shop_id'] ?>"
       class="card p-5 hover:border-indigo-500/30 transition-all duration-200 hover:-translate-y-0.5 fade-up block"
       style="animation-delay:<?= $i * 60 ?>ms">

      <div class="flex items-start justify-between mb-3">
        <div class="flex-1 min-w-0">
          <h3 class="font-semibold text-white truncate"><?= h($shop['shop_name']) ?></h3>
          <p class="text-slate-500 text-xs mt-0.5"><?= h($shop['category'] ?? 'General') ?></p>
        </div>
        <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-semibold <?= $risk['bg'] ?> <?= $risk['text'] ?> border <?= $risk['border'] ?> shrink-0">
          <?= $risk['label'] ?>
        </span>
      </div>

      <!-- Risk bar -->
      <div class="mt-3 mb-3">
        <div class="flex justify-between text-xs text-slate-500 mb-1.5">
          <span>Risk Score</span>
          <span class="font-semibold <?= $risk['text'] ?>"><?= round($shop['risk_score']) ?>/100</span>
        </div>
        <div class="h-1.5 rounded-full bg-slate-700">
          <?php
            $pct = min(100, max(0, $shop['risk_score']));
            $barColor = $shop['risk_score'] > 60 ? '#ef4444' : ($shop['risk_score'] > 30 ? '#f59e0b' : '#22c55e');
          ?>
          <div class="h-1.5 rounded-full transition-all" style="width:<?= $pct ?>%;background:<?= $barColor ?>;"></div>
        </div>
      </div>

      <div class="flex items-center justify-between text-xs text-slate-500 mt-3 pt-3 border-t border-slate-800">
        <?php if ($shop['rating_avg'] > 0): ?>
          <div class="flex items-center gap-1">
            <?= starRating($shop['rating_avg']) ?>
            <span><?= round($shop['rating_avg'], 1) ?></span>
          </div>
        <?php else: ?>
          <span class="text-slate-600">No ratings yet</span>
        <?php endif; ?>
        <span><?= $shop['report_count'] ?> report<?= $shop['report_count'] != 1 ? 's' : '' ?></span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── How it works ───────────────────────────────────────────── -->
<section class="mt-16">
  <h2 class="text-2xl font-bold text-center text-white mb-8" style="font-family:'Syne',sans-serif;">How It Works</h2>
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
    <?php foreach ([
        ['🔍','Search a Shop',     'Look up any online shop to instantly see its current risk score, reviews, and reports.'],
        ['🤖','AI Analysis',       'Submitted reports are analyzed by our NLP model to detect scam language and suspicious patterns.'],
        ['🛡️','Stay Safe',        'Risk score formula: 40% ratings + 30% verified reports + 30% AI probability — so you always know the truth.'],
    ] as [$icon,$title,$desc]): ?>
    <div class="card p-6 text-center">
      <div class="text-4xl mb-3"><?= $icon ?></div>
      <h3 class="font-bold text-white mb-2"><?= $title ?></h3>
      <p class="text-slate-400 text-sm leading-relaxed"><?= $desc ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── CTA ───────────────────────────────────────────────────── -->
<?php if (!isLoggedIn()): ?>
<section class="mt-12 card p-8 text-center" style="background:linear-gradient(135deg,rgba(99,102,241,.08),rgba(167,139,250,.05));border-color:rgba(99,102,241,.25);">
  <h2 class="text-2xl font-bold text-white mb-2" style="font-family:'Syne',sans-serif;">Join the Community</h2>
  <p class="text-slate-400 mb-6">Register to submit scam reports, leave reviews, and help protect others from online fraud.</p>
  <div class="flex justify-center gap-3">
    <a href="<?= APP_URL ?>/auth/register.php" class="btn-primary">Create Free Account</a>
    <a href="<?= APP_URL ?>/auth/login.php"    class="btn-ghost">Sign In</a>
  </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
