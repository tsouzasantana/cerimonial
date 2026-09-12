<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public static function actionOptions(): array
    {
        return [
            'created' => 'Criado',
            'updated' => 'Atualizado',
            'inactivated' => 'Inativado',
            'deleted' => 'Removido',
            'restored' => 'Restaurado',
        ];
    }

    public static function actorTypeOptions(): array
    {
        return [
            'admin' => 'Equipe',
            'client' => 'Cliente',
            'system' => 'Sistema',
        ];
    }

    protected $fillable = [
        'contract_id',
        'auditable_type',
        'auditable_id',
        'auditable_label',
        'action',
        'actor_type',
        'actor_name',
        'changes',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function subjectLabel(): string
    {
        $type = class_basename($this->auditable_type);

        $labels = [
            'Contract' => 'Contrato',
            'ContractTask' => 'Tarefa do checklist',
            'Vendor' => 'Fornecedor',
            'DocumentFile' => 'Documento',
            'Installment' => 'Parcela',
            'Occurrence' => 'Ocorrência',
            'Client' => 'Cliente',
        ];

        return $labels[$type] ?? $type;
    }
}
