<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Releases
    |--------------------------------------------------------------------------
    |
    | Tambahkan rilis terbaru ke awal items lalu ubah current_version sebelum
    | setiap deployment yang perlu diumumkan kepada pengguna.
    |
    */
    'current_version' => '1.1.3',

    'items' => [
        [
            'version' => '1.1.3',
            'title' => 'Rincian total kekayaan lebih jelas',
            'released_at' => '2026-09-14',
            'highlights' => [
                'Total kekayaan sekarang menampilkan rincian dompet, tabungan, dan investasi.',
                'Nilai investasi tetap dihitung sebagai aset tanpa terlihat seperti saldo dompet.',
            ],
        ],
        [
            'version' => '1.1.2',
            'title' => 'Pergantian hari kini mengikuti WIB',
            'released_at' => '2026-09-10',
            'highlights' => [
                'Ringkasan pengeluaran harian sekarang berganti tepat pukul 00.00 WIB.',
                'Tanggal transaksi, grafik, dan periode bulanan kini konsisten menggunakan waktu Jakarta.',
            ],
        ],
        [
            'version' => '1.1.1',
            'title' => 'Perbaikan perhitungan investasi',
            'released_at' => '2026-09-10',
            'highlights' => [
                'Biaya admin pembelian tidak lagi dihitung sebagai modal investasi.',
                'Perhitungan untung dan rugi investasi kini memakai nilai investasi bersih.',
            ],
        ],
        [
            'version' => '1.1.0',
            'title' => 'Investasi dan analisis makin lengkap',
            'released_at' => '2026-09-10',
            'highlights' => [
                'Biaya admin investasi sekarang dipotong terpisah dari rekening.',
                'Aktivitas beli dan jual investasi tampil di halaman Riwayat.',
                'Pemilihan dompet investasi kini lebih nyaman digunakan di HP.',
                'Grafik keuangan sekarang memiliki pilihan periode semua waktu.',
            ],
        ],
    ],
];
