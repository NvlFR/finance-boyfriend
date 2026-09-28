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
    'current_version' => '1.1.8',

    'items' => [
        [
            'version' => '1.1.8',
            'title' => 'Perlindungan data dan keamanan akun',
            'released_at' => '2026-09-27',
            'highlights' => [
                'Arsip dompet dan investasi mempertahankan riwayat; akun dengan data ruang pasangan terlindungi dari penghapusan.',
                'Login Google tetap meminta verifikasi dua langkah, dan akun wajib memverifikasi email.',
                'Pembagian transaksi diperbarui dengan benar, pelunasan terlindungi, serta biaya investasi tercantum dalam laporan PDF.',
                'Filter riwayat, ukuran teks, posisi tur fitur, dan penguncian scroll dialog diperbaiki.',
            ],
            'tour' => [
                [
                    'path' => '/dashboard',
                    'target' => '[data-tour="wealth-summary"]',
                    'title' => 'Ringkasan kekayaan lebih jelas',
                    'description' => 'Total kekayaan mencakup dompet, tabungan, dan investasi. Rincian di bawahnya khusus menunjukkan saldo setiap dompet.',
                ],
                [
                    'path' => '/transactions',
                    'target' => '[data-tour="history-filter"]',
                    'title' => 'Satu filter untuk semua aktivitas',
                    'description' => 'Pencarian dan filter sekarang ikut menyaring transaksi, setoran tabungan, serta aktivitas investasi.',
                ],
                [
                    'path' => '/goals',
                    'target' => '[data-tour="savings-overview"]',
                    'title' => 'Setoran tabungan lebih aman',
                    'description' => 'Saat menyetor, sumber dana kini dipilih melalui kartu dompet yang jelas. Dana dari luar aplikasi juga diberi penjelasan khusus.',
                ],
            ],
        ],
        [
            'version' => '1.1.7',
            'title' => 'Saldo lebih aman, riwayat lebih konsisten',
            'released_at' => '2026-09-20',
            'highlights' => [
                'Perlindungan saldo saat mengedit atau menghapus transaksi dan mengubah dompet.',
                'Investasi tetap tersimpan saat bergabung ke ruang pasangan.',
                'Tanggal riwayat mengikuti WIB; filter investasi dan tabungan kini konsisten.',
                'Biaya investasi masuk pengeluaran dashboard, dengan navigasi dan pemilihan sumber setoran yang lebih jelas.',
            ],
            'tour' => [
                [
                    'path' => '/dashboard',
                    'target' => '[data-tour="wealth-summary"]',
                    'title' => 'Ringkasan kekayaan lebih jelas',
                    'description' => 'Total kekayaan mencakup dompet, tabungan, dan investasi. Rincian di bawahnya khusus menunjukkan saldo setiap dompet.',
                ],
                [
                    'path' => '/transactions',
                    'target' => '[data-tour="history-filter"]',
                    'title' => 'Satu filter untuk semua aktivitas',
                    'description' => 'Pencarian dan filter sekarang ikut menyaring transaksi, setoran tabungan, serta aktivitas investasi.',
                ],
                [
                    'path' => '/goals',
                    'target' => '[data-tour="savings-overview"]',
                    'title' => 'Setoran tabungan lebih aman',
                    'description' => 'Saat menyetor, sumber dana kini dipilih melalui kartu dompet yang jelas. Dana dari luar aplikasi juga diberi penjelasan khusus.',
                ],
            ],
        ],
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
