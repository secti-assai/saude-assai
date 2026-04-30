<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GovAssaiService;
use Illuminate\Http\JsonResponse;

class SaudeCitizenApiController extends Controller
{
    public function __construct(private readonly GovAssaiService $govAssai)
    {
    }

    public function showByCpf(string $cpf): JsonResponse
    {
        $result = $this->govAssai->fetchCitizenByCpf($cpf);

        if ($result['success']) {
            $response = [
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data'],
            ];

            if (! empty($result['origem'])) {
                $response['origem'] = $result['origem'];
            }

            return response()->json($response, 200);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'error_code' => $result['error_code'],
        ], $result['status']);
    }
}
