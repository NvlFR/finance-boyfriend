<?php

namespace App\Services;

use App\Models\PushSubscription as StoredPushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class PushNotificationService
{
    /** @param array<string, mixed> $payload */
    public function sendTo(User $user, array $payload): void
    {
        $publicKey = config('services.web_push.public_key');
        $privateKey = config('services.web_push.private_key');
        $subject = config('services.web_push.subject');

        if (! $publicKey || ! $privateKey || ! $subject) {
            return;
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => $subject,
                    'publicKey' => $publicKey,
                    'privateKey' => $privateKey,
                ],
            ], timeout: 5, clientOptions: ['allow_redirects' => false]);

            $subscriptions = StoredPushSubscription::where('user_id', $user->id)->get();

            foreach ($subscriptions as $storedSubscription) {
                if (! PushEndpointValidator::allows($storedSubscription->endpoint)
                    || ! PushEndpointValidator::validKey((string) $storedSubscription->public_key, 65)
                    || ! PushEndpointValidator::validKey((string) $storedSubscription->auth_token, 16)) {
                    continue;
                }
                $webPush->queueNotification(new Subscription(
                    $storedSubscription->endpoint,
                    $storedSubscription->public_key,
                    $storedSubscription->auth_token,
                    $storedSubscription->content_encoding,
                ), json_encode($payload, JSON_THROW_ON_ERROR));
            }

            foreach ($webPush->flush() as $report) {
                if ($report->isSubscriptionExpired()) {
                    StoredPushSubscription::where('endpoint', $report->getEndpoint())->delete();
                }
            }
        } catch (Throwable $exception) {
            Log::warning('Push notification gagal; aktivitas pengguna tetap tersimpan.', ['exception_type' => $exception::class]);
        }
    }
}
