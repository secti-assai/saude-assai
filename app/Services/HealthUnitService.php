<?php

namespace App\Services;

use App\Models\HealthUnit;
use Illuminate\Support\Str;

class HealthUnitService
{
    public function getAll()
    {
        return HealthUnit::latest()->get();
    }

    public function create(array $data)
    {
        // Handle photo upload
        if (isset($data['photo']) && $data['photo']->isValid()) {
            $data['photo_path'] = $data['photo']->store('health_units', 'public');
            unset($data['photo']);
        }

        $data['id'] = Str::uuid();

        return HealthUnit::create($data);
    }

    public function update(HealthUnit $healthUnit, array $data)
    {
        if (isset($data['photo']) && $data['photo']->isValid()) {
            $data['photo_path'] = $data['photo']->store('health_units', 'public');
            unset($data['photo']);
        }

        $healthUnit->update($data);
        return $healthUnit;
    }

    public function delete(HealthUnit $healthUnit)
    {
        return $healthUnit->delete(); // Soft delete as per guidelines
    }
}
