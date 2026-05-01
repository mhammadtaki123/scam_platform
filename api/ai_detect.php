<?php
/**
 * api/ai_detect.php
 * POST JSON endpoint — accepts { "text": "..." } and returns AI scam analysis.
 * Protected: requires active session.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST required.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$text = trim($body['text'] ?? $_POST['text'] ?? '');

if (strlen($text) < 10) {
    http_response_code(400);
    echo json_encode(['error' => 'Text too short.']);
    exit;
}

$result = detectScamText($text);
echo json_encode($result);
