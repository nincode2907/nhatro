<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

class TrustLocalProxy extends TrustProxies
{
    protected $headers = Request::HEADER_X_FORWARDED_PROTO;

    protected function proxies()
    {
        $trustedProxies = trim((string) config('proxy.trusted_proxies'));

        return $trustedProxies === '*' ? '*' : array_filter(array_map('trim', explode(',', $trustedProxies)));
    }
}
