<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kata kunci non-kerja
    |--------------------------------------------------------------------------
    | Semua huruf kecil. Dicocokkan sebagai substring (str_contains) terhadap
    | NAMA PROSES dan JUDUL WINDOW yang dilaporkan agent. Kalau salah satu
    | proses/window aktif mengandung salah satu kata kunci ini, device akan
    | ditandai sebagai "aktivitas di luar kerja".
    |
    | Sesuaikan daftar ini dengan kebijakan kantor/lab Anda.
    */
    'non_work_keywords' => [
        // Game
        'steam', 'roblox', 'valorant', 'mobile legends', 'pubg', 'genshin',
        'dota', 'minecraft', 'epic games', 'game launcher',

        // Hiburan / video
        'msc', 'netflix', 'tiktok', 'twitch', 'disney+', 'viu', 'vidio', 'gotf', 'mpl', 'naruto', 'eafc', 'efootball',

        // Sosial media
        'instagram', 'facebook', 'twitter', 'x.com', 'whatsapp web',
        'telegram web', 'snapchat',

        // E-commerce / belanja
        'shopee', 'tokopedia', 'lazada',

        // Judi online
        'judi', 'slot online',
    ],

    /*
    |--------------------------------------------------------------------------
    | Kata kunci pengecualian (whitelist)
    |--------------------------------------------------------------------------
    | Kalau proses/window mengandung kata ini, TIDAK akan ditandai meskipun
    | juga cocok dengan daftar non-kerja di atas. Contoh: software internal
    | yang namanya kebetulan mengandung kata 'game'.
    */
    'whitelist_keywords' => [
        // 'gamedev-suite',
    ],
];