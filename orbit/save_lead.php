<?php
// SIGNAL — partner.html quiz lead intake
// POST /orbit/save_lead.php
// Body: {name, email, phone, intent, market, role, channels[], lang, consent_pdpa}

declare(strict_types=1);
require __DIR__ . '/_db.php';

$cfg = load_config();
send_json_cors($cfg['cors_origins']);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

$body = read_json_body();

// === Validation ===
$name    = clean_str($body['name']    ?? '', 120);
$email   = clean_str($body['email']   ?? '', 160);
$phone   = clean_str($body['phone']   ?? '', 40);
$intent  = clean_str($body['intent']  ?? '', 2000);
$market  = clean_str($body['market']  ?? '', 4);
$role    = clean_str($body['role']    ?? '', 40);
$lang    = clean_str($body['lang']    ?? 'uk', 4);
$channels = is_array($body['channels'] ?? null) ? array_slice($body['channels'], 0, 4) : [];
$consent = !empty($body['consent_pdpa']);

$errors = [];
if ($name === '')                $errors[] = 'name_required';
if (!valid_email($email))        $errors[] = 'email_invalid';
if ($intent === '')              $errors[] = 'intent_required';
if (!in_array($market, ['ua','pl','ro','tr'], true))  $errors[] = 'market_invalid';
if ($role === '')                $errors[] = 'role_required';
if (!in_array($lang, ['uk','pl','ro','tr','en'], true)) $errors[] = 'lang_invalid';
if (!$consent)                   $errors[] = 'consent_required';

if ($errors) {
    http_response_code(400);
    echo json_encode(['error' => 'validation_failed', 'fields' => $errors]);
    exit;
}

// Normalize channels (whitelist)
$channels = array_values(array_intersect($channels, ['whatsapp','telegram','email']));
if (!$channels) $channels = ['email'];

// === Rate limit ===
$pdo = db_connect($cfg);
$ipHash = client_ip_hash();
rate_limit_check($pdo, 'leads', $ipHash, $email, $cfg['rate_limit']);

// === Insert ===
$ua = clean_str($_SERVER['HTTP_USER_AGENT'] ?? '', 300);
$stmt = $pdo->prepare("
    INSERT INTO leads
        (source, name, email, phone, market, role, channels, intent_text, lang,
         status, consent_pdpa, user_agent, ip_hash, created_at)
    VALUES
        ('partner_quiz', :name, :email, :phone, :market, :role, :channels, :intent, :lang,
         'new', 1, :ua, :ip_hash, NOW())
");
try {
    $stmt->execute([
        ':name'     => $name,
        ':email'    => $email,
        ':phone'    => $phone,
        ':market'   => $market,
        ':role'     => $role,
        ':channels' => json_encode($channels, JSON_UNESCAPED_UNICODE),
        ':intent'   => $intent,
        ':lang'     => $lang,
        ':ua'       => $ua,
        ':ip_hash'  => $ipHash,
    ]);
    $leadId = (int) $pdo->lastInsertId();
} catch (PDOException $e) {
    http_response_code(503);
    error_log('SIGNAL: lead insert failed: ' . $e->getMessage());
    echo json_encode(['error' => 'save_failed']);
    exit;
}

// === Telegram notification (best-effort, non-blocking on failure) ===
$msg  = "<b>Новий лід SIGNAL</b> #$leadId\n";
$msg .= "<b>Ринок:</b> " . html_escape(strtoupper($market)) . " · <b>Роль:</b> " . html_escape($role) . " · <b>Мова:</b> " . html_escape($lang) . "\n";
$msg .= "<b>Імʼя:</b> " . html_escape($name) . "\n";
$msg .= "<b>Email:</b> " . html_escape($email) . "\n";
if ($phone !== '') $msg .= "<b>Телефон:</b> " . html_escape($phone) . "\n";
$msg .= "<b>Канали:</b> " . html_escape(implode(', ', $channels)) . "\n";
$msg .= "<b>Шукає:</b>\n" . html_escape($intent);
notify_telegram($cfg, $msg);

// === Response ===
echo json_encode(['ok' => true, 'lead_id' => $leadId]);
