</main><!-- /main -->

<footer style="border-top:1px solid rgba(99,102,241,.1);" class="mt-16 py-10">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <!-- Top row: logo + nav links -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-6 mb-6">
      <div class="flex items-center gap-2">
        <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:rgba(99,102,241,.2);border:1px solid rgba(99,102,241,.4);">
          <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
          </svg>
        </div>
        <span style="font-family:'Syne',sans-serif;" class="font-bold text-slate-300">Scam<span class="text-indigo-400">Guard</span></span>
      </div>

      <nav class="flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm">
        <a href="<?= APP_URL ?>/index.php"           class="text-slate-500 hover:text-slate-300 transition-colors">Home</a>
        <a href="<?= APP_URL ?>/shops/search.php"    class="text-slate-500 hover:text-slate-300 transition-colors">Search Shops</a>
        <?php if (isLoggedIn()): ?>
          <a href="<?= APP_URL ?>/reports/submit.php" class="text-slate-500 hover:text-slate-300 transition-colors">Report Scam</a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/faq.php"             class="text-slate-500 hover:text-slate-300 transition-colors">FAQ</a>
        <a href="<?= APP_URL ?>/privacy-policy.php"  class="text-slate-500 hover:text-slate-300 transition-colors">Privacy Policy</a>
        <a href="<?= APP_URL ?>/terms.php"           class="text-slate-500 hover:text-slate-300 transition-colors">Terms of Use</a>
      </nav>
    </div>

    <!-- Divider -->
    <div style="border-top:1px solid rgba(99,102,241,.08);" class="mb-6"></div>

    <!-- Bottom row: copyright + tagline -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-600">
      <span>&copy; <?= date('Y') ?> ScamGuard — AI-Driven Consumer Protection Platform</span>
      <span>CSCI 490 &nbsp;·&nbsp; Hassan Al-Khatib</span>
    </div>

  </div>
</footer>

</div><!-- /z-10 wrapper -->
</body>
</html>
