<?php

return [
    // Chỉ bật khi chủ động dùng reverse proxy/tunnel trước app local.
    'trusted_proxies' => env('TRUSTED_PROXIES', ''),
];
