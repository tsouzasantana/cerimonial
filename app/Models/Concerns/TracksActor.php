<?php

namespace App\Models\Concerns;

use App\Support\ActorResolver;

/**
 * Stamps updated_by_type / updated_by_name with whoever is currently
 * saving the model (staff member or client) — a quick-glance "last
 * touched by" column, separate from the full history in audit_logs.
 */
trait TracksActor
{
    public static function bootTracksActor(): void
    {
        static::saving(function (self $model) {
            $actor = ActorResolver::current();
            $model->updated_by_type = $actor['type'];
            $model->updated_by_name = $actor['name'];
        });
    }

    public function updatedByLabel(): ?string
    {
        if (! $this->updated_by_name) {
            return null;
        }

        return $this->updated_by_type === 'client'
            ? "{$this->updated_by_name} (cliente)"
            : $this->updated_by_name;
    }
}
