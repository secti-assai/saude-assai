<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditService
{
    public function log(Request $request, string $module, string $action, ?string $entityType = null, ?int $entityId = null, array $metadata = []): void
    {
        $user = $request->user();

        AuditLog::create([
            'user_id' => $user?->id,
            'profile' => $user?->role,
            'module' => $module,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
