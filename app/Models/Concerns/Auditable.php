<?php

namespace App\Models\Concerns;

use App\Mail\ClientActivityMail;
use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\User;
use App\Support\ActorResolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Records a row in audit_logs whenever the model is created, updated,
 * inactivated (soft deleted) or restored — capturing who did it (staff
 * member or client, via ActorResolver) and, for updates, which fields
 * changed.
 */
trait Auditable
{
    /**
     * When set before a save(), overrides the audit action recorded for
     * that update (e.g. "reverted" instead of the generic "updated").
     */
    public ?string $auditActionOverride = null;

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
        $changes = $action === 'updated' ? $this->auditableChanges() : null;

        if ($action === 'updated' && $changes === []) {
            return;
        }

        if ($action === 'updated' && $this->auditActionOverride) {
            $action = $this->auditActionOverride;
        }
        $this->auditActionOverride = null;

        $log = AuditLog::create([
            'contract_id' => $this->auditContractId(),
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'auditable_label' => $this->auditLabel(),
            'action' => $action,
            'actor_type' => $actor['type'],
            'actor_name' => $actor['name'],
            'changes' => $changes,
        ]);

        if ($actor['type'] === 'client' && $log->contract_id) {
            $this->notifyStaffOfClientActivity($log);
        }
    }

    protected function notifyStaffOfClientActivity(AuditLog $log): void
    {
        $recipients = config('cerimonial.company_email')
            ? [config('cerimonial.company_email')]
            : User::pluck('email')->all();

        if ($recipients === []) {
            return;
        }

        try {
            $log->loadMissing('contract.client');
            Mail::to($recipients)->queue(new ClientActivityMail($log));
        } catch (\Throwable $e) {
            Log::warning('Falha ao enfileirar notificação de atividade do cliente: '.$e->getMessage());
        }
    }

    /**
     * Old/new pairs for each changed field, e.g. ['name' => ['old' => 'A', 'new' => 'B']].
     * The "old" side is what makes a later revert possible.
     */
    protected function auditableChanges(): array
    {
        return collect($this->getChanges())
            ->except(['updated_at', 'updated_by_type', 'updated_by_name'])
            ->mapWithKeys(fn ($new, $field) => [
                $field => ['old' => $this->getOriginal($field), 'new' => $new],
            ])
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
