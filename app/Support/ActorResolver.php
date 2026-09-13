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
     * The public-portal check runs first: those routes are verified by a
     * CPF-gated session flag, not the auth guard, so a staff member who is
     * also logged in (same browser, e.g. previewing their own client link)
     * would otherwise still have auth()->check() return true and get
     * misattributed as the actor instead of the client.
     *
     * @return array{type: string, name: ?string}
     */
    public static function current(): array
    {
        $contract = request()?->attributes->get('publicContract');

        if ($contract) {
            return ['type' => 'client', 'name' => $contract->client->name ?? 'Cliente'];
        }

        if (auth()->check()) {
            return ['type' => 'admin', 'name' => auth()->user()->name];
        }

        return ['type' => 'system', 'name' => null];
    }
}
