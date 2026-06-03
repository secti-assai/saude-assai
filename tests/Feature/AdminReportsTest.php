<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_run()
    {
        $user = User::query()->where('role', 'admin')->first();
        if (!$user) {
            $user = User::factory()->create(['role' => 'admin']);
        }
        $response = $this->actingAs($user)->get('/admin/relatorios');
        $response->assertStatus(200);
    }
}
