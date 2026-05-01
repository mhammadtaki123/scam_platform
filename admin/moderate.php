<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

// Quick approve/reject via GET (token = user_id as simple CSRF)
if (isset($_GET['action'], $_GET['id'])) {
    $action    = $_GET['action'];
    $report_id = (int)$_GET['id'];
    $token     = (int)($_GET['token'] ?? 0);

    if ($token !== (int)$_SESSION['user_id']) {
        setFlash('error', 'Invalid action token.'); header('Location: ' . APP_URL . '/admin/moderate.php'); exit;
    }

    if (in_array($action, ['approve','reject'])) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $admin_note = trim($_GET['note'] ?? '');
        $pdo->prepare("UPDATE reports SET status=?, admin_note=?, reviewed_at=NOW() WHERE report_id=?")
            ->execute([$status, $admin_note, $report_id]);

        // Recalculate risk score for that shop
        $row = $pdo->prepare("SELECT shop_id FROM reports WHERE report_id=?");
        $row->execute([$report_id]);
        $row = $row->fetch();
        if ($row) {
            $shop_id = $row['shop_id'];
            computeRiskScore($pdo, $shop_id);

            // Notify user
            $reportRow = $pdo->prepare("SELECT r.user_id, s.shop_name FROM reports r JOIN shops s ON r.shop_id = s.shop_id WHERE r.report_id = :id");
            $reportRow->execute([':id' => $report_id]);
            $rdata = $reportRow->fetch();

            if ($rdata) {
                if ($action === 'approve') {
                    createNotification(
                        $pdo,
                        $rdata['user_id'],
                        '✅ Your Report Was Approved',
                        "Your scam report for \"{$rdata['shop_name']}\" has been reviewed and approved by an admin.",
                        APP_URL . '/shops/view.php?id=' . $shop_id
                    );
                } else {
                    createNotification(
                        $pdo,
                        $rdata['user_id'],
                        '❌ Your Report Was Rejected',
                        "Your scam report for \"{$rdata['shop_name']}\" was reviewed but could not be approved." .
                        ($admin_note ? " Admin note: {$admin_note}" : ''),
                        APP_URL . '/shops/view.php?id=' . $shop_id
                    );
                }
            }
        }

        setFlash('success', 'Report ' . ($action === 'approve' ? 'approved' : 'rejected') . '.');
    }
    header('Location: ' . APP_URL . '/admin/moderate.php'); exit;
}

// Filter
$statusFilter = $_GET['status'] ?? 'pending';
$validStatus  = ['pending','approved','rejected','all'];
if (!in_array($statusFilter, $validStatus)) $statusFilter = 'pending';

$whereSQL = $statusFilter !== 'all' ? "WHERE r.status='$statusFilter'" : '';

$reports = $pdo->query("
    SELECT r.*, u.username, s.shop_name, s.shop_id
    FROM reports r
    JOIN users u ON r.user_id=u.user_id
    JOIN shops s ON r.shop_id=s.shop_id
    $whereSQL
    ORDER BY r.created_at DESC
")->fetchAll();

$pageTitle = 'Moderation Queue';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="fade-up">
  <div class="flex items-center gap-4 mb-8">
    <a href="<?= APP_URL ?>/admin/dashboard.php" class="text-sm text-slate-500 hover:text-slate-300 transition-colors">← Dashboard</a>
    <h1 class="text-3xl font-bold text-white" style="font-family:'Syne',sans-serif;">Moderation Queue</h1>
  </div>

  <!-- Status filter tabs -->
  <div class="flex gap-2 mb-6 border-b border-slate-800 pb-4">
    <?php foreach (['pending'=>'⏳ Pending','approved'=>'✅ Approved','rejected'=>'❌ Rejected','all'=>'📋 All'] as $s => $label): ?>
      <a href="?status=<?= $s ?>"
         class="px-4 py-2 rounded-lg text-sm font-medium transition-all <?= $statusFilter === $s
           ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30'
           : 'text-slate-500 hover:text-slate-300 hover:bg-white/5' ?>">
        <?= $label ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (!$reports): ?>
    <div class="card p-12 text-center text-slate-600">
      <div class="text-4xl mb-3">📭</div>
      <p>No <?= $statusFilter !== 'all' ? $statusFilter : '' ?> reports.</p>
    </div>
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
      <div class="card p-5">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
          <div>
            <div class="flex items-center gap-2">
              <a href="<?= APP_URL ?>/shops/view.php?id=<?= $rep['shop_id'] ?>" class="font-semibold text-white hover:text-indigo-400 transition-colors">
                <?= h($rep['shop_name']) ?>
              </a>
              <span class="text-xs px-2 py-0.5 rounded-full border <?= $sc ?>"><?= ucfirst($rep['status']) ?></span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">By <strong class="text-slate-400"><?= h($rep['username']) ?></strong> · <?= timeAgo($rep['created_at']) ?></p>
          </div>

          <div class="flex items-center gap-2">
            <?php if ($rep['ai_score'] !== null): ?>
              <?php $ac = $rep['ai_score']>.7?'text-red-400 border-red-500/30 bg-red-500/10'
                        :($rep['ai_score']>.4?'text-amber-400 border-amber-500/30 bg-amber-500/10'
                        :'text-green-400 border-green-500/30 bg-green-500/10'); ?>
              <span class="text-xs px-2 py-1 rounded-full border <?= $ac ?> font-medium">
                🤖 AI Score: <?= round($rep['ai_score']*100) ?>%
              </span>
            <?php endif; ?>
          </div>
        </div>

        <p class="text-slate-300 text-sm leading-relaxed mb-3"><?= h($rep['description']) ?></p>

        <?php if ($rep['evidence_path']): ?>
          <a href="<?= UPLOAD_URL . h($rep['evidence_path']) ?>" target="_blank"
             class="inline-flex items-center gap-1 text-xs text-indigo-400 hover:underline mb-3">
            📎 View Evidence
          </a>
        <?php endif; ?>

        <?php if ($rep['admin_note']): ?>
          <div class="p-3 rounded-lg bg-slate-800/50 border border-slate-700 text-xs text-slate-400 mb-3">
            <strong class="text-slate-300">Admin note:</strong> <?= h($rep['admin_note']) ?>
          </div>
        <?php endif; ?>

        <?php if ($rep['status'] === 'pending'): ?>
          <div class="flex gap-2 mt-3 pt-3 border-t border-slate-800">
            <a href="?action=approve&id=<?= $rep['report_id'] ?>&token=<?= $_SESSION['user_id'] ?>&status=<?= $statusFilter ?>"
               class="btn-primary text-sm py-1.5">✓ Approve</a>
            <a href="?action=reject&id=<?= $rep['report_id'] ?>&token=<?= $_SESSION['user_id'] ?>&status=<?= $statusFilter ?>"
               class="btn-danger text-sm py-1.5">✗ Reject</a>
          </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
