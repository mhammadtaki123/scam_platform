<?php
// includes/header.php — Call at the top of every page
// Expects $pageTitle to be set before inclusion.
$pageTitle = $pageTitle ?? 'ScamGuard';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($pageTitle) ?> — ScamGuard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-base:   #080c14;
      --bg-card:   #0f1624;
      --bg-nav:    #090d17;
      --accent:    #6366f1;
      --accent-2:  #818cf8;
      --danger:    #ef4444;
      --warning:   #f59e0b;
      --success:   #22c55e;
    }
    * { box-sizing: border-box; }
    body { background: var(--bg-base); font-family: 'DM Sans', sans-serif; color: #e2e8f0; min-height: 100vh; }
    h1,h2,h3,h4 { font-family: 'Syne', sans-serif; }

    /* Grid noise texture */
    body::before {
      content:''; position:fixed; inset:0; pointer-events:none; z-index:0;
      background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%236366f1' fill-opacity='0.025'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }

    .card { background: var(--bg-card); border: 1px solid rgba(99,102,241,.15); border-radius: 12px; }
    .btn-primary { background: var(--accent); color:#fff; border-radius:8px; padding:.55rem 1.25rem; font-weight:600; font-size:.875rem; transition: all .2s; }
    .btn-primary:hover { background: var(--accent-2); transform: translateY(-1px); box-shadow: 0 4px 20px rgba(99,102,241,.35); }
    .btn-danger  { background: var(--danger);  color:#fff; border-radius:8px; padding:.55rem 1.25rem; font-weight:600; font-size:.875rem; transition: all .2s; }
    .btn-danger:hover  { opacity:.85; }
    .btn-ghost { border:1px solid rgba(99,102,241,.3); color: var(--accent-2); border-radius:8px; padding:.55rem 1.25rem; font-weight:500; font-size:.875rem; transition: all .2s; }
    .btn-ghost:hover { background:rgba(99,102,241,.1); }
    .input-field { background: rgba(15,22,36,.7); border: 1px solid rgba(99,102,241,.25); color:#e2e8f0; border-radius:8px; padding:.6rem .9rem; width:100%; font-size:.9rem; transition: border-color .2s; }
    .input-field:focus { outline:none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(99,102,241,.15); }
    .input-field::placeholder { color:#475569; }

    /* Dropdown */
    .dropdown { position: relative; display: inline-block; }
    .dropdown-menu {
      display: none; position: absolute; right: 0; top: calc(100% + 8px);
      background: var(--bg-card); border: 1px solid rgba(99,102,241,.2);
      border-radius: 10px; min-width: 180px; z-index: 100;
      box-shadow: 0 8px 32px rgba(0,0,0,.4); overflow: hidden;
    }
    .dropdown:hover .dropdown-menu,
    .dropdown:focus-within .dropdown-menu { display: block; }
    .dropdown-menu a {
      display: flex; align-items: center; gap: 8px;
      padding: .6rem 1rem; font-size: .85rem; color: #94a3b8;
      text-decoration: none; transition: background .15s, color .15s;
    }
    .dropdown-menu a:hover { background: rgba(99,102,241,.1); color: #e2e8f0; }
    .dropdown-menu .divider { border-top: 1px solid rgba(99,102,241,.1); margin: 4px 0; }

    /* Scrollbar */
    ::-webkit-scrollbar { width:6px; } ::-webkit-scrollbar-track { background:#0f1624; }
    ::-webkit-scrollbar-thumb { background:#334155; border-radius:3px; }

    /* Animations */
    @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:none} }
    .fade-up { animation: fadeUp .45s ease forwards; }
    @keyframes pulse-ring { 0%{box-shadow:0 0 0 0 rgba(239,68,68,.4)} 70%{box-shadow:0 0 0 10px rgba(239,68,68,0)} 100%{box-shadow:0 0 0 0 rgba(239,68,68,0)} }
    .pulse-red { animation: pulse-ring 2s infinite; }
  </style>
</head>
<body class="relative">
<div class="relative z-10">

<!-- ── Navigation ─────────────────────────────────────────── -->
<nav style="background:var(--bg-nav);border-bottom:1px solid rgba(99,102,241,.12);" class="sticky top-0 z-50 backdrop-blur-sm">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">

      <!-- Logo -->
      <a href="<?= APP_URL ?>/index.php" class="flex items-center gap-2.5 group">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:rgba(99,102,241,.2);border:1px solid rgba(99,102,241,.4);">
          <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
          </svg>
        </div>
        <span class="font-bold text-lg tracking-tight text-white" style="font-family:'Syne',sans-serif;">Scam<span class="text-indigo-400">Guard</span></span>
      </a>

      <!-- Nav links -->
      <div class="hidden md:flex items-center gap-1">
        <a href="<?= APP_URL ?>/index.php"        class="px-3 py-2 rounded-lg text-sm text-slate-400 hover:text-white hover:bg-white/5 transition-all">Home</a>
        <a href="<?= APP_URL ?>/shops/search.php" class="px-3 py-2 rounded-lg text-sm text-slate-400 hover:text-white hover:bg-white/5 transition-all">Search Shops</a>
        <a href="<?= APP_URL ?>/leaderboard.php" class="px-3 py-2 rounded-lg text-sm text-slate-400 hover:text-white hover:bg-white/5 transition-all">Leaderboard</a>
        <?php if (isLoggedIn()): ?>
          <a href="<?= APP_URL ?>/reports/submit.php" class="px-3 py-2 rounded-lg text-sm text-slate-400 hover:text-white hover:bg-white/5 transition-all">Report Scam</a>
          <?php if (isAdmin()): ?>
            <a href="<?= APP_URL ?>/admin/dashboard.php" class="px-3 py-2 rounded-lg text-sm text-amber-400 hover:text-amber-300 hover:bg-amber-400/5 transition-all">⚡ Admin</a>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <!-- Right side -->
      <div class="flex items-center gap-3">
        <?php if (isLoggedIn()): ?>
          <?php $unread = getUnreadCount($pdo, $_SESSION['user_id']); ?>
          <a href="<?= APP_URL ?>/notifications.php" class="relative flex items-center p-2 rounded-lg hover:bg-white/10 transition-colors" title="Notifications">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
              </svg>
              <?php if ($unread > 0): ?>
              <span class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[16px] h-4 flex items-center justify-center px-1 leading-none">
                  <?= $unread > 99 ? '99+' : $unread ?>
              </span>
              <?php endif; ?>
          </a>

          <!-- User dropdown -->
          <div class="dropdown">
            <button class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm text-slate-300 hover:bg-white/5 transition-all border border-transparent hover:border-indigo-500/20">
              <!-- Avatar initial -->
              <span class="w-6 h-6 rounded-full bg-indigo-500/30 border border-indigo-500/40 flex items-center justify-center text-indigo-300 text-xs font-bold">
                <?= strtoupper($_SESSION['username'][0]) ?>
              </span>
              <span class="hidden sm:block font-medium"><?= h($_SESSION['username']) ?></span>
              <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

            <div class="dropdown-menu">
              <a href="<?= APP_URL ?>/user/profile.php">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                My Profile
              </a>
              <a href="<?= APP_URL ?>/auth/change_password.php">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                Change Password
              </a>
              <?php if (isAdmin()): ?>
                <a href="<?= APP_URL ?>/admin/dashboard.php">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                  </svg>
                  Admin Panel
                </a>
              <?php endif; ?>
              <div class="divider"></div>
              <a href="<?= APP_URL ?>/auth/logout.php" style="color:#f87171;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Sign Out
              </a>
            </div>
          </div>

        <?php else: ?>
          <a href="<?= APP_URL ?>/auth/login.php"    class="btn-ghost">Sign In</a>
          <a href="<?= APP_URL ?>/auth/register.php" class="btn-primary">Register</a>
        <?php endif; ?>
      </div>

    </div>
  </div>
</nav>

<!-- ── Flash Message ──────────────────────────────────────── -->
<?php if ($flash): ?>
  <?php
    $colors = ['success'=>'border-green-500/40 bg-green-500/10 text-green-300',
               'error'  =>'border-red-500/40 bg-red-500/10 text-red-300',
               'info'   =>'border-indigo-500/40 bg-indigo-500/10 text-indigo-300',
               'warning'=>'border-amber-500/40 bg-amber-500/10 text-amber-300'];
    $cls = $colors[$flash['type']] ?? $colors['info'];
  ?>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 fade-up">
    <div class="border rounded-lg px-4 py-3 text-sm <?= $cls ?>">
      <?= h($flash['message']) ?>
    </div>
  </div>
<?php endif; ?>

<!-- ── Page Content Wrapper ───────────────────────────────── -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
