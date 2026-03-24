<?php

namespace App\Services;

use App\Models\HealthUnit;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class HealthUnitService
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function getAll()
    {
        return HealthUnit::latest()->get();
    }

    public function create(array $data, $request = null)
    {
        if (isset($data['photo']) && $data['photo']->isValid()) {
            $data['photo_path'] = $data['photo']->store('health_units', 'public');
            unset($data['photo']);
        }

        $unit = HealthUnit::create($data);

        if ($request) {
            $this->audit->log($request, 'M2', 'CRIAR_UNIDADE', HealthUnit::class, (string) $unit->id);
        }

        return $unit;
    }

    public function update(HealthUnit $healthUnit, array $data, $request = null)
    {
        if (isset($data['photo']) && $data['photo']->isValid()) {
            if ($healthUnit->photo_path) {
                Storage::disk('public')->delete($healthUnit->photo_path);
            }
            $data['photo_path'] = $data['photo']->store('health_units', 'public');
            unset($data['photo']);
        }

        $healthUnit->update($data);

        if ($request) {
            $this->audit->log($request, 'M2', 'ATUALIZAR_UNIDADE', HealthUnit::class, (string) $healthUnit->id);
        }

        return $healthUnit;
    }

    public function delete(HealthUnit $healthUnit, $request = null)
    {
        $healthUnit->delete(); 
        
        if ($request) {
            $this->audit->log($request, 'M2', 'DELETAR_UNIDADE', HealthUnit::class, (string) $healthUnit->id);
        }
        
        return true;
    }
}
