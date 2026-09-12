<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

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

    protected $fillable = [
        'contract_id',
        'vendor_service_type_id',
        'created_by',
        'name',
        'document',
        'notes',
        'status',
        'payment_status',
    ];

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
}
