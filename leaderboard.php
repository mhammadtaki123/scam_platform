<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Fetch leaderboard data: Top users by approved reports
$leaders = $pdo->query("
    SELECT u.username, COUNT(r.report_id) as approved_reports
    FROM users u
    JOIN reports r ON u.user_id = r.user_id
    WHERE r.status = 'approved'
    GROUP BY u.user_id
    ORDER BY approved_reports DESC
    LIMIT 20
")->fetchAll();

$pageTitle = 'Community Leaderboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto fade-up">
  <div class="text-center mb-10">
    <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center"
         style="background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.35);">
      <span class="text-3xl">🏆</span>
    </div>
    <h1 class="text-3xl font-bold text-white" style="font-family:'Syne',sans-serif;">Community Leaderboard</h1>
    <p class="text-slate-500 mt-2">Top contributors protecting consumers from scams</p>
  </div>

  <div class="card p-2">
    <table class="w-full text-left">
      <thead>
        <tr class="border-b border-slate-800 text-slate-500 text-sm">
          <th class="py-4 px-6 font-medium">Rank</th>
          <th class="py-4 px-6 font-medium">User</th>
          <th class="py-4 px-6 font-medium text-right">Verified Reports</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-800">
        <?php if (!$leaders): ?>
          <tr>
            <td colspan="3" class="py-8 text-center text-slate-500 text-sm">No verified reports yet. Be the first!</td>
          </tr>
        <?php else: ?>
          <?php foreach ($leaders as $index => $leader): 
            $rank = $index + 1;
            $medal = '';
            if ($rank === 1) $medal = '🥇';
            elseif ($rank === 2) $medal = '🥈';
            elseif ($rank === 3) $medal = '🥉';
            else $medal = "<span class='text-slate-600 font-bold'>#$rank</span>";
          ?>
          <tr class="hover:bg-white/5 transition-colors group">
            <td class="py-4 px-6 w-24 text-lg"><?= $medal ?></td>
            <td class="py-4 px-6 font-semibold text-slate-200 flex items-center gap-3">
              <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-400 group-hover:bg-indigo-500/20 group-hover:text-indigo-400 transition-colors">
                <?= strtoupper($leader['username'][0]) ?>
              </div>
              <?= h($leader['username']) ?>
            </td>
            <td class="py-4 px-6 text-right">
              <span class="inline-block px-3 py-1 bg-green-500/10 text-green-400 border border-green-500/20 rounded-full text-sm font-bold">
                <?= $leader['approved_reports'] ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
