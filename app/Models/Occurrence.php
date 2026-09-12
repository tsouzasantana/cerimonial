<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Occurrence extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'contract_id',
        'occurrence_type_id',
        'user_id',
        'occurrence_date',
        'deadline',
        'description',
        'attachment_path',
        'attachment_original_name',
    ];

    protected function casts(): array
    {
        return [
            'occurrence_date' => 'date',
            'deadline' => 'date',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(OccurrenceType::class, 'occurrence_type_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function auditLabel(): string
    {
        return $this->type?->name ?? "#{$this->getKey()}";
    }
}
