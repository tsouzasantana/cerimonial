@php
    use App\Models\AuditLog;
@endphp

@if ($logs->isEmpty())
    <p class="text-sm text-gray-500">Nenhuma atividade registrada ainda.</p>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 mb-4">
            <thead>
                <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <th class="py-2 pr-3">Quando</th>
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
                        <td class="py-2 pr-3 text-gray-900">{{ $log->subjectLabel() }}: {{ $log->auditable_label }}</td>
                        <td class="py-2 pr-3 text-gray-600">{{ AuditLog::actionOptions()[$log->action] ?? $log->action }}</td>
                        <td class="py-2 pr-3 text-gray-600">
                            {{ $log->actor_name ?: (AuditLog::actorTypeOptions()[$log->actor_type] ?? $log->actor_type) }}
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

    {{ $logs->appends(['tab' => 'atividades'])->links() }}
@endif
