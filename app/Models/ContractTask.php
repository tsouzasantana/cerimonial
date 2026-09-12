<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractTask extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_A_INICIAR = 'a_iniciar';

    public const STATUS_EM_ANDAMENTO = 'em_andamento';

    public const STATUS_CONCLUIDA = 'concluida';

    public const STATUS_CANCELADA = 'cancelada';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_A_INICIAR => 'A iniciar',
            self::STATUS_EM_ANDAMENTO => 'Em andamento',
            self::STATUS_CONCLUIDA => 'Concluída',
            self::STATUS_CANCELADA => 'Cancelada',
        ];
    }

    public static function openStatuses(): array
    {
        return [self::STATUS_A_INICIAR, self::STATUS_EM_ANDAMENTO];
    }

    protected $fillable = [
        'contract_id',
        'name',
        'due_date',
        'status',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, self::openStatuses(), true)
            && $this->due_date !== null
            && $this->due_date->isPast()
            && ! $this->due_date->isToday();
    }
}
