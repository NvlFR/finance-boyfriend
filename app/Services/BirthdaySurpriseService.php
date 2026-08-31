<?php

namespace App\Services;

use App\Models\BirthdaySurprise;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class BirthdaySurpriseService
{
    public const DISPLAY_TIMEZONE = 'Asia/Jakarta';

    public const DURATION_DAYS = 7;

    /** @return array<string, mixed>|null */
    public function activeFor(User $recipient): ?array
    {
        if (! $recipient->current_couple_space_id) {
            return null;
        }

        $surprise = BirthdaySurprise::query()
            ->where('couple_space_id', $recipient->current_couple_space_id)
            ->where('recipient_user_id', $recipient->id)
            ->where('is_enabled', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->with(['creator:id,name,nickname,avatar_url', 'recipient:id,name,nickname,avatar_url'])
            ->first();

        return $surprise ? $this->displayPayload($surprise) : null;
    }

    /** @param array<string, mixed> $data */
    public function save(User $creator, array $data): BirthdaySurprise
    {
        $space = $creator->currentCoupleSpace()->with('userTwo')->firstOrFail();
        $recipient = $space->getPartnerOf($creator);
        abort_unless($recipient !== null, 422, 'Pasangan belum terhubung.');

        $existing = BirthdaySurprise::query()->where('couple_space_id', $space->id)->first();
        $existingPhotos = $existing === null ? [] : ($existing->photos ?? []);
        $keptPhotoInput = $data['kept_photos'] ?? [];
        $keptPhotoInput = is_array($keptPhotoInput)
            ? array_values(array_filter($keptPhotoInput, is_string(...)))
            : [];
        $keptPhotos = collect($keptPhotoInput)
            ->filter(fn (string $path): bool => in_array($path, $existingPhotos, true))
            ->unique()
            ->values();
        $photoInput = $data['photos'] ?? [];
        $photoInput = is_array($photoInput)
            ? array_values(array_filter($photoInput, fn (mixed $photo): bool => $photo instanceof UploadedFile))
            : [];
        $newPhotoPaths = collect($photoInput)->map(function (UploadedFile $photo) use ($space): string {
            $path = $photo->store("birthday-surprises/{$space->id}", 'public');

            if ($path === false) {
                throw new RuntimeException('Foto kejutan gagal disimpan.');
            }

            return $path;
        });
        $voucherInput = $data['vouchers'] ?? [];
        $vouchers = is_array($voucherInput)
            ? array_values(array_filter($voucherInput, is_string(...)))
            : [];
        $startsAt = CarbonImmutable::createFromFormat('Y-m-d\TH:i', (string) $data['starts_at'], self::DISPLAY_TIMEZONE)->utc();

        try {
            $surprise = DB::transaction(fn (): BirthdaySurprise => BirthdaySurprise::query()->updateOrCreate(
                ['couple_space_id' => $space->id],
                [
                    'creator_user_id' => $creator->id,
                    'recipient_user_id' => $recipient->id,
                    'opening_message' => (string) $data['opening_message'],
                    'appreciation_message' => (string) $data['appreciation_message'],
                    'love_letter' => (string) $data['love_letter'],
                    'closing_message' => (string) $data['closing_message'],
                    'photos' => $keptPhotos->concat($newPhotoPaths)->values()->all(),
                    'vouchers' => $vouchers,
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->addDays(self::DURATION_DAYS),
                    'is_enabled' => (bool) $data['is_enabled'],
                ],
            ));
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newPhotoPaths->all());

            throw $exception;
        }

        Storage::disk('public')->delete(array_values(array_diff($existingPhotos, $keptPhotos->all())));

        return $surprise->load(['creator', 'recipient']);
    }

    /** @return array<string, mixed> */
    public function editorPayload(BirthdaySurprise $surprise): array
    {
        return [
            'id' => $surprise->id,
            'openingMessage' => $surprise->opening_message,
            'appreciationMessage' => $surprise->appreciation_message,
            'loveLetter' => $surprise->love_letter,
            'closingMessage' => $surprise->closing_message,
            'photos' => collect($surprise->photos ?? [])->map(fn (string $path): array => [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
            ])->values(),
            'vouchers' => $surprise->vouchers ?? [],
            'startsAt' => $surprise->starts_at->timezone(self::DISPLAY_TIMEZONE)->format('Y-m-d\TH:i'),
            'endsAt' => $surprise->ends_at->timezone(self::DISPLAY_TIMEZONE)->toIso8601String(),
            'isEnabled' => $surprise->is_enabled,
            'status' => $this->status($surprise),
        ];
    }

    /** @return array<string, string> */
    public function defaultMessages(): array
    {
        return [
            'openingMessage' => 'Hari ini ada sesuatu yang spesial untuk kamu.',
            'appreciationMessage' => 'Terima kasih untuk setiap perhatian kecil, setiap tawa, dan setiap waktu yang kita jalani bersama.',
            'loveLetter' => 'Kehadiranmu adalah hadiah terbaik dalam hidupku. Semoga semua hal baik yang kamu berikan kembali kepadamu berkali-kali lipat. Aku ingin terus menemani setiap langkah, mimpi, dan perjalananmu.',
            'closingMessage' => 'Masih banyak cerita, perjalanan, dan mimpi yang menunggu kita.',
        ];
    }

    /** @return array<string, mixed> */
    private function displayPayload(BirthdaySurprise $surprise): array
    {
        return [
            'id' => $surprise->id,
            'startsAt' => $surprise->starts_at->toIso8601String(),
            'endsAt' => $surprise->ends_at->toIso8601String(),
            'recipientName' => $surprise->recipient->nicknameOrName(),
            'recipientAvatarUrl' => $surprise->recipient->avatar_url,
            'senderName' => $surprise->creator->nicknameOrName(),
            'senderAvatarUrl' => $surprise->creator->avatar_url,
            'openingMessage' => $surprise->opening_message,
            'appreciationMessage' => $surprise->appreciation_message,
            'loveLetter' => $surprise->love_letter,
            'closingMessage' => $surprise->closing_message,
            'photos' => collect($surprise->photos ?? [])
                ->map(fn (string $path): string => Storage::disk('public')->url($path))
                ->values(),
            'vouchers' => $surprise->vouchers ?? [],
        ];
    }

    private function status(BirthdaySurprise $surprise): string
    {
        if (! $surprise->is_enabled) {
            return 'disabled';
        }

        if ($surprise->starts_at->isFuture()) {
            return 'scheduled';
        }

        return $surprise->ends_at->isFuture() ? 'active' : 'ended';
    }
}
