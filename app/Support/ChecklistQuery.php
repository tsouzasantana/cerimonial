<?php

namespace App\Support;

use App\Models\Contract;
use App\Models\ContractTask;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class ChecklistQuery
{
    private const ALLOWED_SORTS = ['name', 'due_date', 'status'];

    /**
     * Build the checklist task listing for a contract, applying the
     * status filter and column sort requested via query string
     * (shared between the authenticated contract view and the
     * public client portal).
     */
    public static function forContract(Contract $contract, Request $request): Collection
    {
        $sort = $request->query('checklist_sort', 'due_date');
        $direction = $request->query('checklist_direction') === 'desc' ? 'desc' : 'asc';
        $status = $request->query('checklist_status');

        if (! in_array($sort, self::ALLOWED_SORTS, true)) {
            $sort = 'due_date';
        }

        return ContractTask::query()
            ->where('contract_id', $contract->id)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy($sort, $direction)
            ->get();
    }
}
