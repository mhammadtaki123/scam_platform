<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$uid = $_SESSION['user_id'];

// Mark all as read when page is visited
markAllRead($pdo, $uid);

// Fetch all notifications, newest first
$stmt = $pdo->prepare("
    SELECT * FROM notifications
    WHERE user_id = :uid
    ORDER BY created_at DESC
    LIMIT 100
");
$stmt->execute([':uid' => $uid]);
$notifications = $stmt->fetchAll();

// Group by date label
$grouped = [];
foreach ($notifications as $n) {
    $ts   = strtotime($n['created_at']);
    $today     = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $day       = date('Y-m-d', $ts);

    if ($day === $today)          $label = 'Today';
    elseif ($day === $yesterday)  $label = 'Yesterday';
    else                          $label = date('F j, Y', $ts);

    $grouped[$label][] = $n;
}

include __DIR__ . '/includes/header.php';
?>

<main class="min-h-screen py-10 px-4">
  <div class="max-w-2xl mx-auto">

    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-3xl font-bold text-white" style="font-family:'Syne',sans-serif;">Notifications</h1>
        <p class="text-gray-400 mt-1 text-sm">Updates on your reports and activity</p>
      </div>
      <?php if (!empty($notifications)): ?>
      <span class="text-xs text-gray-500 bg-white/5 border border-white/10 rounded-full px-3 py-1">
        <?= count($notifications) ?> total
      </span>
      <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
    <!-- Empty state -->
    <div class="card flex flex-col items-center justify-center py-20 text-center">
      <div class="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
      </div>
      <p class="text-white font-semibold mb-1">No notifications yet</p>
      <p class="text-gray-500 text-sm">You'll see report updates and admin alerts here.</p>
    </div>

    <?php else: ?>

    <?php foreach ($grouped as $label => $items): ?>
    <!-- Date group -->
    <div class="mb-8">
      <div class="flex items-center gap-3 mb-3">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-widest"><?= h($label) ?></span>
        <div class="flex-1 h-px bg-white/5"></div>
      </div>

      <div class="space-y-2">
        <?php foreach ($items as $n): ?>
        <?php
          // Icon & accent color based on title content
          $title = $n['title'];
          if (str_contains($title, 'Approved') || str_contains($title, '✅')) {
              $accent = 'border-green-500/40 bg-green-500/5';
              $dot    = 'bg-green-400';
              $icon   = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>';
              $iconColor = 'text-green-400';
          } elseif (str_contains($title, 'Rejected') || str_contains($title, '❌')) {
              $accent = 'border-red-500/40 bg-red-500/5';
              $dot    = 'bg-red-400';
              $icon   = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>';
              $iconColor = 'text-red-400';
          } elseif (str_contains($title, 'Report') || str_contains($title, '🚨')) {
              $accent = 'border-amber-500/40 bg-amber-500/5';
              $dot    = 'bg-amber-400';
              $icon   = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>';
              $iconColor = 'text-amber-400';
          } else {
              $accent = 'border-blue-500/40 bg-blue-500/5';
              $dot    = 'bg-blue-400';
              $icon   = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';
              $iconColor = 'text-blue-400';
          }
        ?>
        <div class="group flex items-start gap-4 rounded-xl border <?= $accent ?> p-4 transition-all duration-200 hover:bg-white/5">

          <!-- Icon -->
          <div class="mt-0.5 shrink-0 w-8 h-8 rounded-full bg-white/5 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 <?= $iconColor ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <?= $icon ?>
            </svg>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-white leading-snug mb-0.5">
              <?= h(preg_replace('/^[^\w\s]+\s*/', '', $n['title'])) ?>
            </p>
            <p class="text-sm text-gray-400 leading-relaxed"><?= h($n['message']) ?></p>
            <div class="flex items-center gap-3 mt-2">
              <span class="text-xs text-gray-600"><?= timeAgo($n['created_at']) ?></span>
              <?php if ($n['link']): ?>
              <a href="<?= h($n['link']) ?>" class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors font-medium">
                View →
              </a>
              <?php endif; ?>
            </div>
          </div>

        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>

  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
