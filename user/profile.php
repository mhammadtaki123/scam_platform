<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch user stats
$reportsQuery = $pdo->prepare("SELECT COUNT(*) as total, SUM(status='approved') as approved FROM reports WHERE user_id = ?");
$reportsQuery->execute([$user_id]);
$reportStats = $reportsQuery->fetch();

$reviewsCount = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
$reviewsCount->execute([$user_id]);
$reviewsCount = $reviewsCount->fetchColumn();

$trustScore = 0;
if ($reportStats['total'] > 0) {
    $trustScore = round(($reportStats['approved'] / $reportStats['total']) * 100);
}

// Fetch recent reports
$reports = $pdo->prepare("
    SELECT r.*, s.shop_name 
    FROM reports r 
    JOIN shops s ON r.shop_id = s.shop_id 
    WHERE r.user_id = ? 
    ORDER BY r.created_at DESC LIMIT 10
");
$reports->execute([$user_id]);
$reports = $reports->fetchAll();

// Fetch recent reviews
$reviews = $pdo->prepare("
    SELECT r.*, s.shop_name 
    FROM reviews r 
    JOIN shops s ON r.shop_id = s.shop_id 
    WHERE r.user_id = ? 
    ORDER BY r.created_at DESC LIMIT 10
");
$reviews->execute([$user_id]);
$reviews = $reviews->fetchAll();

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-5xl mx-auto fade-up">
  <div class="flex items-center gap-4 mb-8">
    <div class="w-16 h-16 rounded-full bg-indigo-500/20 border border-indigo-500/40 flex items-center justify-center text-indigo-400 font-bold text-2xl shrink-0">
      <?= strtoupper($username[0]) ?>
    </div>
    <div>
      <h1 class="text-3xl font-bold text-white" style="font-family:'Syne',sans-serif;"><?= h($username) ?></h1>
      <p class="text-slate-400 mt-1">Member since <?= date('M Y') /* Placeholder since created_at is not saved in session, but could be fetched */ ?></p>
    </div>
  </div>

  <!-- Stats grid -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="card p-5">
      <div class="text-sm text-slate-500 mb-1">Total Reports</div>
      <div class="text-3xl font-bold text-white" style="font-family:'Syne',sans-serif;"><?= $reportStats['total'] ?></div>
    </div>
    <div class="card p-5">
      <div class="text-sm text-slate-500 mb-1">Approved Reports</div>
      <div class="text-3xl font-bold text-green-400" style="font-family:'Syne',sans-serif;"><?= (int)$reportStats['approved'] ?></div>
    </div>
    <div class="card p-5">
      <div class="text-sm text-slate-500 mb-1">Trust Score</div>
      <div class="flex items-end gap-2">
        <div class="text-3xl font-bold <?= $trustScore > 70 ? 'text-green-400' : ($trustScore > 40 ? 'text-amber-400' : 'text-red-400') ?>" style="font-family:'Syne',sans-serif;">
          <?= $trustScore ?>%
        </div>
      </div>
    </div>
    <div class="card p-5">
      <div class="text-sm text-slate-500 mb-1">Total Reviews</div>
      <div class="text-3xl font-bold text-blue-400" style="font-family:'Syne',sans-serif;"><?= $reviewsCount ?></div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Reports -->
    <div class="card p-6">
      <h2 class="text-lg font-bold text-white mb-5" style="font-family:'Syne',sans-serif;">My Recent Reports</h2>
      <?php if (!$reports): ?>
        <p class="text-slate-500 text-sm py-4 text-center">You haven't submitted any reports yet.</p>
      <?php else: ?>
        <div class="space-y-4">
          <?php foreach ($reports as $rep): 
            $statusColors = [
                'pending'  => 'bg-amber-500/15 text-amber-400 border-amber-500/30',
                'approved' => 'bg-green-500/15 text-green-400 border-green-500/30',
                'rejected' => 'bg-red-500/15 text-red-400 border-red-500/30',
            ];
            $sc = $statusColors[$rep['status']] ?? '';
          ?>
            <div class="border border-slate-700 rounded-lg p-4 hover:border-slate-600 transition-colors">
              <div class="flex items-start justify-between gap-2 mb-2">
                <a href="<?= APP_URL ?>/shops/view.php?id=<?= $rep['shop_id'] ?>" class="font-semibold text-slate-200 hover:text-indigo-400 transition-colors">
                  <?= h($rep['shop_name']) ?>
                </a>
                <span class="text-xs px-2 py-0.5 rounded-full border <?= $sc ?>"><?= ucfirst($rep['status']) ?></span>
              </div>
              <p class="text-slate-400 text-sm line-clamp-2 mb-2"><?= h($rep['description']) ?></p>
              <div class="text-xs text-slate-600"><?= timeAgo($rep['created_at']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Reviews -->
    <div class="card p-6">
      <h2 class="text-lg font-bold text-white mb-5" style="font-family:'Syne',sans-serif;">My Recent Reviews</h2>
      <?php if (!$reviews): ?>
        <p class="text-slate-500 text-sm py-4 text-center">You haven't posted any reviews yet.</p>
      <?php else: ?>
        <div class="space-y-4">
          <?php foreach ($reviews as $rev): ?>
            <div class="border border-slate-700 rounded-lg p-4 hover:border-slate-600 transition-colors">
              <div class="flex items-center justify-between gap-2 mb-2">
                <a href="<?= APP_URL ?>/shops/view.php?id=<?= $rev['shop_id'] ?>" class="font-semibold text-slate-200 hover:text-indigo-400 transition-colors">
                  <?= h($rev['shop_name']) ?>
                </a>
                <?= starRating($rev['rating']) ?>
              </div>
              <?php if ($rev['comment']): ?>
                <p class="text-slate-400 text-sm mb-2">"<?= h($rev['comment']) ?>"</p>
              <?php endif; ?>
              <div class="text-xs text-slate-600"><?= timeAgo($rev['created_at']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
