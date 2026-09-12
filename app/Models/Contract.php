<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_RASCUNHO = 'rascunho';

    public const STATUS_ATIVO = 'ativo';

    public const STATUS_CONCLUIDO = 'concluido';

    public const STATUS_CANCELADO = 'cancelado';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_RASCUNHO => 'Rascunho',
            self::STATUS_ATIVO => 'Ativo',
            self::STATUS_CONCLUIDO => 'Concluído',
            self::STATUS_CANCELADO => 'Cancelado',
        ];
    }

    protected $fillable = [
        'client_id',
        'created_by',
        'event_date',
        'event_location',
        'status',
        'subtotal',
        'discount',
        'total',
        'signed_at',
        'pdf_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'signed_at' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DocumentFile::class);
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(Occurrence::class);
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('total_price');
        $total = max(0, $subtotal - $this->discount);

        $this->forceFill([
            'subtotal' => $subtotal,
            'total' => $total,
        ])->save();
    }
}
