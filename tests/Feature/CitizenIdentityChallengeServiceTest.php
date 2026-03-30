<?php

namespace Tests\Feature;

use App\Services\CitizenIdentityChallengeService;
use Tests\TestCase;

class CitizenIdentityChallengeServiceTest extends TestCase
{
    public function test_it_accepts_full_birth_date_when_challenge_is_year(): void
    {
        $service = app(CitizenIdentityChallengeService::class);

        session()->put('identity_challenge.women_clinic_schedule', [
            'token' => 'test-token',
            'context' => 'women_clinic_schedule',
            'expected' => hash('sha256', '2006'),
            'expected_kind' => 'year',
            'created_at' => now()->timestamp,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        $this->assertTrue($service->verify('women_clinic_schedule', 'test-token', '12/03/2006'));
    }

    public function test_it_accepts_iso_birth_date_when_challenge_is_month(): void
    {
        $service = app(CitizenIdentityChallengeService::class);

        session()->put('identity_challenge.central_pharmacy_reception', [
            'token' => 'test-token',
            'context' => 'central_pharmacy_reception',
            'expected' => hash('sha256', '03'),
            'expected_kind' => 'month',
            'created_at' => now()->timestamp,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        $this->assertTrue($service->verify('central_pharmacy_reception', 'test-token', '2006-03-12'));
    }
}
