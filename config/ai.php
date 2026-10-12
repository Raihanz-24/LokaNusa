<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Penyedia AI (OpenAI-compatible)
    |--------------------------------------------------------------------------
    |
    | LokaNusa memakai gateway AI yang kompatibel dengan API "chat completions"
    | milik OpenAI (mis. Kryptonlab). Isi kredensial lewat .env; JANGAN pernah
    | menaruh API key di dalam kode atau repositori.
    |
    */

    // Base URL gateway, tanpa garis miring di akhir. Endpoint yang dipanggil:
    // {base_url}/chat/completions
    'base_url' => env('AI_BASE_URL', ''),

    'key' => env('AI_API_KEY'),

    // Nama model yang dipakai (mengikuti penamaan penyedia).
    'model' => env('AI_MODEL', 'deepseek-flash-4'),

    // Batas waktu permintaan (detik).
    'timeout' => (int) env('AI_TIMEOUT', 60),

    // Kreativitas balasan (0 = kaku, 1 = kreatif).
    'temperature' => (float) env('AI_TEMPERATURE', 0.7),

    // Jumlah maksimum token keluaran (balasan asisten).
    'max_tokens' => (int) env('AI_MAX_TOKENS', 800),

];
