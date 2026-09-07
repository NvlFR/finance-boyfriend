<?php

namespace App\Http\Controllers;

use App\Http\Requests\Investment\StoreInvestmentRequest;
use App\Http\Requests\Investment\StoreInvestmentTransactionRequest;
use App\Http\Requests\Investment\UpdateInvestmentPriceRequest;
use App\Models\Investment;
use App\Models\Wallet;
use App\Services\InvestmentService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InvestmentController extends Controller
{
    public function __construct(private InvestmentService $investmentService) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Investment::class);

        $user = $request->user();
        $space = $user->currentCoupleSpace;
        $investments = Investment::query()
            ->where('couple_space_id', $space->id)
            ->where('is_active', true)
            ->with([
                'user:id,name,nickname',
                'transactions' => fn ($query) => $query
                    ->with(['user:id,name,nickname', 'wallet:id,name'])
                    ->latest('transaction_date')
                    ->latest('id')
                    ->limit(10),
            ])
            ->latest()
            ->get();
        $wallets = Wallet::query()
            ->where('couple_space_id', $space->id)
            ->where('is_active', true)
            ->where(fn ($query) => $query->where('type', 'joint')->orWhere('user_id', $user->id))
            ->with('user:id,name,nickname')
            ->latest()
            ->get();

        $summary = $investments->reduce(function (array $summary, Investment $investment): array {
            $marketValue = BigDecimal::of($investment->quantity)
                ->multipliedBy($investment->current_price)
                ->toScale(2, RoundingMode::HalfUp);
            $costBasis = BigDecimal::of($investment->quantity)
                ->multipliedBy($investment->average_buy_price)
                ->toScale(2, RoundingMode::HalfUp);

            $summary['market_value'] = BigDecimal::of($summary['market_value'])->plus($marketValue)->__toString();
            $summary['cost_basis'] = BigDecimal::of($summary['cost_basis'])->plus($costBasis)->__toString();
            $summary['unrealized_profit_loss'] = BigDecimal::of($summary['unrealized_profit_loss'])
                ->plus($marketValue->minus($costBasis))
                ->__toString();
            $summary['realized_profit_loss'] = BigDecimal::of($summary['realized_profit_loss'])
                ->plus($investment->realized_profit_loss)
                ->__toString();

            return $summary;
        }, [
            'market_value' => '0.00',
            'cost_basis' => '0.00',
            'unrealized_profit_loss' => '0.00',
            'realized_profit_loss' => '0.00',
        ]);

        return Inertia::render('Investments/Index', [
            'investments' => $investments,
            'wallets' => $wallets,
            'summary' => $summary,
        ]);
    }

    public function store(StoreInvestmentRequest $request): RedirectResponse
    {
        Gate::authorize('create', Investment::class);
        $user = $request->user();
        $validated = $request->validated();

        Investment::create([
            ...$validated,
            'couple_space_id' => $user->current_couple_space_id,
            'user_id' => $validated['scope'] === 'personal' ? $user->id : null,
        ]);

        return back()->with('success', 'Aset investasi berhasil ditambahkan. Catat pembelian pertamanya, ya.');
    }

    public function transact(
        StoreInvestmentTransactionRequest $request,
        Investment $investment,
    ): RedirectResponse {
        $this->investmentService->recordTransaction($request->user(), $investment, $request->validated());

        return back()->with('success', $request->string('type')->value() === 'buy'
            ? 'Pembelian investasi berhasil dicatat.'
            : 'Penjualan investasi berhasil dicatat.');
    }

    public function updatePrice(
        UpdateInvestmentPriceRequest $request,
        Investment $investment,
    ): RedirectResponse {
        $investment->update(['current_price' => $request->validated('current_price')]);

        return back()->with('success', 'Harga terkini berhasil diperbarui.');
    }

    public function destroy(Request $request, Investment $investment): RedirectResponse
    {
        Gate::authorize('delete', $investment);

        if (! BigDecimal::of($investment->quantity)->isZero()) {
            throw ValidationException::withMessages([
                'investment' => 'Jual seluruh unit terlebih dahulu sebelum menghapus aset.',
            ]);
        }

        $investment->delete();

        return back()->with('success', 'Aset investasi berhasil dihapus.');
    }
}
