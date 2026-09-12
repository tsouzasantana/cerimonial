<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Contract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\RedirectResponse;
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

    public function revert(AuditLog $auditLog): RedirectResponse
    {
        abort_unless($auditLog->isRevertible(), 422, 'Esta atividade não pode ser revertida.');

        $modelClass = $auditLog->auditable_type;
        abort_unless(class_exists($modelClass), 404);

        $query = $modelClass::query();
        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query = $modelClass::withTrashed();
        }

        $model = $query->find($auditLog->auditable_id);
        abort_if(is_null($model), 404, 'Registro não encontrado (pode ter sido removido definitivamente).');

        foreach ($auditLog->changes as $field => $change) {
            $model->setAttribute($field, $change['old']);
        }

        $model->auditActionOverride = 'reverted';
        $model->save();

        $redirectTo = $auditLog->contract_id
            ? route('contracts.show', ['contract' => $auditLog->contract_id, 'tab' => 'atividades'])
            : route('audit-logs.index');

        return redirect($redirectTo)->with('success', 'Alteração revertida com sucesso.');
    }
}
