<?php

namespace App\Services;

class PushEndpointValidator
{
    public static function allows(string $endpoint): bool
    {
        $parts = parse_url($endpoint);
        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
            || (isset($parts['port']) && $parts['port'] !== 443)) {
            return false;
        }
        $host = strtolower($parts['host'] ?? '');

        return in_array($host, ['fcm.googleapis.com', 'updates.push.services.mozilla.com', 'web.push.apple.com'], true)
            || str_ends_with($host, '.push.apple.com')
            || str_ends_with($host, '.notify.windows.com');
    }

    public static function validKey(string $value, int $length): bool
    {
        if (! preg_match('/^[A-Za-z0-9_-]+={0,2}$/D', $value)) {
            return false;
        }
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded !== false && strlen($decoded) === $length
            && ($length !== 65 || $decoded[0] === "\x04");
    }
}
