<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\CoupleSpace;
use App\Models\SavingsGoal;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

class TransactionReportService
{
    /**
     * @param  Collection<int, Transaction>  $transactions
     */
    public function excel(Collection $transactions): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'finance-report-');

        if ($temporaryPath === false) {
            throw new RuntimeException('Tidak dapat membuat file Excel sementara.');
        }

        $archive = new ZipArchive;

        if ($archive->open($temporaryPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($temporaryPath);

            throw new RuntimeException('Tidak dapat membuat arsip Excel.');
        }

        $archive->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $archive->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $archive->addFromString('xl/workbook.xml', $this->workbookXml());
        $archive->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml());
        $archive->addFromString('xl/styles.xml', $this->stylesXml());
        $archive->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($transactions));
        $archive->close();

        $contents = file_get_contents($temporaryPath);
        @unlink($temporaryPath);

        if ($contents === false) {
            throw new RuntimeException('Tidak dapat membaca file Excel yang dibuat.');
        }

        return $contents;
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @param  Collection<int, Transaction>  $budgetTransactions
     * @param  Collection<int, Wallet>  $wallets
     * @param  Collection<int, Budget>  $budgets
     * @param  Collection<int, SavingsGoal>  $savingsGoals
     * @param  Collection<int, Subscription>  $subscriptions
     * @param  array<string, mixed>  $settlementDebt
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function financialReport(
        CoupleSpace $space,
        Collection $transactions,
        Collection $budgetTransactions,
        Collection $wallets,
        Collection $budgets,
        Collection $savingsGoals,
        Collection $subscriptions,
        array $settlementDebt,
        array $filters,
    ): array {
        $incomes = $transactions->where('type', 'income');
        $expenses = $transactions->where('type', 'expense');
        $transfers = $transactions->where('type', 'transfer');
        $income = (float) $incomes->sum('amount');
        $expense = (float) $expenses->sum('amount');
        $transferFees = (float) $transfers->sum('fee_amount');
        $savedAmount = (float) $savingsGoals->sum('current_amount');

        $members = collect([$space->userOne, $space->userTwo])
            ->filter()
            ->map(function ($member) use ($incomes, $expenses, $transfers, $wallets): array {
                $memberFees = (float) $transfers->where('user_id', $member->id)->sum('fee_amount');

                return [
                    'name' => $member->nickname ?: $member->name,
                    'income' => (float) $incomes->where('user_id', $member->id)->sum('amount'),
                    'expense' => (float) $expenses->where('user_id', $member->id)->sum('amount') + $memberFees,
                    'wallet_balance' => (float) $wallets->where('user_id', $member->id)->sum('balance'),
                ];
            })
            ->values();

        $categorySummary = $expenses
            ->groupBy(fn (Transaction $transaction): string => $transaction->category?->name ?? 'Tanpa Kategori')
            ->map(function (Collection $categoryTransactions, string $name) use ($expense): array {
                $total = (float) $categoryTransactions->sum('amount');

                return [
                    'name' => $name,
                    'total' => $total,
                    'percentage' => $expense > 0 ? round(($total / $expense) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('total')
            ->values();

        $budgetSummary = $budgets->map(function (Budget $budget) use ($budgetTransactions): array {
            $periodTransactions = $budgetTransactions
                ->where('scope', $budget->scope)
                ->when(
                    $budget->period === 'daily',
                    fn (Collection $items): Collection => $items->filter(
                        fn (Transaction $transaction): bool => $transaction->transaction_date->isToday()
                    ),
                )
                ->when(
                    $budget->category_id !== null,
                    fn (Collection $items): Collection => $items->where('category_id', $budget->category_id),
                )
                ->when(
                    $budget->scope === 'personal' && $budget->user_id !== null,
                    fn (Collection $items): Collection => $items->where('user_id', $budget->user_id),
                );
            $spent = (float) $periodTransactions->sum('amount');
            $limit = (float) $budget->limit_amount;

            return [
                'name' => $budget->name,
                'period' => $budget->period === 'daily' ? 'Harian' : 'Bulanan',
                'scope' => $budget->scope === 'shared' ? 'Bersama' : ($budget->user?->nickname ?: $budget->user?->name ?: 'Pribadi'),
                'category' => $budget->category?->name ?? 'Semua kategori',
                'limit' => $limit,
                'spent' => $spent,
                'remaining' => $limit - $spent,
                'percentage' => $limit > 0 ? round(($spent / $limit) * 100, 1) : 0,
            ];
        })->values();

        return [
            'space' => $space,
            'generatedAt' => now()->timezone('Asia/Jakarta'),
            'periodLabel' => $this->periodLabel($filters),
            'activeFilters' => collect($filters)->filter(fn ($value): bool => $value !== null && $value !== '')->all(),
            'summary' => [
                'income' => $income,
                'expense' => $expense,
                'transfer_fees' => $transferFees,
                'outflow' => $expense + $transferFees,
                'surplus' => $income - $expense - $transferFees,
                'transfer_amount' => (float) $transfers->sum('amount'),
                'transaction_count' => $transactions->count(),
                'net_worth' => (float) $wallets->sum('balance') + $savedAmount,
            ],
            'members' => $members,
            'scopeSummary' => [
                'personal' => (float) $expenses->where('scope', 'personal')->sum('amount') + $transferFees,
                'shared' => (float) $expenses->where('scope', 'shared')->sum('amount'),
            ],
            'categorySummary' => $categorySummary,
            'wallets' => $wallets,
            'budgetSummary' => $budgetSummary,
            'savingsGoals' => $savingsGoals,
            'subscriptions' => $subscriptions,
            'settlementDebt' => $settlementDebt,
            'transactions' => $transactions,
        ];
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     */
    private function worksheetXml(Collection $transactions): string
    {
        $headers = [
            'ID', 'Tanggal', 'Judul Transaksi', 'Tipe', 'Cakupan', 'Kategori',
            'Dompet Asal', 'Dompet Tujuan', 'Nominal (Rp)', 'Biaya Admin (Rp)',
            'Total Potong Dompet Asal (Rp)', 'Dicatat Oleh', 'Catatan',
        ];
        $rows = [$headers];

        foreach ($transactions as $transaction) {
            $rows[] = $this->reportRow($transaction);
        }

        $xmlRows = [];

        foreach ($rows as $rowIndex => $row) {
            $cells = [];

            foreach ($row as $columnIndex => $value) {
                $reference = $this->columnName($columnIndex + 1).($rowIndex + 1);
                $isCurrency = $rowIndex > 0 && in_array($columnIndex, [8, 9, 10], true);

                if ($isCurrency) {
                    $cells[] = '<c r="'.$reference.'" s="2" t="n"><v>'.(float) $value.'</v></c>';
                } else {
                    $style = $rowIndex === 0 ? ' s="1"' : '';
                    $cells[] = '<c r="'.$reference.'"'.$style.' t="inlineStr"><is><t xml:space="preserve">'.$this->xml((string) $value).'</t></is></c>';
                }
            }

            $xmlRows[] = '<row r="'.($rowIndex + 1).'">'.implode('', $cells).'</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols><col min="1" max="1" width="8" customWidth="1"/><col min="2" max="2" width="14" customWidth="1"/><col min="3" max="8" width="22" customWidth="1"/><col min="9" max="11" width="24" customWidth="1"/><col min="12" max="13" width="24" customWidth="1"/></cols>'
            .'<sheetData>'.implode('', $xmlRows).'</sheetData><autoFilter ref="A1:M1"/>'
            .'</worksheet>';
    }

    /**
     * @return array<int, int|string>
     */
    private function reportRow(Transaction $transaction): array
    {
        $amount = (float) $transaction->amount;
        $feeAmount = $transaction->type === 'transfer' ? (float) $transaction->fee_amount : 0;

        return [
            $transaction->id,
            $transaction->transaction_date?->format('Y-m-d H:i') ?? '',
            $transaction->title ?: ($transaction->category?->name ?? 'Transaksi'),
            match ($transaction->type) {
                'income' => 'Pemasukan',
                'expense' => 'Pengeluaran',
                default => 'Transfer',
            },
            $transaction->scope === 'shared' ? 'Bersama' : 'Pribadi',
            $transaction->category?->name ?? '-',
            $transaction->wallet?->name ?? '-',
            $transaction->toWallet?->name ?? '-',
            $amount,
            $feeAmount,
            $transaction->type === 'transfer' ? $amount + $feeAmount : $amount,
            $transaction->user?->name ?? '-',
            $transaction->notes ?? '',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function periodLabel(array $filters): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        return match (true) {
            $startDate && $endDate => "{$startDate} sampai {$endDate}",
            $startDate => "Mulai {$startDate}",
            $endDate => "Sampai {$endDate}",
            default => 'Semua periode',
        };
    }

    private function columnName(int $column): string
    {
        $name = '';

        while ($column > 0) {
            $column--;
            $name = chr(65 + ($column % 26)).$name;
            $column = intdiv($column, 26);
        }

        return $name;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Transaksi" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF4F46E5"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFill="1" applyFont="1"/><xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs></styleSheet>';
    }
}
