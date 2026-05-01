<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$shop_id = (int)($_GET['id'] ?? 0);
if (!$shop_id) {
    header('Location: ' . APP_URL . '/shops/search.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM shops WHERE shop_id = ?");
$stmt->execute([$shop_id]);
$shop = $stmt->fetch();

if (!$shop) {
    setFlash('error', 'Shop not found.');
    header('Location: ' . APP_URL . '/shops/search.php');
    exit;
}

if ($shop['owner_id']) {
    setFlash('warning', 'This shop has already been claimed.');
    header('Location: ' . APP_URL . '/shops/view.php?id=' . $shop_id);
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $evidence = trim($_POST['evidence'] ?? '');

    if (strlen($evidence) < 20) {
        $error = 'Please provide detailed evidence of ownership (at least 20 characters).';
    } else {
        // Check if user already has a pending claim for this shop
        $existing = $pdo->prepare("SELECT claim_id FROM shop_claims WHERE shop_id = ? AND user_id = ? AND status = 'pending'");
        $existing->execute([$shop_id, $_SESSION['user_id']]);
        
        if ($existing->fetch()) {
            $error = 'You already have a pending claim for this shop.';
        } else {
            $pdo->prepare("INSERT INTO shop_claims (shop_id, user_id, evidence) VALUES (?, ?, ?)")
                ->execute([$shop_id, $_SESSION['user_id'], $evidence]);
            $success = 'Your claim has been submitted and is pending admin review.';
        }
    }
}

$pageTitle = 'Claim Shop - ' . $shop['shop_name'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-2xl mx-auto fade-up">
  <div class="flex items-center gap-2 text-sm text-slate-500 mb-6">
    <a href="<?= APP_URL ?>/shops/view.php?id=<?= $shop_id ?>" class="hover:text-slate-300 transition-colors">← Back to Shop</a>
  </div>

  <div class="card p-8">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-white mb-2" style="font-family:'Syne',sans-serif;">Claim Business Profile</h1>
      <p class="text-slate-400 text-sm">Are you the owner of <strong class="text-white"><?= h($shop['shop_name']) ?></strong>? Verify your ownership to respond to reviews and reports.</p>
    </div>

    <?php if ($error): ?>
      <div class="border border-red-500/30 bg-red-500/10 rounded-lg p-3 mb-6 text-red-300 text-sm"><?= h($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
      <div class="border border-green-500/30 bg-green-500/10 rounded-lg p-4 mb-6">
        <p class="text-green-300 text-sm font-medium"><?= h($success) ?></p>
      </div>
    <?php else: ?>
      <form method="POST" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1.5">Evidence of Ownership *</label>
          <p class="text-xs text-slate-500 mb-2">Please explain how you are affiliated with this business and provide links to public profiles (e.g. LinkedIn) or contact information matching the domain's WHOIS data.</p>
          <textarea name="evidence" class="input-field min-h-[150px]" required><?= h($_POST['evidence'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn-primary w-full mt-2">Submit Claim</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
