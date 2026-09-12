<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::query()
            ->with('contract.client')
            ->when($request->filled('actor_type'), fn ($query) => $query->where('actor_type', $request->string('actor_type')))
            ->when($request->filled('contract_id'), fn ($query) => $query->where('contract_id', $request->integer('contract_id')))
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $contracts = Contract::with('client')->orderByDesc('id')->limit(200)->get();

        return view('audit-logs.index', [
            'logs' => $logs,
            'contracts' => $contracts,
            'actionOptions' => AuditLog::actionOptions(),
            'actorTypeOptions' => AuditLog::actorTypeOptions(),
        ]);
    }
}
