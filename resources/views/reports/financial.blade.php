<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light">
        <title>Laporan Keuangan {{ $space->name }}</title>
        @vite(['resources/css/report.css', 'resources/js/report.ts'])
    </head>
    <body class="min-h-screen px-3 py-5 sm:px-6 sm:py-8">
        <div class="mx-auto mb-4 flex max-w-5xl items-center justify-between gap-3 print:hidden">
            <div>
                <p class="text-sm font-bold text-zinc-900">Preview laporan siap dicetak</p>
                <p class="text-xs text-zinc-500">Pilih “Simpan sebagai PDF” pada dialog cetak browser.</p>
            </div>
            <button
                type="button"
                data-print-report
                class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-gradient-to-r from-indigo-600 to-rose-500 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-500/20"
            >
                Cetak / Simpan PDF
            </button>
        </div>

        <main class="report-sheet mx-auto max-w-5xl overflow-hidden rounded-[2rem] border border-indigo-100 bg-white">
            <header class="bg-gradient-to-br from-indigo-950 via-indigo-900 to-rose-900 px-6 py-8 text-white sm:px-10 sm:py-10">
                <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-end">
                    <div>
                        <p class="mb-2 text-xs font-bold tracking-[0.22em] text-indigo-200 uppercase">Couple Finance</p>
                        <h1 class="text-3xl font-black tracking-tight sm:text-4xl">Laporan Keuangan Lengkap</h1>
                        <p class="mt-2 text-sm text-indigo-100">{{ $space->name }} · {{ $periodLabel }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-xs text-indigo-100 backdrop-blur">
                        <p class="font-semibold text-white">Dibuat {{ $generatedAt->format('d M Y') }}</p>
                        <p>{{ $generatedAt->format('H:i') }} WIB · {{ number_format($summary['transaction_count'], 0, ',', '.') }} transaksi</p>
                    </div>
                </div>
            </header>

            <div class="space-y-8 p-5 sm:p-8">
                <section class="report-section space-y-4">
                    <div>
                        <p class="text-xs font-bold tracking-[0.16em] text-indigo-600 uppercase">Ikhtisar</p>
                        <h2 class="text-xl font-black text-zinc-900">Ringkasan Arus Kas</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <article class="report-card rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                            <p class="text-xs font-semibold text-emerald-700">Total pemasukan</p>
                            <p class="mt-2 text-lg font-black text-emerald-950">Rp {{ number_format($summary['income'], 0, ',', '.') }}</p>
                        </article>
                        <article class="report-card rounded-2xl border border-rose-100 bg-rose-50 p-4">
                            <p class="text-xs font-semibold text-rose-700">Total uang keluar</p>
                            <p class="mt-2 text-lg font-black text-rose-950">Rp {{ number_format($summary['outflow'], 0, ',', '.') }}</p>
                        </article>
                        <article class="report-card rounded-2xl border border-amber-100 bg-amber-50 p-4">
                            <p class="text-xs font-semibold text-amber-700">Biaya admin</p>
                            <p class="mt-2 text-lg font-black text-amber-950">Rp {{ number_format($summary['transfer_fees'], 0, ',', '.') }}</p>
                        </article>
                        <article @class([
                            'report-card rounded-2xl border p-4',
                            'border-indigo-100 bg-indigo-50' => $summary['surplus'] >= 0,
                            'border-red-100 bg-red-50' => $summary['surplus'] < 0,
                        ])>
                            <p class="text-xs font-semibold text-zinc-600">Surplus / defisit</p>
                            <p @class([
                                'mt-2 text-lg font-black',
                                'text-indigo-950' => $summary['surplus'] >= 0,
                                'text-red-700' => $summary['surplus'] < 0,
                            ])>Rp {{ number_format($summary['surplus'], 0, ',', '.') }}</p>
                        </article>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($members as $member)
                            <article class="report-card rounded-2xl border border-zinc-200 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="font-black text-zinc-900">{{ $member['name'] }}</h3>
                                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-[10px] font-bold text-zinc-600">Per orang</span>
                                </div>
                                <dl class="mt-4 grid grid-cols-3 gap-2 text-xs">
                                    <div><dt class="text-zinc-500">Pemasukan</dt><dd class="mt-1 font-bold text-emerald-700">Rp {{ number_format($member['income'], 0, ',', '.') }}</dd></div>
                                    <div><dt class="text-zinc-500">Pengeluaran</dt><dd class="mt-1 font-bold text-rose-700">Rp {{ number_format($member['expense'], 0, ',', '.') }}</dd></div>
                                    <div><dt class="text-zinc-500">Saldo dompet</dt><dd class="mt-1 font-bold text-zinc-900">Rp {{ number_format($member['wallet_balance'], 0, ',', '.') }}</dd></div>
                                </dl>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="report-section space-y-4 border-t border-zinc-200 pt-7">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold tracking-[0.16em] text-indigo-600 uppercase">Aset</p>
                            <h2 class="text-xl font-black text-zinc-900">Posisi Keuangan</h2>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-zinc-500">Total kekayaan</p>
                            <p class="text-xl font-black text-indigo-700">Rp {{ number_format($summary['net_worth'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @forelse ($wallets as $wallet)
                            <article class="report-card rounded-2xl border border-zinc-200 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div><p class="font-bold text-zinc-900">{{ $wallet->name }}</p><p class="text-xs text-zinc-500">{{ $wallet->type === 'joint' ? 'Dompet bersama' : ($wallet->user?->nickname ?: $wallet->user?->name ?: 'Pribadi') }} · {{ ucfirst($wallet->wallet_type) }}</p></div>
                                    <span class="h-3 w-3 rounded-full bg-indigo-500"></span>
                                </div>
                                <p class="mt-4 text-lg font-black">Rp {{ number_format((float) $wallet->balance, 0, ',', '.') }}</p>
                            </article>
                        @empty
                            <p class="text-sm text-zinc-500">Belum ada dompet aktif.</p>
                        @endforelse
                    </div>
                </section>

                <section class="report-section grid gap-5 border-t border-zinc-200 pt-7 lg:grid-cols-2">
                    <div class="space-y-4">
                        <div><p class="text-xs font-bold tracking-[0.16em] text-indigo-600 uppercase">Peruntukan</p><h2 class="text-xl font-black">Pribadi vs Bersama</h2></div>
                        <div class="grid grid-cols-2 gap-3">
                            <article class="report-card rounded-2xl bg-violet-50 p-4"><p class="text-xs font-semibold text-violet-700">Pribadi</p><p class="mt-2 text-lg font-black text-violet-950">Rp {{ number_format($scopeSummary['personal'], 0, ',', '.') }}</p></article>
                            <article class="report-card rounded-2xl bg-pink-50 p-4"><p class="text-xs font-semibold text-pink-700">Bersama</p><p class="mt-2 text-lg font-black text-pink-950">Rp {{ number_format($scopeSummary['shared'], 0, ',', '.') }}</p></article>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div><p class="text-xs font-bold tracking-[0.16em] text-indigo-600 uppercase">Talangan</p><h2 class="text-xl font-black">Status Antar Pasangan</h2></div>
                        <article class="report-card rounded-2xl border border-zinc-200 p-4">
                            @if ($settlementDebt['amount_owed'] > 0)
                                <p class="text-sm text-zinc-600">{{ $settlementDebt['debtor_name'] }} perlu membayar {{ $settlementDebt['creditor_name'] }}</p>
                                <p class="mt-2 text-2xl font-black text-amber-700">Rp {{ number_format($settlementDebt['amount_owed'], 0, ',', '.') }}</p>
                                <p class="mt-1 text-xs text-zinc-500">{{ $settlementDebt['unsettled_splits_count'] }} transaksi belum selesai</p>
                            @else
                                <p class="font-bold text-emerald-700">Tidak ada talangan yang perlu dilunasi.</p>
                            @endif
                        </article>
                    </div>
                </section>

                <section class="report-section space-y-4 border-t border-zinc-200 pt-7">
                    <div><p class="text-xs font-bold tracking-[0.16em] text-indigo-600 uppercase">Kontrol belanja</p><h2 class="text-xl font-black">Anggaran Harian & Bulanan</h2></div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @forelse ($budgetSummary as $budget)
                            <article class="report-card rounded-2xl border border-zinc-200 p-4">
                                <div class="flex items-start justify-between gap-3"><div><p class="font-bold">{{ $budget['name'] }}</p><p class="text-xs text-zinc-500">{{ $budget['period'] }} · {{ $budget['scope'] }} · {{ $budget['category'] }}</p></div><span @class(['rounded-full px-2.5 py-1 text-[10px] font-bold', 'bg-emerald-100 text-emerald-700' => $budget['remaining'] >= 0, 'bg-red-100 text-red-700' => $budget['remaining'] < 0])>{{ $budget['percentage'] }}%</span></div>
                                <progress class="mt-4 h-2 w-full overflow-hidden rounded-full accent-indigo-600" value="{{ min(100, $budget['percentage']) }}" max="100">{{ $budget['percentage'] }}%</progress>
                                <div class="mt-3 flex justify-between text-xs"><span class="text-zinc-500">Terpakai Rp {{ number_format($budget['spent'], 0, ',', '.') }}</span><span class="font-bold">Batas Rp {{ number_format($budget['limit'], 0, ',', '.') }}</span></div>
                            </article>
                        @empty
                            <p class="text-sm text-zinc-500">Belum ada anggaran aktif.</p>
                        @endforelse
                    </div>
                </section>

                <section class="report-section grid gap-5 border-t border-zinc-200 pt-7 lg:grid-cols-2">
                    <div class="space-y-4">
                        <div><p class="text-xs font-bold tracking-[0.16em] text-indigo-600 uppercase">Tujuan</p><h2 class="text-xl font-black">Tabungan</h2></div>
                        @forelse ($savingsGoals as $goal)
                            <article class="report-card rounded-2xl border border-zinc-200 p-4"><div class="flex justify-between gap-3"><div><p class="font-bold">{{ $goal->name }}</p><p class="text-xs text-zinc-500">{{ $goal->status === 'completed' ? 'Selesai' : 'Berjalan' }}</p></div><p class="font-black text-indigo-700">{{ $goal->percentage }}%</p></div><p class="mt-3 text-sm"><strong>Rp {{ number_format((float) $goal->current_amount, 0, ',', '.') }}</strong> dari Rp {{ number_format((float) $goal->target_amount, 0, ',', '.') }}</p></article>
                        @empty
                            <p class="text-sm text-zinc-500">Belum ada target tabungan.</p>
                        @endforelse
                    </div>
                    <div class="space-y-4">
                        <div><p class="text-xs font-bold tracking-[0.16em] text-indigo-600 uppercase">Komitmen</p><h2 class="text-xl font-black">Langganan Aktif</h2></div>
                        @forelse ($subscriptions as $subscription)
                            <article class="report-card flex items-center justify-between gap-3 rounded-2xl border border-zinc-200 p-4"><div><p class="font-bold">{{ $subscription->name }}</p><p class="text-xs text-zinc-500">Tagihan berikutnya {{ $subscription->next_billing_date->format('d M Y') }} · {{ ucfirst($subscription->billing_cycle) }}</p></div><p class="font-black text-rose-700">Rp {{ number_format((float) $subscription->amount, 0, ',', '.') }}</p></article>
                        @empty
                            <p class="text-sm text-zinc-500">Tidak ada langganan aktif.</p>
                        @endforelse
                    </div>
                </section>

                <section class="report-section space-y-4 border-t border-zinc-200 pt-7">
                    <div><p class="text-xs font-bold tracking-[0.16em] text-indigo-600 uppercase">Analisis</p><h2 class="text-xl font-black">Pengeluaran per Kategori</h2></div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @forelse ($categorySummary as $category)
                            <article class="report-card flex items-center justify-between gap-3 rounded-xl bg-zinc-50 px-4 py-3"><div><p class="text-sm font-bold">{{ $category['name'] }}</p><p class="text-xs text-zinc-500">{{ $category['percentage'] }}% dari pengeluaran</p></div><p class="text-sm font-black">Rp {{ number_format($category['total'], 0, ',', '.') }}</p></article>
                        @empty
                            <p class="text-sm text-zinc-500">Belum ada pengeluaran pada periode ini.</p>
                        @endforelse
                    </div>
                </section>

                <section class="space-y-4 border-t border-zinc-200 pt-7">
                    <div><p class="text-xs font-bold tracking-[0.16em] text-indigo-600 uppercase">Audit trail</p><h2 class="text-xl font-black">Rincian Seluruh Transaksi</h2><p class="text-xs text-zinc-500">Nominal transfer tidak dihitung sebagai pengeluaran; hanya biaya admin yang masuk arus kas keluar.</p></div>
                    <div class="overflow-x-auto rounded-2xl border border-zinc-200">
                        <table class="report-table w-full min-w-[760px] border-collapse text-left text-xs">
                            <thead class="bg-zinc-900 text-white"><tr><th class="px-3 py-3">Tanggal</th><th class="px-3 py-3">Transaksi</th><th class="px-3 py-3">Pemilik</th><th class="px-3 py-3">Dompet</th><th class="px-3 py-3">Cakupan</th><th class="px-3 py-3 text-right">Nominal</th></tr></thead>
                            <tbody class="divide-y divide-zinc-100">
                                @forelse ($transactions as $transaction)
                                    <tr class="report-table-row"><td class="whitespace-nowrap px-3 py-3 text-zinc-500">{{ $transaction->transaction_date->format('d M Y H:i') }}</td><td class="px-3 py-3"><p class="font-bold text-zinc-900">{{ $transaction->title ?: ($transaction->category?->name ?? 'Transaksi') }}</p><p class="text-[10px] text-zinc-500">{{ $transaction->type === 'income' ? 'Pemasukan' : ($transaction->type === 'expense' ? 'Pengeluaran' : 'Transfer') }}{{ (float) $transaction->fee_amount > 0 ? ' · Admin Rp '.number_format((float) $transaction->fee_amount, 0, ',', '.') : '' }}</p></td><td class="px-3 py-3">{{ $transaction->user?->nickname ?: $transaction->user?->name ?: '-' }}</td><td class="px-3 py-3">{{ $transaction->wallet?->name ?? '-' }}{{ $transaction->toWallet ? ' → '.$transaction->toWallet->name : '' }}</td><td class="px-3 py-3">{{ $transaction->scope === 'shared' ? 'Bersama' : 'Pribadi' }}</td><td @class(['whitespace-nowrap px-3 py-3 text-right font-black', 'text-emerald-700' => $transaction->type === 'income', 'text-rose-700' => $transaction->type === 'expense', 'text-indigo-700' => $transaction->type === 'transfer'])>{{ $transaction->type === 'income' ? '+' : ($transaction->type === 'expense' ? '-' : '') }}Rp {{ number_format((float) $transaction->amount, 0, ',', '.') }}</td></tr>
                                @empty
                                    <tr><td colspan="6" class="px-4 py-10 text-center text-zinc-500">Tidak ada transaksi untuk filter yang dipilih.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <footer class="border-t border-zinc-200 bg-zinc-50 px-6 py-5 text-center text-[10px] text-zinc-500">
                Laporan ini dibuat otomatis oleh Couple Finance · Transfer antardompet tidak menggandakan pemasukan atau pengeluaran.
            </footer>
        </main>
    </body>
</html>
