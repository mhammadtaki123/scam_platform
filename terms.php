<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Terms of Use';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto fade-up">
  <div class="mb-8">
    <h1 class="text-3xl font-bold text-white" style="font-family:'Syne',sans-serif;">Terms of Use</h1>
    <p class="text-slate-500 text-sm mt-2">Last updated: <?= date('F j, Y') ?></p>
  </div>

  <div class="card p-8 space-y-8 text-slate-300 leading-relaxed text-sm">

    <section>
      <h2 class="text-lg font-bold text-white mb-3" style="font-family:'Syne',sans-serif;">1. Acceptance of Terms</h2>
      <p>By accessing or using ScamGuard ("the Platform"), you agree to be bound by these Terms of Use. If you do not agree with any part of these terms, you may not use the Platform.</p>
    </section>

    <div style="border-top:1px solid rgba(99,102,241,.1);"></div>

    <section>
      <h2 class="text-lg font-bold text-white mb-3" style="font-family:'Syne',sans-serif;">2. Use of the Platform</h2>
      <p class="mb-3">You agree to use ScamGuard only for lawful purposes. You must not:</p>
      <ul class="space-y-2 list-none">
        <?php foreach ([
          'Submit false, misleading, or defamatory reports about any shop or business.',
          'Impersonate another person or entity when creating an account.',
          'Attempt to manipulate risk scores through coordinated fake reviews or reports.',
          'Use automated tools, bots, or scrapers to access the Platform without permission.',
          'Upload malicious files or attempt to compromise the security of the Platform.',
        ] as $item): ?>
          <li class="flex items-start gap-2">
            <span class="text-red-400 mt-0.5 shrink-0">✕</span>
            <span><?= $item ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>

    <div style="border-top:1px solid rgba(99,102,241,.1);"></div>

    <section>
      <h2 class="text-lg font-bold text-white mb-3" style="font-family:'Syne',sans-serif;">3. User-Submitted Content</h2>
      <p class="mb-3">When you submit a report or review, you confirm that:</p>
      <ul class="space-y-2 list-none">
        <?php foreach ([
          'The information is accurate and based on your genuine personal experience.',
          'You have the right to share any evidence or attachments you upload.',
          'You grant ScamGuard a non-exclusive licence to display, analyse, and moderate your submission.',
        ] as $item): ?>
          <li class="flex items-start gap-2">
            <span class="text-indigo-400 mt-0.5 shrink-0">›</span>
            <span><?= $item ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <p class="mt-3">ScamGuard reserves the right to remove any content that violates these terms or that is deemed inappropriate by our moderation team.</p>
    </section>

    <div style="border-top:1px solid rgba(99,102,241,.1);"></div>

    <section>
      <h2 class="text-lg font-bold text-white mb-3" style="font-family:'Syne',sans-serif;">4. AI-Generated Risk Scores</h2>
      <p>Risk scores displayed on the Platform are computed algorithmically using user reviews, verified reports, and AI analysis. They are provided for informational purposes only and do not constitute legal, financial, or professional advice. ScamGuard does not guarantee the accuracy of any risk score and accepts no liability for decisions made based on them.</p>
    </section>

    <div style="border-top:1px solid rgba(99,102,241,.1);"></div>

    <section>
      <h2 class="text-lg font-bold text-white mb-3" style="font-family:'Syne',sans-serif;">5. Account Responsibilities</h2>
      <p>You are responsible for maintaining the confidentiality of your account credentials. You must notify us immediately if you suspect any unauthorised access to your account. ScamGuard is not liable for any loss resulting from unauthorised use of your account.</p>
    </section>

    <div style="border-top:1px solid rgba(99,102,241,.1);"></div>

    <section>
      <h2 class="text-lg font-bold text-white mb-3" style="font-family:'Syne',sans-serif;">6. Disclaimer of Warranties</h2>
      <p>The Platform is provided on an "as is" and "as available" basis without warranties of any kind, either express or implied. ScamGuard does not warrant that the Platform will be uninterrupted, error-free, or free of viruses or other harmful components.</p>
    </section>

    <div style="border-top:1px solid rgba(99,102,241,.1);"></div>

    <section>
      <h2 class="text-lg font-bold text-white mb-3" style="font-family:'Syne',sans-serif;">7. Limitation of Liability</h2>
      <p>To the fullest extent permitted by law, ScamGuard and its contributors shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of, or inability to use, the Platform.</p>
    </section>

    <div style="border-top:1px solid rgba(99,102,241,.1);"></div>

    <section>
      <h2 class="text-lg font-bold text-white mb-3" style="font-family:'Syne',sans-serif;">8. Changes to These Terms</h2>
      <p>We reserve the right to update these Terms of Use at any time. Continued use of the Platform after changes are posted constitutes your acceptance of the revised terms.</p>
    </section>

    <div style="border-top:1px solid rgba(99,102,241,.1);"></div>

    <section>
      <h2 class="text-lg font-bold text-white mb-3" style="font-family:'Syne',sans-serif;">9. Contact</h2>
      <p>For questions regarding these terms, please reach out via the platform's admin contact.</p>
    </section>

  </div>

  <div class="flex gap-4 mt-6 text-sm">
    <a href="<?= APP_URL ?>/privacy-policy.php" class="text-indigo-400 hover:text-indigo-300 transition-colors">Privacy Policy →</a>
    <a href="<?= APP_URL ?>/faq.php"            class="text-indigo-400 hover:text-indigo-300 transition-colors">FAQ →</a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
