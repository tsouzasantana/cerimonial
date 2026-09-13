<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\SearchesByEncryptedDocument;
use App\Models\Concerns\TracksActor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use Auditable, HasFactory, SearchesByEncryptedDocument, SoftDeletes, TracksActor;

    public const STATUS_A_PRESTAR = 'a_prestar';

    public const STATUS_PRESTADO = 'prestado';

    public const STATUS_CANCELADO = 'cancelado';

    public const PAYMENT_NAO_PAGO = 'nao_pago';

    public const PAYMENT_PARCIAL = 'pago_parcial';

    public const PAYMENT_INTEGRAL = 'pago_integral';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_A_PRESTAR => 'A prestar',
            self::STATUS_PRESTADO => 'Prestado',
            self::STATUS_CANCELADO => 'Cancelado',
        ];
    }

    public static function paymentStatusOptions(): array
    {
        return [
            self::PAYMENT_NAO_PAGO => 'Não pago',
            self::PAYMENT_PARCIAL => 'Pago parcialmente',
            self::PAYMENT_INTEGRAL => 'Pago integralmente',
        ];
    }

    public function paymentStatusBadgeVariant(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_INTEGRAL => 'success',
            self::PAYMENT_PARCIAL => 'warning',
            default => 'neutral',
        };
    }

    protected $fillable = [
        'contract_id',
        'vendor_service_type_id',
        'created_by',
        'name',
        'document',
        'contract_value',
        'notes',
        'status',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'document' => 'encrypted',
            'contract_value' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        // Recompute automatically when contract_value changes, so it stays
        // in sync without needing a separate save() call (which would
        // re-trigger this same "saving" event).
        static::saving(function (self $vendor) {
            if ($vendor->exists && $vendor->isDirty('contract_value')) {
                $vendor->payment_status = $vendor->computePaymentStatus();
            }
        });
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function vendorServiceType(): BelongsTo
    {
        return $this->belongsTo(VendorServiceType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DocumentFile::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(VendorInstallment::class);
    }

    /**
     * "Pago integralmente" once paid installments cover the contract
     * value, "pago parcialmente" once anything has been paid, otherwise
     * "não pago" — this replaces manually picking the payment status.
     */
    public function computePaymentStatus(): string
    {
        $paidTotal = $this->installments()->where('status', Installment::STATUS_PAGO)->sum('amount');

        return match (true) {
            $this->contract_value !== null && $this->contract_value > 0 && $paidTotal >= $this->contract_value => self::PAYMENT_INTEGRAL,
            $paidTotal > 0 => self::PAYMENT_PARCIAL,
            default => self::PAYMENT_NAO_PAGO,
        };
    }

    public function recalculatePaymentStatus(): void
    {
        $this->forceFill(['payment_status' => $this->computePaymentStatus()])->save();
    }
}
