<?php

return [
    // Notifikasi KUOTA muncul bila sisa asesi <= angka ini
    'notif_sisa' => (int) env('VOUCHER_NOTIF_SISA', 10),

    // Notifikasi MASA BERLAKU muncul bila sisa hari <= angka ini
    'notif_hari' => (int) env('VOUCHER_NOTIF_HARI', 30),

    // Penutup pesan WhatsApp
    'wa_ttd' => env('VOUCHER_WA_TTD', 'Admin LSP'),
];
