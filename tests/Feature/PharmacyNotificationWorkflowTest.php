<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\User;
use App\Services\PharmacyDispensationService;
use App\Services\PharmacyNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class PharmacyNotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_level_one_first_dispense_triggers_guidance_and_feedback_notifications(): void
    {
        config()->set('services.gov_assai.base_url', 'https://gov-assai.test');
        config()->set('services.gov_assai.api_key', 'test-key');

        Http::fake([
            'https://gov-assai.test/api/saude/cidadaos/cpf/*' => Http::response([
                'success' => true,
                'message' => 'Consulta realizada com sucesso',
                'data' => [
                    'cidadao' => [
                        'nome' => 'CIDADAO FARMACIA',
                        'data_nascimento' => '1991-07-10',
                    ],
                    'gov_assai' => [
                        'nivel' => 1,
                    ],
                ],
            ], 200),
        ]);

        $notifications = Mockery::mock(PharmacyNotificationService::class);
        $notifications->shouldReceive('sendRegularizationGuidance')
            ->once()
            ->withArgs(function (Citizen $citizen, int $level, string $eventKey): bool {
                return $level === 1
                    && $citizen->cpf_hash === hash('sha256', '90012640944')
                    && str_starts_with($eventKey, 'dispensed-notified-');
            });

        $notifications->shouldReceive('sendDispenseFeedback')->once();

        $this->app->instance(PharmacyNotificationService::class, $notifications);

        $attendant = User::factory()->create([
            'role' => User::ROLE_FARMACIA,
            'email_verified_at' => now(),
        ]);

        $result = app(PharmacyDispensationService::class)->processDispensation([
            'cpf' => '90012640944',
            'full_name' => 'Cidadao Farmacia',
            'phone' => '(43) 92222-2222',
            'dispense_category' => 'MEDICACAO',
        ], (int) $attendant->id, new Request());

        $this->assertTrue($result['success']);
        $this->assertSame('DISPENSED_NOTIFIED', $result['action']);
    }

    public function test_blocked_flow_triggers_regularization_guidance_notification(): void
    {
        config()->set('services.gov_assai.base_url', 'https://gov-assai.test');
        config()->set('services.gov_assai.api_key', 'test-key');

        Http::fake([
            'https://gov-assai.test/api/saude/cidadaos/cpf/*' => Http::response([
                'success' => true,
                'message' => 'Consulta realizada com sucesso',
                'data' => [
                    'cidadao' => [
                        'nome' => 'CIDADAO BLOQUEADO',
                        'data_nascimento' => '1990-09-09',
                    ],
                    'gov_assai' => [
                        'nivel' => 1,
                    ],
                ],
            ], 200),
        ]);

        $notifications = Mockery::mock(PharmacyNotificationService::class);
        $notifications->shouldReceive('sendRegularizationGuidance')
            ->once()
            ->withArgs(function (Citizen $citizen, int $level, string $eventKey): bool {
                return $level === 1
                    && $citizen->cpf_hash === hash('sha256', '90012640945')
                    && str_starts_with($eventKey, 'blocked-');
            });
        $notifications->shouldReceive('sendDispenseFeedback')->never();

        $this->app->instance(PharmacyNotificationService::class, $notifications);

        $attendant = User::factory()->create([
            'role' => User::ROLE_FARMACIA,
            'email_verified_at' => now(),
        ]);

        Citizen::create([
            'cpf' => '90012640945',
            'cpf_hash' => hash('sha256', '90012640945'),
            'full_name' => 'CIDADAO BLOQUEADO',
            'birth_date' => '1990-09-09',
            'is_resident_assai' => true,
            'gov_assai_level' => 1,
            'pharmacy_lock_flag' => true,
            'phone' => '(43) 91111-1111',
        ]);

        $result = app(PharmacyDispensationService::class)->processDispensation([
            'cpf' => '90012640945',
            'full_name' => 'Cidadao Bloqueado',
            'phone' => '(43) 91111-1111',
            'dispense_category' => 'SUPLEMENTO',
        ], (int) $attendant->id, new Request());

        $this->assertFalse($result['success']);
        $this->assertSame('BLOCKED', $result['action']);
    }
}
