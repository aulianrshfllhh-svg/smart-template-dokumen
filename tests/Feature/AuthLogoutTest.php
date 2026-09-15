<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterOpd;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthLogoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $opd = MasterOpd::create(['nama_opd' => 'Kecamatan Depok', 'kode_opd' => 'OPD_DEPOK']);
        $this->user = User::create([
            'username_nip' => '199001012020011001',
            'nama_lengkap' => 'Operator Depok',
            'email' => 'operator@example.com',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'opd_id' => $opd->id,
        ]);
    }

    /**
     * Test POST logout works and redirects to login.
     */
    public function test_post_logout_redirects_to_login(): void
    {
        $response = $this->actingAs($this->user)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * Test GET /logout (direct URL navigation) works and redirects to login without 419 or 405 error.
     */
    public function test_get_logout_redirects_to_login(): void
    {
        $response = $this->actingAs($this->user)->get('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * Test unauthenticated GET /logout gracefully redirects to login.
     */
    public function test_unauthenticated_get_logout_redirects_to_login(): void
    {
        $response = $this->get('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * Test POST /login with valid credentials redirects to dashboard.
     */
    public function test_post_login_with_valid_credentials_redirects_to_dashboard(): void
    {
        $response = $this->post('/login', [
            'username_nip' => '199001012020011001',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }
}
