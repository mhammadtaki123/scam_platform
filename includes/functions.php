<?php
// ── Auth Helpers ──────────────────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin(string $redirect = '/auth/login.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . $redirect);
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
}

// ── Flash Messages ────────────────────────────────────────────
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ── Risk Score Utilities ──────────────────────────────────────
/**
 * Compute risk score: 40% ratings + 30% approved reports + 30% AI score
 * All components normalized to 0–100 before weighting.
 */
function computeRiskScore(PDO $pdo, int $shop_id): float {
    // 1. Rating component (low rating = high risk)
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM reviews WHERE shop_id = ?");
    $stmt->execute([$shop_id]);
    $rev = $stmt->fetch();
    $ratingComponent = ($rev['cnt'] > 0)
        ? (1 - (($rev['avg_r'] - 1) / 4)) * 100  // 5-star → 0 risk, 1-star → 100 risk
        : 50;  // Neutral if no reviews

    // 2. Report component
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(status='approved') as approved FROM reports WHERE shop_id = ?");
    $stmt->execute([$shop_id]);
    $rep = $stmt->fetch();
    $reportComponent = 0;
    if ($rep['total'] > 0) {
        $approvalRate  = $rep['approved'] / $rep['total'];
        $volumePenalty = min($rep['total'] * 5, 50); // Up to 50 pts for volume
        $reportComponent = ($approvalRate * 50) + $volumePenalty;
    }

    // 3. AI component — average of all report AI scores
    $stmt = $pdo->prepare("SELECT AVG(ai_score) as avg_ai FROM reports WHERE shop_id = ? AND ai_score IS NOT NULL");
    $stmt->execute([$shop_id]);
    $ai = $stmt->fetch();
    $aiComponent = ($ai['avg_ai'] !== null) ? ($ai['avg_ai'] * 100) : 50;

    // Fetch weights from settings
    $settingsRows = $pdo->query("SELECT * FROM settings")->fetchAll();
    $weights = [];
    foreach ($settingsRows as $row) {
        $weights[$row['setting_key']] = (float)$row['setting_value'];
    }
    $wRating = $weights['weight_rating'] ?? 0.4;
    $wReports = $weights['weight_reports'] ?? 0.3;
    $wAi = $weights['weight_ai'] ?? 0.3;

    $score = ($wRating * $ratingComponent) + ($wReports * $reportComponent) + ($wAi * $aiComponent);
    $score = max(0, min(100, $score));

    // Persist
    $pdo->prepare("INSERT INTO risk_scores (shop_id, risk_score, rating_avg, report_count, ai_avg_score)
                   VALUES (?, ?, ?, ?, ?)
                   ON DUPLICATE KEY UPDATE
                     risk_score   = VALUES(risk_score),
                     rating_avg   = VALUES(rating_avg),
                     report_count = VALUES(report_count),
                     ai_avg_score = VALUES(ai_avg_score),
                     last_updated = NOW()")
        ->execute([$shop_id, $score, $rev['avg_r'], $rep['total'], $ai['avg_ai']]);

    return round($score, 1);
}

function riskLabel(float $score): array {
    if ($score <= 30) return ['label' => 'Low Risk',    'color' => 'green',  'bg' => 'bg-green-500/20',  'text' => 'text-green-400',  'border' => 'border-green-500/40'];
    if ($score <= 60) return ['label' => 'Medium Risk', 'color' => 'amber',  'bg' => 'bg-amber-500/20',  'text' => 'text-amber-400',  'border' => 'border-amber-500/40'];
    return              ['label' => 'High Risk',   'color' => 'red',    'bg' => 'bg-red-500/20',    'text' => 'text-red-400',    'border' => 'border-red-500/40'];
}

// ── AI Scam Detection (Ollama Local) ─────────────────────────
function detectScamText(string $text): array {
    $payload = json_encode([
        'model'  => OLLAMA_MODEL,
        'stream' => false,
        'messages' => [
            [
                'role'    => 'system',
                'content' => 'You are a scam detection AI. Respond ONLY with raw JSON, no markdown, no explanation.',
            ],
            [
                'role'    => 'user',
                'content' => "Analyze this online shop report for scam indicators.\n\nReport:\n\"\"\"\n$text\n\"\"\"\n\nRespond ONLY with a JSON object with these exact fields:\n- score: float 0.0-1.0 (0=legitimate, 1=scam)\n- confidence: \"low\"|\"medium\"|\"high\"\n- flags: array of short strings (e.g. \"no refund\", \"fake tracking\")\n- summary: one sentence, max 120 chars",
            ],
        ],
    ]);

    $ch = curl_init(OLLAMA_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 60,   // local models can be slow — give it time
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        return ['score' => 0.5, 'confidence' => 'low', 'flags' => [], 'summary' => 'AI unavailable.'];
    }

    // Ollama response: { "message": { "content": "..." } }
    $data = json_decode($response, true);
    $raw  = $data['message']['content'] ?? '{}';

    // Strip markdown fences if the model adds them anyway
    $raw = preg_replace('/```json|```/i', '', $raw);

    // Extract the first {...} block in case there's surrounding text
    if (preg_match('/\{.*\}/s', $raw, $m)) {
        $raw = $m[0];
    }

    $result = json_decode(trim($raw), true);

    if (!$result || !isset($result['score'])) {
        return ['score' => 0.5, 'confidence' => 'low', 'flags' => [], 'summary' => 'Could not parse AI response.'];
    }

    return [
        'score'      => (float) max(0, min(1, $result['score'])),
        'confidence' => $result['confidence'] ?? 'medium',
        'flags'      => (array)($result['flags'] ?? []),
        'summary'    => substr($result['summary'] ?? '', 0, 180),
    ];
}

// ── General Utilities ─────────────────────────────────────────
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function timeAgo(string $datetime): string {
    $now  = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);
    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'just now';
}

function starRating(float $rating): string {
    $html = '<span class="flex gap-0.5">';
    for ($i = 1; $i <= 5; $i++) {
        $filled = $i <= round($rating);
        $html .= '<svg class="w-4 h-4 ' . ($filled ? 'text-amber-400' : 'text-slate-600') . '"
                       fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0
                             00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0
                             00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1
                             1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1
                             1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0
                             00.951-.69l1.07-3.292z"/>
                  </svg>';
    }
    return $html . '</span>';
}

// ── Notifications ────────────────────────────────────────────────────────────

function createNotification(PDO $pdo, int $user_id, string $title, string $message, ?string $link = null): void {
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, link)
        VALUES (:uid, :title, :msg, :link)
    ");
    $stmt->execute([':uid' => $user_id, ':title' => $title, ':msg' => $message, ':link' => $link]);
}

function getUnreadCount(PDO $pdo, int $user_id): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
    $stmt->execute([':uid' => $user_id]);
    return (int) $stmt->fetchColumn();
}

function markAllRead(PDO $pdo, int $user_id): void {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid")->execute([':uid' => $user_id]);
}
