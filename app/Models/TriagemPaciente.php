<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TriagemPaciente extends Model
{
    use SoftDeletes;

    protected $table = 'triagem_pacientes';

    protected $fillable = [
        'cpf',
        'cns',
        'full_name',
        'social_name',
        'birth_date',
        'sexo',
        'raca_cor',
        'etnia',
        'nome_mae',
        'nome_pai',
        'municipio_nascimento',
        'phone',
        'status',
        'priority_color',
        'arrived_at',
        'triagem_started_at',
        'triagem_finished_at',
        'registered_by_user_id',
        'nurse_user_id',
        'health_unit_id',
        'citizen_id',
    ];

    protected $casts = [
        'birth_date'           => 'date',
        'arrived_at'           => 'datetime',
        'triagem_started_at'   => 'datetime',
        'triagem_finished_at'  => 'datetime',
    ];

    // ─── Status constants ──────────────────────────────────────
    public const STATUS_AGUARDANDO  = 'AGUARDANDO';
    public const STATUS_EM_TRIAGEM  = 'EM_TRIAGEM';
    public const STATUS_FINALIZADO  = 'FINALIZADO';

    // ─── Risk colour (Manchester simplified) ────────────────────
    public const COLOR_VERMELHO  = 'VERMELHO';
    public const COLOR_LARANJA   = 'LARANJA';
    public const COLOR_AMARELO   = 'AMARELO';
    public const COLOR_VERDE     = 'VERDE';
    public const COLOR_AZUL      = 'AZUL';

    // ─── Relationships ───────────────────────────────────────────
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nurse_user_id');
    }

    public function healthUnit(): BelongsTo
    {
        return $this->belongsTo(HealthUnit::class);
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────
    public static function racaCorOptions(): array
    {
        return [
            'branca'    => 'Branca',
            'preta'     => 'Preta',
            'parda'     => 'Parda',
            'amarela'   => 'Amarela',
            'indigena'  => 'Indígena',
            'ignorado'  => 'Ignorado/Não declarado',
        ];
    }

    public static function sexoOptions(): array
    {
        return ['M' => 'Masculino', 'F' => 'Feminino', 'I' => 'Outro/Não informado'];
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_AGUARDANDO => 'Aguardando',
            self::STATUS_EM_TRIAGEM => 'Em Triagem',
            self::STATUS_FINALIZADO => 'Finalizado',
            default                 => ucfirst(strtolower($this->status)),
        };
    }

    public function priorityColorLabel(): string
    {
        return match ($this->priority_color) {
            self::COLOR_VERMELHO => '🔴 Emergência',
            self::COLOR_LARANJA  => '🟠 Muito Urgente',
            self::COLOR_AMARELO  => '🟡 Urgente',
            self::COLOR_VERDE    => '🟢 Pouco Urgente',
            default              => '🔵 Não Urgente',
        };
    }

    /** Formata CPF para exibição: 000.000.000-00 */
    public function getCpfFormattedAttribute(): string
    {
        $cpf = $this->cpf ?? '';
        if (strlen($cpf) === 11) {
            return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
        }
        return $cpf;
    }

    /** Idade calculada a partir de birth_date */
    public function getIdadeAttribute(): ?int
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }
}
