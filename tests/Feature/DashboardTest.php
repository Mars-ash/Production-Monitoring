<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard Produksi');
    }

    public function test_ajax_data_endpoint_returns_json(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/dashboard/data?date=2026-03-05');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'stats' => [
                'active_machines',
                'avg_productivity',
                'total_output',
                'total_target',
            ],
            'charts' => [
                'productivityByMachine',
                'qtyData',
                'customerDistribution',
            ],
            'table',
        ]);
    }
}
