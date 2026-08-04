<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BethaStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_stock_index_requires_authentication()
    {
        $response = $this->get(route('doctor.stock.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_doctor_stock_index_requires_permission()
    {
        $user = User::factory()->create([
            'permissions' => [],
        ]);
        $response = $this->actingAs($user)->get(route('doctor.stock.index'));
        $response->assertStatus(403);
    }

    public function test_doctor_stock_index_returns_betha_medications_when_queried()
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MEDICO ?? 'medico',
            'permissions' => ['doctor.module'],
        ]);

        $response = $this->actingAs($user)->get(route('doctor.stock.index', ['q' => 'Dipirona']));

        $response->assertStatus(200);
        $response->assertViewIs('doctor.stock');
        $response->assertViewHas('medications');
    }

    public function test_betha_stock_service_retrieves_farmacia_central_id()
    {
        $service = new BethaStockService();
        $unitId = $service->getFarmaciaCentralUnitId();

        $this->assertIsInt($unitId);
        $this->assertEquals(5797, $unitId);
    }
}

