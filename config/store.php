<?php

return [
    // Flat delivery fee in naira added to every gadget order (0 = agreed with the customer after purchase).
    'delivery_fee' => env('STORE_DELIVERY_FEE', 0),

    // Where "Back to Novastaq" links go.
    'main_site' => env('MAIN_SITE_URL', 'https://novastaq.com'),

    'support_email' => env('STORE_SUPPORT_EMAIL', 'hello@novastaq.com'),
    'admin_emails' => env('STORE_ADMIN_EMAILS', 'hello@novastaq.com,novastaqtechnologies@gmail.com'),
    'whatsapp_url' => env('STORE_WHATSAPP_URL', 'https://wa.me/2348150533325'),

    // Secret for the one-time /install page (hosts without shell access). Empty = page disabled.
    'install_token' => env('INSTALL_TOKEN', ''),

    // Secret token for automated cloud cron runner (/cron/run?token=...)
    'cron_token' => env('CRON_TOKEN', 'novastaq_scheduler_secret_99f2b'),
];
