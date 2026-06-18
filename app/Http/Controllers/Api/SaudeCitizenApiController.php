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

            // Salva ou atualiza localmente para garantir que o cidadão exista no DB e possa ser sincronizado com bypasses/importações.
            if (!empty($result['data'])) {
                $mapped = $this->govAssai->mapCitizenDataForLocalCreate($result['data']);
                $cleanCpf = preg_replace('/\D+/', '', $cpf);
                
                if (!empty($cleanCpf)) {
                    $cpfHash = hash('sha256', $cleanCpf);
                    
                    \App\Models\Citizen::updateOrCreate(
                        ['cpf_hash' => $cpfHash],
                        [
                            'cpf' => $cleanCpf,
                            'full_name' => $mapped['name'] ?? 'NOME NAO INFORMADO',
                            'social_name' => $mapped['social_name'],
                            'birth_date' => $mapped['birth_date'] ?? '1900-01-01',
                            'sexo' => $mapped['sexo'],
                            'email' => $mapped['email'],
                            'phone' => $mapped['phone'],
                            'cns' => $mapped['cns'],
                            'is_resident_assai' => $mapped['is_resident_assai'] ?? false,
                        ]
                    );
                }
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
