<?php

namespace App\Http\Controllers;

use App\Http\Requests\BirthdaySurprise\UpdateBirthdaySurpriseRequest;
use App\Models\BirthdaySurprise;
use App\Services\BirthdaySurpriseService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BirthdaySurpriseController extends Controller
{
    public function __construct(
        protected BirthdaySurpriseService $birthdaySurpriseService,
    ) {}

    public function edit(Request $request): Response
    {
        $space = $request->user()->currentCoupleSpace()
            ->with(['userOne', 'userTwo', 'birthdaySurprise'])
            ->first();
        $surprise = $space?->birthdaySurprise;

        $surprise
            ? Gate::authorize('update', $surprise)
            : Gate::authorize('create', BirthdaySurprise::class);

        return Inertia::render('BirthdaySurprise/Edit', [
            'partner' => $space?->getPartnerOf($request->user()),
            'surprise' => $surprise ? $this->birthdaySurpriseService->editorPayload($surprise) : null,
            'defaults' => [
                ...$this->birthdaySurpriseService->defaultMessages(),
                'startsAt' => CarbonImmutable::now(BirthdaySurpriseService::DISPLAY_TIMEZONE)
                    ->addHour()->startOfMinute()->format('Y-m-d\TH:i'),
                'vouchers' => ['Dinner pilihan kamu', 'Satu hari full quality time', 'Jalan-jalan bersama'],
            ],
            'durationDays' => BirthdaySurpriseService::DURATION_DAYS,
            'timezone' => BirthdaySurpriseService::DISPLAY_TIMEZONE,
        ]);
    }

    public function update(UpdateBirthdaySurpriseRequest $request): RedirectResponse
    {
        $existing = $request->user()->currentCoupleSpace?->birthdaySurprise;

        $existing
            ? Gate::authorize('update', $existing)
            : Gate::authorize('create', BirthdaySurprise::class);

        $this->birthdaySurpriseService->save($request->user(), $request->validated());

        return to_route('birthday-surprise.edit')
            ->with('success', 'Surprise berhasil disimpan dan dijadwalkan selama 7 hari.');
    }
}
