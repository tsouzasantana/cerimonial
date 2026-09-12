@php
    use App\Models\ContractTask;

    $isPublic = $isPublic ?? false;
    $allTasks = $contract->tasks;
    $overdueTasks = $allTasks->filter(fn ($t) => $t->isOverdue());
    $doneCount = $allTasks->where('status', ContractTask::STATUS_CONCLUIDA)->count();
    $totalCount = $allTasks->count();

    $statusUrl = fn ($task) => $isPublic
        ? route('public.tasks.status', ['token' => $contract->public_token, 'task' => $task])
        : route('contract-tasks.status', [$contract, $task]);

    $updateUrl = fn ($task) => $isPublic
        ? route('public.tasks.update', ['token' => $contract->public_token, 'task' => $task])
        : route('contract-tasks.update', [$contract, $task]);

    $currentSort = request()->query('checklist_sort', 'due_date');
    $currentDirection = request()->query('checklist_direction', 'asc') === 'desc' ? 'desc' : 'asc';
    $nextDirection = fn ($col) => ($currentSort === $col && $currentDirection === 'asc') ? 'desc' : 'asc';
    $sortUrl = fn ($col) => request()->fullUrlWithQuery(['checklist_sort' => $col, 'checklist_direction' => $nextDirection($col), 'tab' => 'checklist']);
    $sortIndicator = fn ($col) => $currentSort === $col ? ($currentDirection === 'asc' ? '▲' : '▼') : '';
@endphp

<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-gray-50 rounded-lg p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total de tarefas</p>
        <p class="text-2xl font-semibold text-gray-900">{{ $totalCount }}</p>
    </div>
    <div class="bg-red-50 rounded-lg p-4">
        <p class="text-xs text-red-600 uppercase tracking-wide">Atrasadas</p>
        <p class="text-2xl font-semibold text-red-700">{{ $overdueTasks->count() }}</p>
    </div>
    <div class="bg-green-50 rounded-lg p-4">
        <p class="text-xs text-green-700 uppercase tracking-wide">Concluídas</p>
        <p class="text-2xl font-semibold text-green-800">{{ $doneCount }}</p>
    </div>
    <div class="bg-indigo-50 rounded-lg p-4">
        <p class="text-xs text-indigo-700 uppercase tracking-wide">Progresso</p>
        <p class="text-2xl font-semibold text-indigo-800">{{ $totalCount ? round($doneCount / $totalCount * 100) : 0 }}%</p>
    </div>
</div>

@if ($overdueTasks->isNotEmpty())
    <div class="mb-6 rounded-md bg-red-50 border border-red-200 p-4">
        <p class="text-sm font-medium text-red-800 mb-1">{{ $overdueTasks->count() }} {{ Str::plural('tarefa', $overdueTasks->count()) }} com prazo vencido:</p>
        <ul class="text-sm text-red-700 list-disc list-inside">
            @foreach ($overdueTasks->sortBy('due_date') as $task)
                <li>{{ $task->name }} &mdash; venceu em {{ $task->due_date->format('d/m/Y') }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="GET" class="flex flex-wrap items-center gap-3 mb-4">
    <input type="hidden" name="tab" value="checklist">
    <input type="hidden" name="checklist_sort" value="{{ $currentSort }}">
    <input type="hidden" name="checklist_direction" value="{{ $currentDirection }}">
    <select name="checklist_status" class="border-gray-300 rounded-md shadow-sm text-sm" onchange="this.form.submit()">
        <option value="">Todos os status</option>
        @foreach (ContractTask::statusOptions() as $value => $label)
            <option value="{{ $value }}" @selected(request('checklist_status') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @if (request('checklist_status'))
        <a href="{{ request()->fullUrlWithQuery(['checklist_status' => null]) }}" class="text-sm text-gray-500 hover:text-gray-700">Limpar filtro</a>
    @endif
</form>

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 mb-4">
        <thead>
            <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <th class="py-2 pr-3"><a href="{{ $sortUrl('name') }}" class="hover:text-gray-700">Tarefa {{ $sortIndicator('name') }}</a></th>
                <th class="py-2 pr-3"><a href="{{ $sortUrl('due_date') }}" class="hover:text-gray-700">Prazo {{ $sortIndicator('due_date') }}</a></th>
                <th class="py-2 pr-3"><a href="{{ $sortUrl('status') }}" class="hover:text-gray-700">Status {{ $sortIndicator('status') }}</a></th>
                <th class="py-2 pr-3">Observações</th>
                <th class="py-2 pr-3 text-right">Ações</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($checklistTasks as $task)
                <tr>
                    <td class="py-2 pr-3 text-gray-900">{{ $task->name }}</td>
                    <td class="py-2 pr-3 {{ $task->isOverdue() ? 'text-red-600 font-medium' : 'text-gray-600' }}" data-task-due="{{ $task->id }}">
                        {{ $task->due_date->format('d/m/Y') }}
                        <span class="js-overdue-badge" @if(!$task->isOverdue()) hidden @endif>&nbsp;⚠</span>
                    </td>
                    <td class="py-2 pr-3">
                        <select class="js-task-status border-gray-300 rounded-md shadow-sm text-xs"
                                data-url="{{ $statusUrl($task) }}" data-task-id="{{ $task->id }}">
                            @foreach (ContractTask::statusOptions() as $value => $label)
                                <option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="py-2 pr-3 text-gray-600 max-w-xs truncate" title="{{ $task->notes }}">{{ $task->notes ?: '—' }}</td>
                    <td class="py-2 pr-3 text-right whitespace-nowrap">
                        <button type="button" class="text-gray-600 hover:text-gray-900 text-sm" x-data x-on:click="$dispatch('open-modal', 'edit-task-{{ $task->id }}')">Editar</button>
                        @if (! $isPublic)
                            <form method="POST" action="{{ route('contract-tasks.destroy', [$contract, $task]) }}" class="inline" onsubmit="return confirm('Inativar esta tarefa?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm ml-2">Inativar</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-3 text-center text-gray-500">Nenhuma tarefa encontrada.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@foreach ($checklistTasks as $task)
    <x-modal name="edit-task-{{ $task->id }}" maxWidth="md">
        <form method="POST" action="{{ $updateUrl($task) }}" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <h3 class="text-lg font-medium text-gray-900">Editar tarefa</h3>

            @if ($isPublic)
                <p class="text-sm font-medium text-gray-900">{{ $task->name }}</p>
                <input type="hidden" name="name" value="{{ $task->name }}">
            @else
                <div>
                    <x-input-label value="Nome da tarefa" />
                    <x-text-input name="name" type="text" class="mt-1 block w-full" value="{{ $task->name }}" required />
                </div>
            @endif

            <div>
                <x-input-label value="Prazo" />
                <x-text-input name="due_date" type="date" class="mt-1 block w-full" value="{{ $task->due_date->format('Y-m-d') }}" required />
            </div>

            <div>
                <x-input-label value="Status" />
                <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @foreach (ContractTask::statusOptions() as $value => $label)
                        <option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label value="Observações" />
                <textarea name="notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ $task->notes }}</textarea>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" class="text-sm text-gray-600 hover:text-gray-900" x-data x-on:click="$dispatch('close-modal', 'edit-task-{{ $task->id }}')">Cancelar</button>
                <x-primary-button type="submit">Salvar</x-primary-button>
            </div>
        </form>
    </x-modal>
@endforeach

@if (! $isPublic)
    <div class="mt-6 border-t pt-4">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Adicionar tarefa avulsa</h4>
        <form method="POST" action="{{ route('contract-tasks.store', $contract) }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <x-input-label value="Nome da tarefa" />
                <x-text-input name="name" type="text" class="mt-1 block w-64" required />
            </div>
            <div>
                <x-input-label value="Prazo" />
                <x-text-input name="due_date" type="date" class="mt-1 block w-40" required />
            </div>
            <div>
                <x-input-label value="Observações" />
                <x-text-input name="notes" type="text" class="mt-1 block w-64" />
            </div>
            <x-secondary-button type="submit">Adicionar</x-secondary-button>
        </form>
    </div>
@endif

<script>
document.addEventListener('change', function (event) {
    if (!event.target.matches('.js-task-status')) return;

    const select = event.target;
    const row = select.closest('tr');
    const dueCell = row.querySelector('[data-task-due]');

    fetch(select.dataset.url, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ status: select.value }),
    })
        .then(response => response.json())
        .then(data => {
            const badge = row.querySelector('.js-overdue-badge');
            if (data.is_overdue) {
                dueCell.classList.add('text-red-600', 'font-medium');
                dueCell.classList.remove('text-gray-600');
                badge.hidden = false;
            } else {
                dueCell.classList.remove('text-red-600', 'font-medium');
                dueCell.classList.add('text-gray-600');
                badge.hidden = true;
            }
        });
});
</script>
