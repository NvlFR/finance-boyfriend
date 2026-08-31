<?php

use App\Models\BirthdaySurprise;
use App\Models\CoupleSpace;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

afterEach(function () {
    Carbon::setTestNow();
});

/** @return array{0: CoupleSpace, 1: User, 2: User} */
function connectedBirthdayCouple(): array
{
    $space = CoupleSpace::factory()->active()->create();
    $creator = $space->userOne;
    $recipient = $space->userTwo;
    $creator->update(['current_couple_space_id' => $space->id]);
    $recipient->update(['current_couple_space_id' => $space->id]);

    return [$space, $creator, $recipient];
}

/** @return array<string, mixed> */
function validBirthdaySurpriseData(array $overrides = []): array
{
    return [
        'opening_message' => 'Ada hadiah kecil untuk kamu.',
        'appreciation_message' => 'Terima kasih selalu ada di sisiku.',
        'love_letter' => 'Aku ingin terus menemani semua perjalananmu.',
        'closing_message' => 'Mari membuat lebih banyak cerita bersama.',
        'starts_at' => '2026-08-27T00:15',
        'is_enabled' => true,
        'kept_photos' => [],
        'photos' => [],
        'vouchers' => ['Dinner pilihan kamu', 'Quality time', 'Jalan-jalan'],
        ...$overrides,
    ];
}

test('only the couple space creator can open birthday surprise settings', function () {
    [, $creator, $recipient] = connectedBirthdayCouple();
    $creator->update(['name' => 'Adit Pratama', 'nickname' => 'Adit']);
    $recipient->update(['name' => 'Nabila Putri', 'nickname' => 'Bila']);

    $this->actingAs($creator)
        ->get(route('birthday-surprise.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('BirthdaySurprise/Edit')
            ->where('partner.name', 'Nabila Putri')
            ->where('partner.nickname', 'Bila')
            ->where('durationDays', 7));

    $this->actingAs($recipient)
        ->get(route('birthday-surprise.edit'))
        ->assertForbidden();
});

test('the explicitly assigned partner manages the surprise even when they joined the space', function () {
    [$space, $spaceCreator, $joinedPartner] = connectedBirthdayCouple();
    $spaceCreator->update(['name' => 'Raina Yuniar', 'nickname' => 'Raina']);
    $joinedPartner->update(['name' => 'Noval Pratama', 'nickname' => 'Noval']);
    $space->update(['birthday_surprise_manager_user_id' => $joinedPartner->id]);

    $this->actingAs($spaceCreator)
        ->get(route('birthday-surprise.edit'))
        ->assertForbidden();

    $this->actingAs($joinedPartner)
        ->get(route('birthday-surprise.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('BirthdaySurprise/Edit')
            ->where('partner.nickname', 'Raina'));

    $this->actingAs($joinedPartner)
        ->get(route('couple-space.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('canManageBirthdaySurprise', true));

    $this->actingAs($joinedPartner)
        ->post(route('birthday-surprise.update'), validBirthdaySurpriseData())
        ->assertRedirect(route('birthday-surprise.edit'));

    $surprise = BirthdaySurprise::query()->firstOrFail();

    expect($surprise->creator_user_id)->toBe($joinedPartner->id)
        ->and($surprise->recipient_user_id)->toBe($spaceCreator->id);
});

test('creator can save messages photos and a seven day schedule in jakarta time', function () {
    Storage::fake('public');
    Carbon::setTestNow(Carbon::parse('2026-08-26 12:00:00', 'UTC'));
    [$space, $creator, $recipient] = connectedBirthdayCouple();

    $response = $this->actingAs($creator)->post(route('birthday-surprise.update'), validBirthdaySurpriseData([
        'photos' => [
            UploadedFile::fake()->image('memory-one.jpg'),
            UploadedFile::fake()->image('memory-two.png'),
        ],
    ]));

    $response->assertRedirect(route('birthday-surprise.edit'));

    $surprise = BirthdaySurprise::query()->firstOrFail();

    expect($surprise->couple_space_id)->toBe($space->id)
        ->and($surprise->creator_user_id)->toBe($creator->id)
        ->and($surprise->recipient_user_id)->toBe($recipient->id)
        ->and($surprise->starts_at->toDateTimeString())->toBe('2026-08-26 17:15:00')
        ->and($surprise->ends_at->diffInDays($surprise->starts_at, true))->toBe(7.0)
        ->and($surprise->photos)->toHaveCount(2);

    foreach ($surprise->photos as $photoPath) {
        Storage::disk('public')->assertExists($photoPath);
    }
});

test('partner cannot create or change the birthday surprise settings', function () {
    [, , $recipient] = connectedBirthdayCouple();

    $this->actingAs($recipient)
        ->post(route('birthday-surprise.update'), validBirthdaySurpriseData())
        ->assertForbidden();

    expect(BirthdaySurprise::query()->exists())->toBeFalse();
});

test('active surprise uses current database names and is visible only to its recipient', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-27 03:00:00', 'UTC'));
    [$space, $creator, $recipient] = connectedBirthdayCouple();
    $creator->update(['name' => 'Dimas Saputra', 'nickname' => 'Dimas']);
    $recipient->update(['name' => 'Nabila Putri', 'nickname' => 'Bila']);

    BirthdaySurprise::query()->create([
        'couple_space_id' => $space->id,
        'creator_user_id' => $creator->id,
        'recipient_user_id' => $recipient->id,
        'opening_message' => 'Pesan pembuka dinamis.',
        'appreciation_message' => 'Pesan kenangan.',
        'love_letter' => 'Surat khusus.',
        'closing_message' => 'Pesan penutup.',
        'photos' => ['birthday-surprises/photo.jpg'],
        'vouchers' => ['Dinner berdua'],
        'starts_at' => now()->subMinute(),
        'ends_at' => now()->addDays(7)->subMinute(),
        'is_enabled' => true,
    ]);

    $this->actingAs($recipient)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('birthdaySurprise.recipientName', 'Bila')
        ->where('birthdaySurprise.senderName', 'Dimas')
        ->where('birthdaySurprise.openingMessage', 'Pesan pembuka dinamis.')
        ->where('birthdaySurprise.vouchers.0', 'Dinner berdua'));

    $this->actingAs($creator)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('birthdaySurprise', null));
});

test('surprise is hidden before its start and after its seven day window', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-26 12:00:00', 'UTC'));
    [$space, $creator, $recipient] = connectedBirthdayCouple();
    $startsAt = now()->addHour();
    $surprise = BirthdaySurprise::query()->create([
        'couple_space_id' => $space->id,
        'creator_user_id' => $creator->id,
        'recipient_user_id' => $recipient->id,
        'opening_message' => 'Pembuka.',
        'appreciation_message' => 'Apresiasi.',
        'love_letter' => 'Surat.',
        'closing_message' => 'Penutup.',
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->copy()->addDays(7),
        'is_enabled' => true,
    ]);

    $this->actingAs($recipient)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('birthdaySurprise', null));

    Carbon::setTestNow($surprise->ends_at);

    $this->actingAs($recipient)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('birthdaySurprise', null));
});

test('updating photos removes only images that the creator discarded', function () {
    Storage::fake('public');
    [$space, $creator] = connectedBirthdayCouple();
    Storage::disk('public')->put('birthday-surprises/keep.jpg', 'keep');
    Storage::disk('public')->put('birthday-surprises/remove.jpg', 'remove');
    $surprise = BirthdaySurprise::factory()->create([
        'couple_space_id' => $space->id,
        'creator_user_id' => $space->user_one_id,
        'recipient_user_id' => $space->user_two_id,
        'photos' => ['birthday-surprises/keep.jpg', 'birthday-surprises/remove.jpg'],
    ]);

    $this->actingAs($creator)->post(route('birthday-surprise.update'), validBirthdaySurpriseData([
        'kept_photos' => ['birthday-surprises/keep.jpg'],
        'photos' => [UploadedFile::fake()->image('new.jpg')],
    ]))->assertRedirect(route('birthday-surprise.edit'));

    $surprise->refresh();

    Storage::disk('public')->assertExists('birthday-surprises/keep.jpg');
    Storage::disk('public')->assertMissing('birthday-surprises/remove.jpg');
    expect($surprise->photos)->toHaveCount(2)
        ->and($surprise->photos)->toContain('birthday-surprises/keep.jpg');
});
