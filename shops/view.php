<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$shop_id = (int)($_GET['id'] ?? 0);
if (!$shop_id) { header('Location: ' . APP_URL . '/shops/search.php'); exit; }

$shop = $pdo->prepare("SELECT * FROM shops WHERE shop_id = ?");
$shop->execute([$shop_id]);
$shop = $shop->fetch();
if (!$shop) { setFlash('error', 'Shop not found.'); header('Location: ' . APP_URL . '/shops/search.php'); exit; }

// ── Handle new review submission ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    requireLogin();

    if ($_POST['action'] === 'review') {
        $rating  = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if ($rating >= 1 && $rating <= 5) {
            // Check if already reviewed
            $existing = $pdo->prepare("SELECT review_id FROM reviews WHERE user_id=? AND shop_id=?");
            $existing->execute([$_SESSION['user_id'], $shop_id]);
            if (!$existing->fetch()) {
                $pdo->prepare("INSERT INTO reviews (user_id,shop_id,rating,comment) VALUES (?,?,?,?)")
                    ->execute([$_SESSION['user_id'], $shop_id, $rating, $comment]);
                computeRiskScore($pdo, $shop_id);
                setFlash('success', 'Review submitted!');
            } else {
                setFlash('warning', 'You already reviewed this shop.');
            }
        }
        header("Location: " . APP_URL . "/shops/view.php?id=$shop_id"); exit;
    } elseif ($_POST['action'] === 'respond' && $shop['owner_id'] == $_SESSION['user_id']) {
        $review_id = (int)($_POST['review_id'] ?? 0);
        $response_text = trim($_POST['response_text'] ?? '');
        
        if ($review_id && $response_text) {
            $pdo->prepare("INSERT INTO shop_responses (review_id, owner_id, response_text) VALUES (?,?,?)")
                ->execute([$review_id, $_SESSION['user_id'], $response_text]);
            setFlash('success', 'Response posted.');
        }
        header("Location: " . APP_URL . "/shops/view.php?id=$shop_id"); exit;
    }
}

// ── Load data ─────────────────────────────────────────────────
$risk_score = computeRiskScore($pdo, $shop_id);
$risk       = riskLabel($risk_score);

$reviews = $pdo->prepare("
    SELECT r.*, u.username FROM reviews r
    JOIN users u ON r.user_id=u.user_id
    WHERE r.shop_id=? ORDER BY r.created_at DESC
");
$reviews->execute([$shop_id]);
$reviews = $reviews->fetchAll();
$avgRating = count($reviews) ? array_sum(array_column($reviews,'rating'))/count($reviews) : 0;

$responsesQuery = $pdo->prepare("
    SELECT sr.* FROM shop_responses sr
    JOIN reviews r ON sr.review_id = r.review_id
    WHERE r.shop_id = ?
");
$responsesQuery->execute([$shop_id]);
$shopResponses = [];
foreach ($responsesQuery->fetchAll() as $resp) {
    $shopResponses[$resp['review_id']] = $resp;
}

$reports = $pdo->prepare("
    SELECT r.*, u.username FROM reports r
    JOIN users u ON r.user_id=u.user_id
    WHERE r.shop_id=? AND r.status='approved' ORDER BY r.created_at DESC
");
$reports->execute([$shop_id]);
$reports = $reports->fetchAll();

$pageTitle = $shop['shop_name'];
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ── Breadcrumb ─────────────────────────────────────────── -->
<div class="flex items-center gap-2 text-sm text-slate-500 mb-6">
  <a href="<?= APP_URL ?>/shops/search.php" class="hover:text-slate-300 transition-colors">Shops</a>
  <span>/</span>
  <span class="text-slate-300"><?= h($shop['shop_name']) ?></span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- ── Left: Main content ──────────────────────────────────── -->
  <div class="lg:col-span-2 space-y-6 fade-up">

    <!-- Shop header card -->
    <div class="card p-6">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-white" style="font-family:'Syne',sans-serif;"><?= h($shop['shop_name']) ?></h1>
          <?php if ($shop['category']): ?>
            <span class="inline-block mt-1 px-2 py-0.5 rounded text-xs font-medium bg-indigo-500/15 text-indigo-400 border border-indigo-500/25">
              <?= h($shop['category']) ?>
            </span>
          <?php endif; ?>
          <?php if ($shop['website_url']): ?>
            <a href="<?= h($shop['website_url']) ?>" target="_blank" rel="noopener noreferrer"
               class="mt-2 flex items-center gap-1.5 text-sm text-slate-400 hover:text-indigo-400 transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
              </svg>
              <?= h($shop['website_url']) ?>
            </a>
          <?php endif; ?>
        </div>
        <span class="px-3 py-1 rounded-full text-sm font-semibold <?= $risk['bg'] ?> <?= $risk['text'] ?> border <?= $risk['border'] ?> shrink-0">
          <?= $risk['label'] ?>
        </span>
      </div>
      <?php if ($shop['description']): ?>
        <p class="text-slate-400 mt-4 leading-relaxed"><?= h($shop['description']) ?></p>
      <?php endif; ?>

      <?php if (isLoggedIn()): ?>
        <div class="mt-4 pt-4 border-t border-slate-800 flex gap-2">
          <a href="<?= APP_URL ?>/reports/submit.php?shop_id=<?= $shop_id ?>" class="btn-danger text-sm flex-1 text-center">
            🚨 Report This Shop
          </a>
          <?php if (!$shop['owner_id']): ?>
            <a href="<?= APP_URL ?>/shops/claim.php?id=<?= $shop_id ?>" class="btn-ghost text-sm flex-1 text-center">
              🏢 Claim Business
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- ── Reviews ─────────────────────────────────────────── -->
    <div class="card p-6">
      <div class="flex items-center justify-between mb-5">
        <h2 class="text-lg font-bold text-white" style="font-family:'Syne',sans-serif;">
          Reviews <span class="text-slate-500 font-normal text-base">(<?= count($reviews) ?>)</span>
        </h2>
        <?php if ($avgRating > 0): ?>
          <div class="flex items-center gap-2">
            <?= starRating($avgRating) ?>
            <span class="text-slate-300 font-semibold"><?= round($avgRating,1) ?></span>
          </div>
        <?php endif; ?>
      </div>

      <!-- Write a review -->
      <?php if (isLoggedIn()): ?>
        <form method="POST" class="mb-6 p-4 rounded-lg border border-slate-700 bg-slate-800/30">
          <input type="hidden" name="action" value="review">
          <p class="text-sm font-medium text-slate-300 mb-3">Write a Review</p>

          <!-- Star selector -->
          <div class="flex gap-1 mb-3" id="star-selector">
            <?php for($s=1;$s<=5;$s++): ?>
              <label class="cursor-pointer">
                <input type="radio" name="rating" value="<?= $s ?>" class="sr-only" required>
                <svg class="w-7 h-7 text-slate-600 hover:text-amber-400 transition-colors star-icon" data-val="<?= $s ?>"
                     fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
              </label>
            <?php endfor; ?>
          </div>

          <textarea name="comment" class="input-field mb-3 resize-none" rows="3"
                    placeholder="Share your experience with this shop…"></textarea>
          <button type="submit" class="btn-primary text-sm">Post Review</button>
        </form>
      <?php else: ?>
        <div class="mb-5 p-3 rounded-lg border border-slate-700 text-sm text-slate-400 text-center">
          <a href="<?= APP_URL ?>/auth/login.php" class="text-indigo-400 hover:underline">Sign in</a> to write a review
        </div>
      <?php endif; ?>

      <!-- Review list -->
      <?php if (!$reviews): ?>
        <p class="text-slate-600 text-sm text-center py-6">No reviews yet. Be the first!</p>
      <?php else: ?>
        <div class="space-y-4">
          <?php foreach ($reviews as $review): ?>
            <div class="flex gap-3">
              <div class="w-8 h-8 rounded-full bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400 font-bold text-sm shrink-0">
                <?= strtoupper($review['username'][0]) ?>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                  <span class="text-sm font-medium text-slate-300"><?= h($review['username']) ?></span>
                  <?= starRating($review['rating']) ?>
                  <span class="text-slate-600 text-xs ml-auto"><?= timeAgo($review['created_at']) ?></span>
                </div>
                <?php if ($review['comment']): ?>
                  <p class="text-slate-400 text-sm leading-relaxed"><?= h($review['comment']) ?></p>
                <?php endif; ?>
                
                <?php if (isset($shopResponses[$review['review_id']])): ?>
                  <div class="mt-3 bg-indigo-500/10 border border-indigo-500/20 rounded-lg p-3">
                    <p class="text-xs font-bold text-indigo-400 mb-1">Response from Owner:</p>
                    <p class="text-sm text-slate-300"><?= h($shopResponses[$review['review_id']]['response_text']) ?></p>
                  </div>
                <?php elseif (isLoggedIn() && $shop['owner_id'] == $_SESSION['user_id']): ?>
                  <form method="POST" class="mt-3">
                    <input type="hidden" name="action" value="respond">
                    <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                    <textarea name="response_text" class="input-field text-sm mb-2" rows="2" placeholder="Write a response..." required></textarea>
                    <button type="submit" class="btn-ghost py-1 px-3 text-xs">Post Response</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- ── Approved Scam Reports ─────────────────────────────── -->
    <div class="card p-6">
      <h2 class="text-lg font-bold text-white mb-5" style="font-family:'Syne',sans-serif;">
        Verified Scam Reports <span class="text-slate-500 font-normal text-base">(<?= count($reports) ?>)</span>
      </h2>
      <?php if (!$reports): ?>
        <p class="text-slate-600 text-sm text-center py-6">✅ No verified scam reports for this shop.</p>
      <?php else: ?>
        <div class="space-y-4">
          <?php foreach ($reports as $rep): ?>
            <div class="border border-red-500/20 bg-red-500/5 rounded-lg p-4">
              <div class="flex items-start justify-between gap-2 mb-2">
                <span class="text-sm font-medium text-slate-300"><?= h($rep['username']) ?></span>
                <div class="flex items-center gap-2 shrink-0">
                  <?php if ($rep['ai_score'] !== null): ?>
                    <span class="text-xs px-2 py-0.5 rounded-full border border-red-500/30 bg-red-500/15 text-red-400">
                      AI: <?= round($rep['ai_score']*100) ?>% scam
                    </span>
                  <?php endif; ?>
                  <span class="text-xs text-slate-600"><?= timeAgo($rep['created_at']) ?></span>
                </div>
              </div>
              <p class="text-slate-400 text-sm leading-relaxed"><?= h($rep['description']) ?></p>
              <?php if ($rep['evidence_path']): ?>
                <a href="<?= UPLOAD_URL . h($rep['evidence_path']) ?>" target="_blank"
                   class="mt-2 inline-flex items-center gap-1 text-xs text-indigo-400 hover:underline">
                  📎 View Evidence
                </a>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div><!-- /left -->

  <!-- ── Right: Risk sidebar ─────────────────────────────────── -->
  <div class="space-y-4 fade-up" style="animation-delay:150ms">

    <!-- Risk gauge card -->
    <div class="card p-6 text-center">
      <h3 class="text-sm font-semibold text-slate-400 mb-4">Overall Risk Score</h3>

      <!-- SVG gauge -->
      <div class="relative mx-auto" style="width:160px;height:90px;">
        <svg viewBox="0 0 200 110" class="w-full">
          <!-- Track -->
          <path d="M 20 100 A 80 80 0 0 1 180 100" stroke="#1e293b" stroke-width="20" fill="none" stroke-linecap="round"/>
          <!-- Fill -->
          <?php
            $pct2 = min(100, max(0, $risk_score)) / 100;
            // Semicircle: starts at left (180°) sweeps clockwise to right (0°)
            // large-arc-flag must ALWAYS be 0 — we never sweep more than 180°
            $angle     = M_PI - ($pct2 * M_PI);   // 180° → 0° as score goes 0 → 100
            $ex        = 100 + 80 * cos($angle);
            $ey        = 100 - 80 * sin($angle);
            $largeArc  = 0;                        // always 0 for a semicircle
            $fillColor = $risk_score > 60 ? '#ef4444' : ($risk_score > 30 ? '#f59e0b' : '#22c55e');
          ?>
          <path d="M 20 100 A 80 80 0 <?= $largeArc ?> 1 <?= round($ex,2) ?> <?= round($ey,2) ?>"
                stroke="<?= $fillColor ?>" stroke-width="20" fill="none" stroke-linecap="round"/>
          <!-- Needle dot -->
          <circle cx="<?= round($ex,2) ?>" cy="<?= round($ey,2) ?>" r="8" fill="<?= $fillColor ?>" />
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-end pb-2">
          <span class="text-3xl font-extrabold <?= $risk['text'] ?>" style="font-family:'Syne',sans-serif;"><?= round($risk_score) ?></span>
          <span class="text-xs text-slate-500">out of 100</span>
        </div>
      </div>

      <span class="inline-block mt-3 px-3 py-1 rounded-full text-sm font-semibold <?= $risk['bg'] ?> <?= $risk['text'] ?> border <?= $risk['border'] ?>">
        <?= $risk['label'] ?>
      </span>

      <!-- Score breakdown -->
      <div class="mt-5 space-y-2 text-left">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Score Breakdown</p>
        <?php
          $rs = $pdo->prepare("SELECT * FROM risk_scores WHERE shop_id=?");
          $rs->execute([$shop_id]);
          $rs = $rs->fetch();
        ?>
        <div class="flex justify-between text-xs">
          <span class="text-slate-400">⭐ Ratings (40%)</span>
          <span class="text-slate-300"><?= $rs && $rs['rating_avg'] ? round($rs['rating_avg'],1).'/5' : 'N/A' ?></span>
        </div>
        <div class="flex justify-between text-xs">
          <span class="text-slate-400">🚨 Reports (30%)</span>
          <span class="text-slate-300"><?= $rs['report_count'] ?? 0 ?> verified</span>
        </div>
        <div class="flex justify-between text-xs">
          <span class="text-slate-400">🤖 AI Score (30%)</span>
          <span class="text-slate-300"><?= $rs && $rs['ai_avg_score'] !== null ? round($rs['ai_avg_score']*100).'%' : 'N/A' ?></span>
        </div>
      </div>
    </div>

    <!-- Quick stats -->
    <div class="card p-5 space-y-3">
      <h3 class="text-sm font-semibold text-slate-400">Quick Stats</h3>
      <?php foreach ([
        ['Total Reviews',  count($reviews), '⭐'],
        ['Verified Reports', count($reports), '🚩'],
        ['Avg. Rating', $avgRating > 0 ? round($avgRating,1).'/5' : 'N/A', '📊'],
      ] as [$label, $value, $icon]): ?>
        <div class="flex items-center justify-between">
          <span class="text-xs text-slate-500"><?= $icon ?> <?= $label ?></span>
          <span class="text-sm font-semibold text-slate-200"><?= $value ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Report CTA -->
    <?php if (isLoggedIn()): ?>
      <a href="<?= APP_URL ?>/reports/submit.php?shop_id=<?= $shop_id ?>"
         class="card p-5 flex items-center gap-3 border-red-500/20 hover:border-red-500/40 transition-all group block">
        <div class="w-10 h-10 rounded-lg bg-red-500/15 border border-red-500/25 flex items-center justify-center shrink-0">
          <span class="text-xl">🚨</span>
        </div>
        <div>
          <p class="text-sm font-semibold text-white">Report This Shop</p>
          <p class="text-xs text-slate-500 mt-0.5">Help protect others from scams</p>
        </div>
      </a>
    <?php endif; ?>

  </div><!-- /right -->
</div>

<script>
// Interactive star rating
document.querySelectorAll('#star-selector label').forEach((label, idx) => {
  const icons = document.querySelectorAll('#star-selector .star-icon');
  label.addEventListener('mouseenter', () => {
    icons.forEach((icon, i) => {
      icon.style.color = i <= idx ? '#fbbf24' : '#475569';
    });
  });
  label.addEventListener('mouseleave', () => {
    const checked = document.querySelector('#star-selector input:checked');
    icons.forEach((icon, i) => {
      const val = checked ? parseInt(checked.value) - 1 : -1;
      icon.style.color = i <= val ? '#fbbf24' : '#475569';
    });
  });
  label.querySelector('input').addEventListener('change', () => {
    icons.forEach((icon, i) => {
      icon.style.color = i <= idx ? '#fbbf24' : '#475569';
    });
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
