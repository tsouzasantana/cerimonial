<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'contract_id',
        'uploaded_by',
        'document_type_id',
        'title',
        'original_filename',
        'path',
        'url',
        'mime_type',
        'size',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function isLink(): bool
    {
        return ! empty($this->url);
    }
}
