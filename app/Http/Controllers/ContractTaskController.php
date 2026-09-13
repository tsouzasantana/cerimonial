<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresContractOwnership;
use App\Http\Requests\ContractTaskRequest;
use App\Models\Contract;
use App\Models\ContractTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContractTaskController extends Controller
{
    use EnsuresContractOwnership;

    public function store(ContractTaskRequest $request, Contract $contract): RedirectResponse
    {
        $maxOrder = (int) $contract->tasks()->max('sort_order');

        $contract->tasks()->create($request->validated() + [
            'status' => ContractTask::STATUS_A_INICIAR,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('contracts.show', ['contract' => $contract, 'tab' => 'checklist'])
            ->with('success', 'Tarefa adicionada ao checklist.');
    }

    public function update(ContractTaskRequest $request, Contract $contract, ContractTask $task): RedirectResponse
    {
        $this->ensureBelongsToContract($task, $contract);

        $task->update($request->validated());

        return redirect()->route('contracts.show', ['contract' => $contract, 'tab' => 'checklist'])
            ->with('success', 'Tarefa atualizada.');
    }

    public function updateStatus(Request $request, Contract $contract, ContractTask $task): JsonResponse
    {
        $this->ensureBelongsToContract($task, $contract);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(ContractTask::statusOptions()))],
        ]);

        $task->update(['status' => $data['status']]);

        return response()->json([
            'status' => $task->status,
            'status_label' => ContractTask::statusOptions()[$task->status],
            'is_overdue' => $task->isOverdue(),
        ]);
    }

    public function destroy(Contract $contract, ContractTask $task): RedirectResponse
    {
        $this->ensureBelongsToContract($task, $contract);

        $task->delete();

        return redirect()->route('contracts.show', ['contract' => $contract, 'tab' => 'checklist'])
            ->with('success', 'Tarefa inativada.');
    }

    public function restore(Contract $contract, int $task): RedirectResponse
    {
        $task = ContractTask::withTrashed()->findOrFail($task);
        $this->ensureBelongsToContract($task, $contract);

        $task->restore();

        return redirect()->route('contracts.show', ['contract' => $contract, 'tab' => 'checklist'])
            ->with('success', 'Tarefa reativada.');
    }
}
