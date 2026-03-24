<?php

namespace App\Services;

use App\Models\PortalContent;
use Illuminate\Support\Str;

class PortalContentService
{
    public function getAll($type = null)
    {
        $query = PortalContent::latest();
        if ($type) {
            $query->where('type', $type);
        }
        return $query->get();
    }

    public function create(array $data)
    {
        $data['id'] = Str::uuid();
        return PortalContent::create($data);
    }

    public function update(PortalContent $portalContent, array $data)
    {
        $portalContent->update($data);
        return $portalContent;
    }

    public function delete(PortalContent $portalContent)
    {
        return $portalContent->delete();
    }
}
