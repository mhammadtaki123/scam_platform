<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $claim_id = (int)($_POST['claim_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM shop_claims WHERE claim_id = ? AND status = 'pending'");
    $stmt->execute([$claim_id]);
    $claim = $stmt->fetch();

    if ($claim) {
        if ($action === 'approve') {
            // Check if shop already has an owner
            $shop = $pdo->prepare("SELECT owner_id FROM shops WHERE shop_id = ?");
            $shop->execute([$claim['shop_id']]);
            $shopData = $shop->fetch();

            if ($shopData && $shopData['owner_id']) {
                $error = 'This shop already has an owner.';
                $pdo->prepare("UPDATE shop_claims SET status = 'rejected' WHERE claim_id = ?")->execute([$claim_id]);
            } else {
                $pdo->beginTransaction();
                try {
                    $pdo->prepare("UPDATE shop_claims SET status = 'approved' WHERE claim_id = ?")->execute([$claim_id]);
                    $pdo->prepare("UPDATE shops SET owner_id = ? WHERE shop_id = ?")->execute([$claim['user_id'], $claim['shop_id']]);
                    
                    // Reject any other pending claims for this shop
                    $pdo->prepare("UPDATE shop_claims SET status = 'rejected' WHERE shop_id = ? AND status = 'pending'")->execute([$claim['shop_id']]);
                    
                    // Notify user
                    createNotification($pdo, $claim['user_id'], 'Claim Approved', 'Your claim for shop ownership has been approved. You can now manage it.');

                    $pdo->commit();
                    $success = 'Claim approved and ownership assigned.';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Error processing claim.';
                }
            }
        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE shop_claims SET status = 'rejected' WHERE claim_id = ?")->execute([$claim_id]);
            createNotification($pdo, $claim['user_id'], 'Claim Rejected', 'Your claim for shop ownership was rejected due to insufficient evidence.');
            $success = 'Claim rejected.';
        }
    }
}

// Fetch pending claims
$claims = $pdo->query("
    SELECT c.*, s.shop_name, u.username, u.email 
    FROM shop_claims c
    JOIN shops s ON c.shop_id = s.shop_id
    JOIN users u ON c.user_id = u.user_id
    WHERE c.status = 'pending'
    ORDER BY c.created_at ASC
")->fetchAll();

$pageTitle = 'Manage Claims';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="fade-up">
  <div class="flex items-center gap-4 mb-8">
    <a href="<?= APP_URL ?>/admin/dashboard.php" class="text-sm text-slate-500 hover:text-slate-300">← Dashboard</a>
    <h1 class="text-3xl font-bold text-white" style="font-family:'Syne',sans-serif;">Shop Ownership Claims</h1>
  </div>

  <?php if ($error): ?>
    <div class="border border-red-500/30 bg-red-500/10 rounded-lg p-3 mb-6 text-red-300 text-sm"><?= h($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="border border-green-500/30 bg-green-500/10 rounded-lg p-3 mb-6 text-green-300 text-sm"><?= h($success) ?></div>
  <?php endif; ?>

  <div class="card p-6">
    <h2 class="text-lg font-bold text-white mb-5" style="font-family:'Syne',sans-serif;">Pending Claims (<?= count($claims) ?>)</h2>
    
    <?php if (!$claims): ?>
      <p class="text-slate-500 text-sm py-4">No pending claims at the moment.</p>
    <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($claims as $claim): ?>
          <div class="border border-slate-700 bg-slate-800/30 rounded-lg p-5">
            <div class="flex flex-col md:flex-row justify-between gap-4">
              <div>
                <h3 class="font-bold text-white mb-1">
                  Shop: <a href="<?= APP_URL ?>/shops/view.php?id=<?= $claim['shop_id'] ?>" class="text-indigo-400 hover:underline"><?= h($claim['shop_name']) ?></a>
                </h3>
                <p class="text-sm text-slate-400 mb-2">Claimed by: <span class="font-semibold text-slate-300"><?= h($claim['username']) ?></span> (<?= h($claim['email']) ?>)</p>
                <div class="text-xs text-slate-500 mb-4">Submitted: <?= date('M j, Y g:i A', strtotime($claim['created_at'])) ?></div>
                
                <div class="bg-slate-900/50 p-4 rounded border border-slate-700">
                  <h4 class="text-xs font-semibold text-slate-400 uppercase mb-2">Evidence Provided:</h4>
                  <p class="text-sm text-slate-300 whitespace-pre-wrap"><?= h($claim['evidence']) ?></p>
                </div>
              </div>

              <div class="flex flex-row md:flex-col gap-2 shrink-0 md:min-w-[120px]">
                <form method="POST" onsubmit="return confirm('Approve this claim?');">
                  <input type="hidden" name="claim_id" value="<?= $claim['claim_id'] ?>">
                  <input type="hidden" name="action" value="approve">
                  <button type="submit" class="btn-primary w-full text-xs py-2 bg-green-600 hover:bg-green-500 text-white">Approve</button>
                </form>
                <form method="POST" onsubmit="return confirm('Reject this claim?');">
                  <input type="hidden" name="claim_id" value="<?= $claim['claim_id'] ?>">
                  <input type="hidden" name="action" value="reject">
                  <button type="submit" class="btn-danger w-full text-xs py-2">Reject</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
