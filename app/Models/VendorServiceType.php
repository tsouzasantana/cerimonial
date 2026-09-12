<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorServiceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    /**
     * Resolve the service type id to use for a vendor, creating a new
     * type on the fly when the user typed a custom name instead of
     * picking one from the list (the "Outro" free-text option).
     */
    public static function resolveId(?int $id, ?string $customName): int
    {
        if (filled($customName)) {
            return self::firstOrCreate(['name' => trim($customName)])->id;
        }

        return $id;
    }
}
