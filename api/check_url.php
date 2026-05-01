<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Simple API Key authentication (Optional, but good practice. For now, public access)
// if (($_GET['api_key'] ?? '') !== 'YOUR_SECRET_KEY') {
//     http_response_code(401);
//     echo json_encode(['error' => 'Unauthorized']);
//     exit;
// }

$url = trim($_GET['url'] ?? '');

if (!$url) {
    http_response_code(400);
    echo json_encode(['error' => 'URL parameter is required']);
    exit;
}

// Extract domain from URL
$parsed = parse_url($url);
$domain = $parsed['host'] ?? $url;
$domain = preg_replace('/^www\./', '', $domain);

// Find shop by domain
$stmt = $pdo->prepare("SELECT shop_id, shop_name, category FROM shops WHERE website_url LIKE ?");
$stmt->execute(['%' . $domain . '%']);
$shop = $stmt->fetch();

if (!$shop) {
    echo json_encode([
        'found' => false,
        'message' => 'Shop not found in our database.',
        'risk_score' => null
    ]);
    exit;
}

// Fetch risk score
$rs = $pdo->prepare("SELECT risk_score FROM risk_scores WHERE shop_id = ?");
$rs->execute([$shop['shop_id']]);
$risk_score = $rs->fetchColumn() ?: 0;

$riskData = riskLabel($risk_score);

echo json_encode([
    'found' => true,
    'shop_name' => $shop['shop_name'],
    'category' => $shop['category'],
    'risk_score' => round($risk_score),
    'risk_level' => $riskData['label'],
    'details_url' => APP_URL . '/shops/view.php?id=' . $shop['shop_id']
]);
