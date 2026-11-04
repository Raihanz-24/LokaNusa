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

    // Jumlah percobaan ulang saat jaringan/penyedia gangguan sementara.
    'retries' => (int) env('AI_RETRIES', 3),

    // Jeda dasar antar percobaan (milidetik); naik 2x tiap percobaan.
    'retry_delay_ms' => (int) env('AI_RETRY_DELAY_MS', 400),

    // Kreativitas balasan (0 = kaku, 1 = kreatif).
    'temperature' => (float) env('AI_TEMPERATURE', 0.7),

    // Jumlah maksimum token keluaran (balasan asisten).
    'max_tokens' => (int) env('AI_MAX_TOKENS', 800),

    // Batas token khusus saat AI menyusun itinerary terstruktur (JSON).
    // Lebih besar dari balasan biasa agar rencana multi-hari tidak terpotong.
    'plan_max_tokens' => (int) env('AI_PLAN_MAX_TOKENS', 3500),

];
