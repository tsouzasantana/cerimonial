<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TracksActor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A manual entry in the contract's financial control tab — a wedding
 * expense that isn't already covered by a contract installment or a
 * vendor installment, optionally linked to one of the contract's vendors.
 */
class FinancialEntry extends Model
{
    use Auditable, HasFactory, SoftDeletes, TracksActor;

    protected $fillable = [
        'contract_id',
        'vendor_id',
        'created_by',
        'description',
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

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOverdue(): bool
    {
        return $this->status === Installment::STATUS_PENDENTE
            && $this->due_date !== null
            && $this->due_date->isPast();
    }

    public function statusBadgeVariant(): string
    {
        return match ($this->status) {
            Installment::STATUS_PAGO => 'success',
            Installment::STATUS_PENDENTE => 'warning',
            Installment::STATUS_ATRASADO => 'danger',
            default => 'neutral',
        };
    }

    protected function auditLabel(): string
    {
        return $this->description;
    }
}
