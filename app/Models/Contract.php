<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TracksActor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Contract extends Model
{
    use Auditable, HasFactory, SoftDeletes, TracksActor;

    public const STATUS_RASCUNHO = 'rascunho';

    public const STATUS_ATIVO = 'ativo';

    public const STATUS_CONCLUIDO = 'concluido';

    public const STATUS_CANCELADO = 'cancelado';

    public const DISCOUNT_TYPE_FIXED = 'fixed';

    public const DISCOUNT_TYPE_PERCENTAGE = 'percentage';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_RASCUNHO => 'Rascunho',
            self::STATUS_ATIVO => 'Ativo',
            self::STATUS_CONCLUIDO => 'Concluído',
            self::STATUS_CANCELADO => 'Cancelado',
        ];
    }

    public static function discountTypeOptions(): array
    {
        return [
            self::DISCOUNT_TYPE_FIXED => 'Valor fixo (R$)',
            self::DISCOUNT_TYPE_PERCENTAGE => 'Percentual (%)',
        ];
    }

    public function statusBadgeVariant(): string
    {
        return match ($this->status) {
            self::STATUS_ATIVO => 'info',
            self::STATUS_CONCLUIDO => 'success',
            self::STATUS_CANCELADO => 'danger',
            default => 'neutral',
        };
    }

    protected static function booted(): void
    {
        static::creating(function (Contract $contract) {
            $contract->public_token ??= Str::random(48);
        });
    }

    protected $fillable = [
        'client_id',
        'created_by',
        'event_date',
        'event_location',
        'status',
        'subtotal',
        'discount',
        'discount_type',
        'discount_value',
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
            'discount_value' => 'decimal:2',
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

    public function tasks(): HasMany
    {
        return $this->hasMany(ContractTask::class);
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    public function financialEntries(): HasMany
    {
        return $this->hasMany(FinancialEntry::class);
    }

    /**
     * Re-derives discount from discount_type/discount_value against the
     * current subtotal (not just from whatever "discount" already holds),
     * so a percentage discount stays correct as items are added/removed.
     */
    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('total_price');
        $discount = $this->discount_type === self::DISCOUNT_TYPE_PERCENTAGE
            ? round($subtotal * (float) $this->discount_value / 100, 2)
            : (float) $this->discount_value;
        $total = max(0, $subtotal - $discount);

        $this->forceFill([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
        ])->save();
    }

    public function applyChecklistTemplate(): void
    {
        $sortOrder = 0;

        ChecklistTemplate::orderByDesc('days_offset')->get()->each(function (ChecklistTemplate $template) use (&$sortOrder) {
            $this->tasks()->create([
                'name' => $template->name,
                'due_date' => $template->dueDateFor($this->event_date),
                'status' => ContractTask::STATUS_A_INICIAR,
                'sort_order' => $sortOrder++,
            ]);
        });
    }

    public function shiftChecklistDates(int $deltaDays): void
    {
        if ($deltaDays === 0) {
            return;
        }

        $this->tasks()->get()->each(function (ContractTask $task) use ($deltaDays) {
            $task->update(['due_date' => $task->due_date->copy()->addDays($deltaDays)]);
        });
    }

    public function regeneratePublicToken(): void
    {
        $this->forceFill(['public_token' => Str::random(48)])->save();
    }

    /**
     * The date the contract was actually signed: the earliest "Contrato
     * assinado" occurrence, so registering it is enough without editing
     * signed_at by hand. Falls back to the manually-set signed_at for
     * contracts signed before an occurrence was recorded.
     */
    public function signedAt(): ?Carbon
    {
        $fromOccurrence = $this->occurrences()
            ->whereHas('type', fn ($query) => $query->where('name', 'like', '%assinado%'))
            ->orderBy('occurrence_date')
            ->value('occurrence_date');

        return $fromOccurrence ?? $this->signed_at;
    }

    public function paidInstallmentsTotal(): float
    {
        return (float) $this->installments()->where('status', Installment::STATUS_PAGO)->sum('amount');
    }

    public function remainingInstallmentsBalance(): float
    {
        return max(0, (float) $this->total - $this->paidInstallmentsTotal());
    }

    /**
     * Part of the contract total that hasn't been broken into an
     * installment yet (paid or not), mirroring Vendor::uninvoicedBalance().
     */
    public function uninvoicedInstallmentsBalance(): float
    {
        $itemizedTotal = (float) $this->installments()->sum('amount');

        return max(0, (float) $this->total - $itemizedTotal);
    }
}
