<?php

namespace App\Services;

use App\Models\PushSubscription as StoredPushSubscription;
use App\Models\User;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

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

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ], timeout: 5);

        $subscriptions = StoredPushSubscription::where('user_id', $user->id)->get();

        foreach ($subscriptions as $storedSubscription) {
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
    }
}
