<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_login_page_returns_successful_response(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_admin_can_login_to_dashboard(): void
    {
        $this->post('/login', [
            'role' => 'admin',
            'username' => 'admin1',
            'password' => 'admin123',
        ])->assertRedirect('/');
    }

    public function test_admin_can_open_chat_page(): void
    {
        $this->withSession(['auth_role' => 'admin'])
            ->get('/pesan')
            ->assertStatus(200)
            ->assertSee('Chat Civitas');
    }

    public function test_superadmin_can_login_to_selection_page(): void
    {
        $this->post('/login', [
            'role' => 'superadmin',
            'username' => 'superadmin1',
            'password' => 'superadmin123',
        ])->assertRedirect('/superadmin/seleksi-admin');
    }
}
