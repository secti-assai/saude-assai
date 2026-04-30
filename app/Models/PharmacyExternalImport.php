<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PharmacyExternalImport extends Model
{
    use HasUuids;
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uploaded_by_user_id',
        'betha_filename',
        'daily_filename',
        'betha_sha256',
        'daily_sha256',
        'imported_at',
        'stats',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
        'stats' => 'array',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(PharmacyExternalImportRow::class, 'import_id');
    }
}
