<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Auditoria</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
                    <div>
                        <x-input-label for="filter-contract_id" value="Contrato" />
                        <select id="filter-contract_id" name="contract_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">Todos</option>
                            @foreach ($contracts as $contract)
                                <option value="{{ $contract->id }}" @selected((string) request('contract_id') === (string) $contract->id)>
                                    #{{ $contract->id }} &mdash; {{ $contract->client->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="filter-actor_type" value="Quem" />
                        <select id="filter-actor_type" name="actor_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">Todos</option>
                            @foreach ($actorTypeOptions as $value => $label)
                                <option value="{{ $value }}" @selected(request('actor_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="filter-action" value="Ação" />
                        <select id="filter-action" name="action" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">Todas</option>
                            @foreach ($actionOptions as $value => $label)
                                <option value="{{ $value }}" @selected(request('action') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="filter-date_from" value="De" />
                        <x-text-input id="filter-date_from" name="date_from" type="date" class="mt-1 block w-full text-sm" value="{{ request('date_from') }}" />
                    </div>
                    <div>
                        <x-input-label for="filter-date_to" value="Até" />
                        <x-text-input id="filter-date_to" name="date_to" type="date" class="mt-1 block w-full text-sm" value="{{ request('date_to') }}" />
                    </div>
                    <div class="sm:col-span-5 flex gap-3">
                        <x-secondary-button type="submit">Filtrar</x-secondary-button>
                        @if (request()->anyFilled(['contract_id', 'actor_type', 'action', 'date_from', 'date_to']))
                            <a href="{{ route('audit-logs.index') }}" class="text-sm text-gray-500 hover:text-gray-700 self-center">Limpar filtros</a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if ($logs->isEmpty())
                    <p class="text-sm text-gray-500">Nenhuma atividade encontrada.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 mb-4">
                            <thead>
                                <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <th class="py-2 pr-3">Quando</th>
                                    <th class="py-2 pr-3">Contrato</th>
                                    <th class="py-2 pr-3">Item</th>
                                    <th class="py-2 pr-3">Ação</th>
                                    <th class="py-2 pr-3">Quem</th>
                                    <th class="py-2 pr-3">Alterações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($logs as $log)
                                    <tr>
                                        <td class="py-2 pr-3 text-gray-600 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="py-2 pr-3 text-gray-600 whitespace-nowrap">
                                            @if ($log->contract)
                                                <a href="{{ route('contracts.show', $log->contract) }}" class="text-brand-600 hover:text-brand-800">
                                                    #{{ $log->contract->id }} &mdash; {{ $log->contract->client->name }}
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="py-2 pr-3 text-gray-900">{{ $log->subjectLabel() }}: {{ $log->auditable_label }}</td>
                                        <td class="py-2 pr-3 text-gray-600">{{ $actionOptions[$log->action] ?? $log->action }}</td>
                                        <td class="py-2 pr-3 text-gray-600">
                                            {{ $log->actor_name ?: ($actorTypeOptions[$log->actor_type] ?? $log->actor_type) }}
                                            @if ($log->actor_type === 'client')
                                                <span class="text-xs text-gray-400">(cliente)</span>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-3 text-gray-500 text-xs max-w-sm">
                                            @include('audit-logs._changes-cell', ['log' => $log])
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{ $logs->links() }}
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
