<?php

use App\Http\Controllers\Api\GovAssaiWebhookController;
use App\Http\Controllers\Api\SaudeCitizenApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.key')->group(function () {
    Route::get('/saude/cidadaos/cpf/{cpf}', [SaudeCitizenApiController::class, 'showByCpf']);
});

Route::middleware(['api.key', 'webhook.govassai'])->group(function () {
    Route::post('/integracoes/gov-assai/webhooks/cidadaos.updated', [GovAssaiWebhookController::class, 'citizenUpdated']);
});
