<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_responses_include_security_headers(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(self), geolocation=(self), microphone=()');

        $this->assertStringContainsString(
            "default-src 'self'",
            (string) $response->headers->get('Content-Security-Policy'),
        );
        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    public function test_production_check_fails_safely_in_local_environment(): void
    {
        $this->artisan('app:production-check')
            ->expectsOutputToContain('[GAGAL] APP_ENV harus production')
            ->expectsOutputToContain('Aplikasi belum siap dijalankan sebagai production.')
            ->assertFailed();
    }
}
