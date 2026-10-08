<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProxyUrlTest extends TestCase
{
    public function test_https_proxy_generates_https_assets_and_login_action(): void
    {
        config()->set('proxy.trusted_proxies', '*');

        $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
        ])->get('http://example.ngrok-free.app/login')
            ->assertOk()
            ->assertSee('https://example.ngrok-free.app/css/app.css', false)
            ->assertSee('action="https://example.ngrok-free.app/login"', false);
    }

    public function test_direct_local_request_keeps_http_urls(): void
    {
        config()->set('proxy.trusted_proxies', '*');

        $this->withHeaders(['Host' => '127.0.0.1:15400'])->get('/login')
            ->assertOk()
            ->assertSee('http://127.0.0.1:15400/css/app.css', false);
    }

    public function test_forwarded_scheme_is_ignored_when_proxy_support_is_disabled(): void
    {
        config()->set('proxy.trusted_proxies', '');

        $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
        ])->get('http://example.ngrok-free.app/login')
            ->assertOk()
            ->assertSee('http://example.ngrok-free.app/css/app.css', false);
    }
}
