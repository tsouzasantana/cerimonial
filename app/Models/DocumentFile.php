<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentFile extends Model
{
    use HasFactory, SoftDeletes;

    public static function categoryOptions(): array
    {
        return [
            'contrato_assinado' => 'Contrato assinado',
            'documento_pessoal' => 'Documento pessoal',
            'comprovante_pagamento' => 'Comprovante de pagamento',
            'outro' => 'Outro',
        ];
    }

    protected $fillable = [
        'client_id',
        'contract_id',
        'uploaded_by',
        'title',
        'category',
        'original_filename',
        'path',
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
}
