<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Privacy Policy';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto fade-up">
  <div class="mb-8">
    <h1 class="text-3xl font-bold text-white" style="font-family:'Syne',sans-serif;">Privacy Policy</h1>
    <p class="text-slate-500 text-sm mt-2">Last updated: <?= date('F j, Y') ?></p>
  </div>

  <div class="card p-8 space-y-8 text-slate-300 leading-relaxed text-sm">

    <section>
      <h2 class="text-lg font-bold text-white mb-3" style="font-family:'Syne',sans-serif;">1. Overview</h2>
      <p>ScamGuard ("we", "our", "the Platform") is committed to protecting your privacy. This policy explains what data we collect, how we use it, and the choices you have. By using ScamGuard, you agree to the practices described here.</p>
    </section>

    <div style="border-top:1px solid rgba(99,102,241,.1);"></div>

    <section>
      <h2 class="text-lg font-bold text-white mb-3" style="font-family:'Syne',sans-serif;">2. Data We Collect</h2>

      <p class="font-medium text-slate-200 mb-2">Account Information</p>
      <p class="mb-4">When you register, we collect your username, email address, and a bcrypt-hashed version of your password. We never store your password in plain text.</p>

      <p class="font-medium text-slate-200 mb-2">User-Submitted Content</p>
      <p class="mb-4">Reports you submit (including descriptions and uploaded evidence files), reviews, and star ratings are stored in our database and associated with your account.</p>

      <p class="font-medium text-slate-200 mb-2">Usage Data</p>
      <p>We do not use third-party analytics or tracking cookies. Standard web server logs (IP address, browser type, pages visited) may be retained temporarily for security and debugging purposes.</p>
    </section>

    <div style="border-top:1px solid rgba(99,102,241,.1);"></div>

    <section>
      <h2 class="text-lg font-bold text-white mb-3" style="font-family:'Syne',sans-serif;">3. How We Use Your Data</h2>
      <ul class="space-y-2 list-none">
        <?php foreach ([
          ['To operate the Platform', 'Your account details allow you to log in, submit reports, and leave reviews.'],
          ['To compute risk scores',  'Report descriptions are sent to an AI model (Anthropic Claude or a local Ollama instance) for scam probability analysis. No personally identifiable information is included in these requests.'],
          ['To moderate content',     'Admins can view submitted reports and evidence to approve or reject them.'],
          ['To improve the Platform', 'Aggregated, anonymised data may be used to improve scoring accuracy and platform features.'],
        ] as [$title, $desc]): ?>
          <li class="flex items-start gap-3 p-3 rounded-lg" style="background:rgba(99,102,241,.05);border:1px solid rgba(99,102,241,.1);">
            <span class="text-indigo-400 mt-0.5 shrink-0">›</span>
            <div>
              <p class="font-medium text-slate-200"><?= $title ?></p>
              <p class="text-slate-400 mt-0.5"><?= $desc ?></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>

    <div style="border-top:1px solid rgba(99,102,241,.1);"></div>

    <section>
      <h2 class="text-lg font-bold text-white mb-3" style="font-family:'Syne',sans-serif;">4. AI Processing</h2>
      <p class="mb-3">When you submit a scam report, the text of your description is forwarded to an AI model for analysis. Depending on the platform configuration:</p>
      <ul class="space-y-2">
        <li class="flex items-start gap-2">
          <span class="text-indigo-400 shrink-0 mt-0.5">›</span>
          <span><strong class="text-slate-200">Cloud mode (Anthropic Claude):</strong> your report text is transmitted to Anthropic's API. Anthropic's own privacy policy applies to this data.</span>
        </li>
        <li class="flex items-start gap-2">
          <span class="text-indigo-400 shrink-0 mt-0.5">›</span>
          <span><strong class="text-slate-200">Local mode (Ollama):</strong> processing happens entirely on the local server. No data leaves the machine.</span>
        </li>
      </ul>
    </section>

    <div style="border-top:1px solid rgba(99,102,241,.1);"></div>

    <section>
      <h2 class="text-lg font-bold text-white mb-3" style="font-family:'Syne',sans-serif;">5. Data Sharing</h2>
      <p>We do not sell, rent, or share your personal data with third parties for marketing purposes. Data is only shared:</p>
      <ul class="mt-3 space-y-1.5">
        <li class="flex items-start gap-2"><span class="text-indigo-400 shrink-0">›</span><span>With the AI provider for report analysis (cloud mode only, text only).</span></li>
        <li class="flex items-start gap-2"><span class="text-indigo-400 shrink-0">›</span><span>When required by law or to protect the safety of users.</span></li>
      </ul>
    </section>

    <div style="border-top:1px solid rgba(99,102,241,.1);"></div>

    <section>
      <h2 class="text-lg font-bold text-white mb-3" style="font-family:'Syne',sans-serif;">6. Data Security</h2>
      <p>All passwords are hashed with bcrypt. Database queries use PDO prepared statements to prevent SQL injection. File uploads are validated for type and size and stored with randomised filenames. The Platform runs locally over XAMPP and is not publicly exposed by default.</p>
    </section>

    <div style="border-top:1px solid rgba(99,102,241,.1);"></div>

    <section>
      <h2 class="text-lg font-bold text-white mb-3" style="font-family:'Syne',sans-serif;">7. Your Rights</h2>
      <p>You may request deletion of your account and associated data at any time by contacting an administrator. Approved reports may be retained in anonymised form to preserve the integrity of risk scores.</p>
    </section>

    <div style="border-top:1px solid rgba(99,102,241,.1);"></div>

    <section>
      <h2 class="text-lg font-bold text-white mb-3" style="font-family:'Syne',sans-serif;">8. Changes to This Policy</h2>
      <p>We may update this Privacy Policy from time to time. We will post the revised version on this page with an updated date. Continued use of the Platform after changes are posted constitutes acceptance.</p>
    </section>

  </div>

  <div class="flex gap-4 mt-6 text-sm">
    <a href="<?= APP_URL ?>/terms.php" class="text-indigo-400 hover:text-indigo-300 transition-colors">Terms of Use →</a>
    <a href="<?= APP_URL ?>/faq.php"   class="text-indigo-400 hover:text-indigo-300 transition-colors">FAQ →</a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
