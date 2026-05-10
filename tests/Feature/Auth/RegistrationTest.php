<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * This application uses invitation-only registration.
 * The standard Breeze /register endpoint is intentionally disabled (returns 404).
 * New users must register via a valid invitation link (/register/{token}).
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_open_registration_post_is_disabled(): void
    {
        $this->post('/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'Test@12345678!',
            'password_confirmation' => 'Test@12345678!',
        ])->assertNotFound();

        $this->assertGuest();
    }

    public function test_invitation_register_page_requires_valid_token(): void
    {
        // Real invitation-based registration lives at /invite/{token}.
        // An invalid/expired token must NOT produce a 200 success page.
        $response = $this->get('/invite/this-is-not-a-valid-token');
        $this->assertNotSame(200, $response->status(), 'Invalid invite token must not return 200');
    }
}
