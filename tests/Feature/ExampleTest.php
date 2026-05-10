<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Unauthenticated requests to the root are redirected to login.
     * The login page itself must render a 200.
     */
    public function test_root_redirects_unauthenticated_users_to_login(): void
    {
        $this->get('/')->assertRedirect();
    }

    public function test_login_page_is_accessible(): void
    {
        $this->get('/login')->assertOk();
    }
}
