<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Models\Contract;
use App\Support\ActorResolver;

/**
 * Records a row in audit_logs whenever the model is created, updated,
 * inactivated (soft deleted) or restored — capturing who did it (staff
 * member or client, via ActorResolver) and, for updates, which fields
 * changed.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (self $model) => $model->writeAuditLog('created'));
        static::updated(fn (self $model) => $model->writeAuditLog('updated'));
        static::deleted(fn (self $model) => $model->writeAuditLog(
            method_exists($model, 'trashed') && $model->trashed() ? 'inactivated' : 'deleted'
        ));
        static::restored(fn (self $model) => $model->writeAuditLog('restored'));
    }

    protected function writeAuditLog(string $action): void
    {
        $actor = ActorResolver::current();

        AuditLog::create([
            'contract_id' => $this->auditContractId(),
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'auditable_label' => $this->auditLabel(),
            'action' => $action,
            'actor_type' => $actor['type'],
            'actor_name' => $actor['name'],
            'changes' => $action === 'updated' ? $this->auditableChanges() : null,
        ]);
    }

    protected function auditableChanges(): array
    {
        return collect($this->getChanges())
            ->except(['updated_at', 'updated_by_type', 'updated_by_name'])
            ->all();
    }

    protected function auditContractId(): ?int
    {
        if (array_key_exists('contract_id', $this->attributes)) {
            return $this->attributes['contract_id'];
        }

        return $this instanceof Contract ? $this->getKey() : null;
    }

    protected function auditLabel(): string
    {
        return (string) ($this->name ?? $this->title ?? "#{$this->getKey()}");
    }
}
