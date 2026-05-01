<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

// Fetch report trends for the last 30 days
$days = [];
$counts = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $days[] = date('M j', strtotime($date));
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $counts[] = $stmt->fetchColumn();
}

// Fetch categories for shop distribution
$categories = [];
$catCounts = [];
$catQuery = $pdo->query("SELECT category, COUNT(*) as c FROM shops GROUP BY category");
foreach ($catQuery as $row) {
    $cat = $row['category'] ?: 'Uncategorized';
    $categories[] = $cat;
    $catCounts[] = $row['c'];
}

$pageTitle = 'Analytics';
require_once __DIR__ . '/../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="fade-up">
  <div class="flex items-center gap-4 mb-8">
    <a href="<?= APP_URL ?>/admin/dashboard.php" class="text-sm text-slate-500 hover:text-slate-300">← Dashboard</a>
    <h1 class="text-3xl font-bold text-white" style="font-family:'Syne',sans-serif;">Platform Analytics</h1>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Trend Chart -->
    <div class="card p-6">
      <h2 class="text-lg font-bold text-white mb-4" style="font-family:'Syne',sans-serif;">Reports (Last 30 Days)</h2>
      <canvas id="trendChart" height="250"></canvas>
    </div>

    <!-- Category Distribution -->
    <div class="card p-6">
      <h2 class="text-lg font-bold text-white mb-4" style="font-family:'Syne',sans-serif;">Shops by Category</h2>
      <div class="flex justify-center">
        <div style="width: 300px; height: 300px;">
          <canvas id="catChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Reports Trend Chart
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($days) ?>,
        datasets: [{
            label: 'New Reports',
            data: <?= json_encode($counts) ?>,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.1)',
            borderWidth: 2,
            tension: 0.3,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(255,255,255,0.05)' },
                ticks: { color: '#94a3b8', stepSize: 1 }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#94a3b8' }
            }
        }
    }
});

// Category Distribution Chart
new Chart(document.getElementById('catChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($categories) ?>,
        datasets: [{
            data: <?= json_encode($catCounts) ?>,
            backgroundColor: [
                '#6366f1', '#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { color: '#94a3b8' } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
