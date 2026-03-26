<?php

use App\Http\Controllers\Api\GovAssaiWebhookController;
use App\Http\Controllers\Api\SaudeCitizenApiController;
use Illuminate\Support\Facades\Route;
use App\Models\HealthUnit;

Route::middleware('api.key')->group(function () {
    Route::get('/saude/cidadaos/cpf/{cpf}', [SaudeCitizenApiController::class, 'showByCpf']);
});

Route::middleware(['api.key', 'webhook.govassai'])->group(function () {
    Route::post('/integracoes/gov-assai/webhooks/cidadaos.updated', [GovAssaiWebhookController::class, 'citizenUpdated']);
});

Route::get('/calls/{unit}', function ($unit) {

    $unit = \App\Models\HealthUnit::where('id', $unit)
        ->firstOrFail();

    $calls = \App\Models\Call::with('attendance.citizen')
        ->whereHas('attendance', function ($q) use ($unit) {
            $q->where('health_unit_id', $unit->id);
        })
        ->where('status', 'CHAMADO')
        ->latest('called_at')
        ->take(7)
        ->get();

    return response()->json([
        'current' => $calls->first(),
        'previous' => $calls->slice(1)->values()
    ]);
});