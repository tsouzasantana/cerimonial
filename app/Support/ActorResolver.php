<?php

namespace App\Support;

class ActorResolver
{
    /**
     * Identify who is performing the current write operation, so it can
     * be recorded on audit logs and "last updated by" columns — either
     * an authenticated staff member, a client acting through the public
     * portal (identified by the contract bound to the current request),
     * or the system (console commands, seeders).
     *
     * @return array{type: string, name: ?string}
     */
    public static function current(): array
    {
        if (auth()->check()) {
            return ['type' => 'admin', 'name' => auth()->user()->name];
        }

        $contract = request()?->attributes->get('publicContract');

        if ($contract) {
            return ['type' => 'client', 'name' => $contract->client->name ?? 'Cliente'];
        }

        return ['type' => 'system', 'name' => null];
    }
}
