<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    public function test_session_cookie_is_marked_secure_when_configured_for_https(): void
    {
        Config::set('session.secure', true);

        $response = $this->get(secure_url('login'));

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));

        $this->assertNotNull($cookie, 'Cookie de sessão não encontrado na resposta.');
        $this->assertTrue($cookie->isSecure());
    }

    public function test_session_cookie_is_http_only_by_default(): void
    {
        $response = $this->get(route('login'));

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));

        $this->assertNotNull($cookie, 'Cookie de sessão não encontrado na resposta.');
        $this->assertTrue($cookie->isHttpOnly());
    }
}
