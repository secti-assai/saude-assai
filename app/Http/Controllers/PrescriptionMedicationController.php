<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Medication;
use Illuminate\Support\Facades\Validator;

class PrescriptionMedicationController extends Controller
{
    /**
     * Cadastra um novo medicamento via AJAX.
     */
    public function store(Request $request)
    {
        $data = $request->only(['name', 'presentation', 'concentration']);
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'presentation' => 'nullable|string|max:255',
            'concentration' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => 'Dados inválidos', 'messages' => $validator->errors()], 422);
        }
        // Gera um código único para o medicamento (formato REM-XXX) quando não fornecido
        $nextId = (Medication::max('id') ?? 0) + 1;
        $data['code'] = sprintf('REM-%03d', $nextId);

        $med = Medication::create($data);
        return response()->json(['id' => $med->id, 'name' => $med->name]);
    }
}
