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
    'current_version' => '1.1.6',

    'items' => [
        [
            'version' => '1.1.6',
            'title' => 'Dana darurat kini bisa ditransfer',
            'released_at' => '2026-09-20',
            'highlights' => [
                'Dana darurat sekarang dapat dipindahkan langsung ke rekening atau dompet tujuan.',
                'Nominal transfer dan biaya admin dipotong akurat dari tabungan dana darurat.',
            ],
        ],
        [
            'version' => '1.1.5',
            'title' => 'Dana darurat siap dipakai saat mendesak',
            'released_at' => '2026-09-16',
            'highlights' => [
                'Tandai tabungan sebagai dana darurat agar dapat dipilih sebagai metode pembayaran pengeluaran mendesak.',
                'Pemakaian dana darurat tercatat di riwayat dan langsung mengurangi saldo tabungan terkait.',
            ],
        ],
        [
            'version' => '1.1.4',
            'title' => 'Talangan kini perlu dipilih terlebih dahulu',
            'released_at' => '2026-09-15',
            'highlights' => [
                'Transaksi bersama sekarang tidak otomatis membuat utang atau talangan.',
                'Aktifkan Catat sebagai talangan hanya saat pasangan memang perlu mengganti uang.',
            ],
        ],
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
