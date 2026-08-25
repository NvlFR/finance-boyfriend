<?php

test('service worker never caches authenticated dynamic responses', function () {
    $serviceWorker = file_get_contents(public_path('sw.js'));

    expect($serviceWorker)
        ->toContain("const CACHE_NAME = 'couple-finance-static-v2'")
        ->toContain('event.respondWith(fetch(request))')
        ->not->toContain('cache.put(request, responseClone)')
        ->not->toContain("caches.match('/')");
});
