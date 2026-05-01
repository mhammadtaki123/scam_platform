<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'FAQ';
require_once __DIR__ . '/includes/header.php';

$faqs = [
  'General' => [
    ['What is ScamGuard?',
     'ScamGuard is a community-powered scam detection platform. Users can search for online shops, read and submit scam reports, and leave star ratings. Our AI analyzes each report and combines it with user ratings to produce a 0–100 risk score for every shop.'],
    ['Is ScamGuard free to use?',
     'Yes. Searching shops, reading reports, and viewing risk scores requires no account. Submitting reports and leaving reviews requires a free registered account.'],
    ['Who runs ScamGuard?',
     'ScamGuard was built as a CSCI 490 capstone project. The platform is moderated by an admin team that reviews and verifies submitted scam reports before they affect a shop\'s risk score.'],
  ],
  'Risk Scores' => [
    ['How is the risk score calculated?',
     'The score is a weighted average of three components: star ratings (40%), verified scam reports (30%), and AI scam probability (30%). Each component is normalised to 0–100 before weighting. The final score is recalculated automatically whenever a new review or report is processed.'],
    ['What do the risk labels mean?',
     'Scores of 0–30 are labelled Low Risk (green), 31–60 are Medium Risk (amber), and 61–100 are High Risk (red). These thresholds are guidelines — always read the actual reports for full context.'],
    ['Can a shop\'s risk score improve over time?',
     'Yes. If a shop receives positive reviews and no new verified scam reports, its score will decrease on the next recalculation. Risk scores are dynamic, not permanent.'],
    ['Why is a shop showing a score of 50 with no data?',
     'When a shop has no reviews or AI-analyzed reports yet, each component defaults to a neutral value of 50. This prevents new shops from appearing deceptively safe or dangerous before real data exists.'],
  ],
  'Reports & Reviews' => [
    ['How do I submit a scam report?',
     'Sign in to your account, then click "Report Scam" in the navigation or on a shop\'s detail page. Describe your experience in at least 30 characters and optionally upload an image or PDF as evidence. Your report will be analyzed by AI and then reviewed by an admin before it is published.'],
    ['Can I submit a report anonymously?',
     'No. A registered account is required to submit reports. Your username is shown alongside published reports so the community can gauge credibility. Your email address is never publicly displayed.'],
    ['How long does report moderation take?',
     'Admins aim to review reports as quickly as possible. Once approved, the shop\'s risk score is recalculated immediately. If your report is rejected, it will not affect the risk score.'],
    ['Can I upload evidence with my report?',
     'Yes. You can attach one image (JPG, PNG, GIF, WEBP) or PDF file up to 5 MB. Evidence files are visible to admins during moderation and to the public on the shop\'s detail page after approval.'],
    ['Can I edit or delete my review after posting?',
     'Currently, reviews cannot be edited after submission. If you need a review removed, contact an administrator.'],
  ],
  'Accounts' => [
    ['How do I change my password?',
     'Click your username in the top-right navigation to open the dropdown menu, then select "Change Password". You will need to enter your current password to confirm the change.'],
    ['I forgot my password. How do I reset it?',
     'Password reset via email is not yet implemented. Please contact an administrator who can manually reset your account.'],
    ['How do I delete my account?',
     'Account self-deletion is not available through the UI. Contact an administrator to request deletion. Approved reports may be retained in anonymised form to preserve the integrity of existing risk scores.'],
  ],
  'AI & Technology' => [
    ['What AI model does ScamGuard use?',
     'ScamGuard supports two AI backends: Anthropic Claude (cloud) or a locally running Ollama model such as llama3.1:8b. The active backend is configured by the administrator. In local mode, no report data ever leaves the server.'],
    ['Can the AI be wrong?',
     'Yes. The AI provides a probability estimate, not a definitive verdict. It is one of three inputs into the risk score and is always combined with real user ratings and admin-verified reports. Never rely solely on the AI score when making purchasing decisions.'],
    ['Does ScamGuard track me or show ads?',
     'No. ScamGuard does not use third-party analytics, advertising networks, or tracking cookies. The platform is designed to be self-hosted and privacy-respecting by default.'],
  ],
];
?>

<div class="max-w-3xl mx-auto fade-up">
  <div class="mb-8">
    <h1 class="text-3xl font-bold text-white" style="font-family:'Syne',sans-serif;">Frequently Asked Questions</h1>
    <p class="text-slate-500 mt-2">Everything you need to know about ScamGuard.</p>
  </div>

  <!-- Section nav -->
  <div class="flex flex-wrap gap-2 mb-8">
    <?php foreach (array_keys($faqs) as $section): ?>
      <a href="#<?= strtolower(str_replace([' ','&'], ['-',''], $section)) ?>"
         class="px-3 py-1.5 rounded-full text-xs font-medium transition-all"
         style="background:rgba(99,102,241,.12);border:1px solid rgba(99,102,241,.25);color:#818cf8;">
        <?= $section ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="space-y-10">
    <?php foreach ($faqs as $section => $items):
      $anchor = strtolower(str_replace([' ','&'], ['-',''], $section));
    ?>
    <div id="<?= $anchor ?>">
      <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2" style="font-family:'Syne',sans-serif;">
        <span class="w-1 h-5 rounded-full bg-indigo-500 inline-block"></span>
        <?= $section ?>
      </h2>

      <div class="space-y-3">
        <?php foreach ($items as $i => [$q, $a]): ?>
        <details class="card group" style="border-color:rgba(99,102,241,.15);">
          <summary class="flex items-center justify-between gap-4 p-5 cursor-pointer list-none select-none hover:bg-white/2 rounded-xl transition-colors">
            <span class="font-medium text-slate-200 text-sm"><?= h($q) ?></span>
            <svg class="w-4 h-4 text-slate-500 shrink-0 transition-transform duration-200 group-open:rotate-45"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
          </summary>
          <div class="px-5 pb-5 text-slate-400 text-sm leading-relaxed border-t border-slate-800 pt-4">
            <?= h($a) ?>
          </div>
        </details>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="flex gap-4 mt-10 text-sm">
    <a href="<?= APP_URL ?>/terms.php"          class="text-indigo-400 hover:text-indigo-300 transition-colors">Terms of Use →</a>
    <a href="<?= APP_URL ?>/privacy-policy.php" class="text-indigo-400 hover:text-indigo-300 transition-colors">Privacy Policy →</a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
