<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Contract;
use Illuminate\Database\Eloquent\Model;

/**
 * Contract-scoped resources (installments, occurrences, tasks, vendors) are
 * reached via nested routes like contracts/{contract}/tasks/{task}, so a
 * request can name a real child id that belongs to a different contract.
 * This centralizes the ownership check every one of those controllers needs.
 */
trait EnsuresContractOwnership
{
    protected function ensureBelongsToContract(Model $child, Contract $contract): void
    {
        abort_unless($child->contract_id === $contract->id, 404);
    }
}
