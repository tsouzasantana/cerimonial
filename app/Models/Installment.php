<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Installment extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    public const STATUS_PENDENTE = 'pendente';

    public const STATUS_PAGO = 'pago';

    public const STATUS_ATRASADO = 'atrasado';

    public const STATUS_CANCELADO = 'cancelado';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDENTE => 'Pendente',
            self::STATUS_PAGO => 'Pago',
            self::STATUS_ATRASADO => 'Atrasado',
            self::STATUS_CANCELADO => 'Cancelado',
        ];
    }

    public static function paymentMethodOptions(): array
    {
        return [
            'dinheiro' => 'Dinheiro',
            'pix' => 'PIX',
            'cartao_credito' => 'Cartão de crédito',
            'cartao_debito' => 'Cartão de débito',
            'transferencia' => 'Transferência bancária',
            'boleto' => 'Boleto',
        ];
    }

    protected $fillable = [
        'contract_id',
        'number',
        'amount',
        'due_date',
        'paid_at',
        'payment_method',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'date',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_PENDENTE
            && $this->due_date !== null
            && $this->due_date->isPast();
    }

    protected function auditLabel(): string
    {
        return "Parcela nº {$this->number}";
    }
}
