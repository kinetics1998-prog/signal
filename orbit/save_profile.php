<?php
// SIGNAL — orbit/onboarding.html member profile intake
// POST /orbit/save_profile.php
// Body: {name, surname, company, role, email, telegram, markets, niche, about,
//        scale, lookingTypes, looking, lookingGeo, offeringTypes, offering, ideal,
//        consent_pdpa}

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

// === Helpers for array-or-string fields ===
$arrToStr = function ($v, int $maxLen = 500): string {
    if (is_array($v)) $v = implode(', ', array_map('strval', $v));
    return clean_str((string) $v, $maxLen);
};

// === Validation + sanitize ===
$name      = clean_str($body['name']      ?? '', 120);
$surname   = clean_str($body['surname']   ?? '', 120);
$company   = clean_str($body['company']   ?? '', 200);
$role      = clean_str($body['role']      ?? '', 100);
$email     = clean_str($body['email']     ?? '', 160);
$telegram  = clean_str($body['telegram']  ?? '', 100);
$markets   = $arrToStr($body['markets']   ?? '', 100);
$niche     = clean_str($body['niche']     ?? '', 200);
$about     = clean_str($body['about']     ?? '', 1500);
$scale     = clean_str($body['scale']     ?? '', 60);
$lookingT  = $arrToStr($body['lookingTypes']  ?? '', 500);
$lookingX  = clean_str($body['looking']       ?? '', 1500);
$lookingG  = clean_str($body['lookingGeo']    ?? '', 200);
$offeringT = $arrToStr($body['offeringTypes'] ?? '', 500);
$offeringX = clean_str($body['offering']      ?? '', 1500);
$ideal     = clean_str($body['ideal']         ?? '', 300);
$lang      = clean_str($body['lang'] ?? 'uk', 4);
$consent   = !empty($body['consent_pdpa']);

$errors = [];
if ($name === '')          $errors[] = 'name_required';
if (!valid_email($email))  $errors[] = 'email_invalid';
if ($company === '' && $niche === '') $errors[] = 'company_or_niche_required';
if (!$consent)             $errors[] = 'consent_required';

if ($errors) {
    http_response_code(400);
    echo json_encode(['error' => 'validation_failed', 'fields' => $errors]);
    exit;
}

// === Rate limit ===
$pdo = db_connect($cfg);
$ipHash = client_ip_hash();
rate_limit_check($pdo, 'signal_profiles', $ipHash, $email, $cfg['rate_limit']);

// === UPSERT (insert-or-update by email) ===
$ua = clean_str($_SERVER['HTTP_USER_AGENT'] ?? '', 300);

try {
    $sql = "INSERT INTO signal_profiles
        (name, surname, company, role, email, telegram, markets, niche, about,
         scale, looking_types, looking_text, looking_geo,
         offering_types, offering_text, ideal_partner,
         lang, status, consent_pdpa, user_agent, ip_hash, created_at)
       VALUES
        (:name, :surname, :company, :role, :email, :telegram, :markets, :niche, :about,
         :scale, :looking_types, :looking_text, :looking_geo,
         :offering_types, :offering_text, :ideal_partner,
         :lang, 'waitlist', 1, :ua, :ip_hash, NOW())
       ON DUPLICATE KEY UPDATE
         name = VALUES(name),
         surname = VALUES(surname),
         company = VALUES(company),
         role = VALUES(role),
         telegram = VALUES(telegram),
         markets = VALUES(markets),
         niche = VALUES(niche),
         about = VALUES(about),
         scale = VALUES(scale),
         looking_types = VALUES(looking_types),
         looking_text = VALUES(looking_text),
         looking_geo = VALUES(looking_geo),
         offering_types = VALUES(offering_types),
         offering_text = VALUES(offering_text),
         ideal_partner = VALUES(ideal_partner),
         lang = VALUES(lang),
         updated_at = NOW()";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name'           => $name,
        ':surname'        => $surname,
        ':company'        => $company,
        ':role'           => $role,
        ':email'          => $email,
        ':telegram'       => $telegram,
        ':markets'        => $markets,
        ':niche'          => $niche,
        ':about'          => $about,
        ':scale'          => $scale,
        ':looking_types'  => $lookingT,
        ':looking_text'   => $lookingX,
        ':looking_geo'    => $lookingG,
        ':offering_types' => $offeringT,
        ':offering_text'  => $offeringX,
        ':ideal_partner'  => $ideal,
        ':lang'           => $lang,
        ':ua'             => $ua,
        ':ip_hash'        => $ipHash,
    ]);
    $rowsAffected = $stmt->rowCount();
    $action = ($rowsAffected === 1) ? 'created' : 'updated';
    $profileId = (int) $pdo->lastInsertId();
} catch (PDOException $e) {
    http_response_code(503);
    error_log('SIGNAL: profile upsert failed: ' . $e->getMessage());
    echo json_encode(['error' => 'save_failed']);
    exit;
}

// === Telegram notification (only on first creation) ===
if ($action === 'created') {
    $msg  = "<b>Новий учасник SIGNAL</b> #{$profileId}\n";
    $msg .= "<b>" . html_escape("$name $surname") . "</b>";
    if ($role !== '') $msg .= " · " . html_escape($role);
    $msg .= "\n";
    if ($company !== '') $msg .= "<b>Компанія:</b> " . html_escape($company) . "\n";
    $msg .= "<b>Email:</b> " . html_escape($email) . "\n";
    if ($telegram !== '') $msg .= "<b>Telegram:</b> " . html_escape($telegram) . "\n";
    if ($markets !== '') $msg .= "<b>Ринки:</b> " . html_escape($markets) . "\n";
    if ($niche !== '') $msg .= "<b>Ніша:</b> " . html_escape($niche) . "\n";
    if ($scale !== '') $msg .= "<b>Масштаб:</b> " . html_escape($scale) . "\n";
    if ($lookingT !== '') $msg .= "\n<b>Шукає:</b> " . html_escape($lookingT);
    if ($offeringT !== '') $msg .= "\n<b>Пропонує:</b> " . html_escape($offeringT);
    notify_telegram($cfg, $msg);
}

// === Response ===
echo json_encode([
    'ok'      => true,
    'action'  => $action,
    'profile_id' => $profileId,
    'message' => $action === 'created'
        ? 'Профіль створено. Перші матчі — протягом 24 годин.'
        : 'Профіль оновлено.',
]);
