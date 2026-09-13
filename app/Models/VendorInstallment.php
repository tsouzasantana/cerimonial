<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A planned/paid installment of a vendor's own contract value (what the
 * couple owes that vendor) — mirrors Installment (the couple's payments to
 * the cerimonial), but scoped to a vendor instead of the contract directly.
 * Reuses Installment's status/payment-method vocabulary since it's the
 * same domain concept.
 */
class VendorInstallment extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
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

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
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
        return "Parcela nº {$this->number} (fornecedor)";
    }

    protected function auditContractId(): ?int
    {
        return $this->vendor?->contract_id;
    }
}
