<?php
// SIGNAL — credentials template
// Copy to config.php on production. NEVER commit config.php.

return [
    // MySQL — Hostinger u227106289_mixzu
    'db' => [
        'host'     => 'localhost',
        'name'     => 'u227106289_mixzu',
        'user'     => 'u227106289_mixzu',
        'pass'     => 'CHANGE_ME',
        'charset'  => 'utf8mb4',
    ],

    // Telegram bot — for lead notifications to owner
    // Create bot via @BotFather, get token, add bot to @signal_ua_admin chat
    'telegram' => [
        'bot_token' => 'CHANGE_ME',          // 1234:ABC...
        'chat_id'   => 'CHANGE_ME',          // numeric chat id (start with - for groups)
    ],

    // Owner email (fallback notification + GDPR contact)
    'owner_email' => 'kinetics1998@gmail.com',

    // Allowed CORS origins (GitHub Pages + custom domain if any)
    'cors_origins' => [
        'https://kinetics1998-prog.github.io',
        'https://signal.ua',
    ],

    // Rate limit
    'rate_limit' => [
        'per_ip_per_hour'    => 5,
        'per_email_per_day'  => 10,
    ],
];
