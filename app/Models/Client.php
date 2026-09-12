<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\SearchesByEncryptedDocument;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use Auditable, HasFactory, SearchesByEncryptedDocument, SoftDeletes;

    protected $fillable = [
        'name',
        'document',
        'rg',
        'email',
        'phone',
        'phone_alt',
        'address_street',
        'address_number',
        'address_complement',
        'address_district',
        'address_city',
        'address_state',
        'address_zipcode',
        'birth_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'document' => 'encrypted',
        ];
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DocumentFile::class);
    }
}
