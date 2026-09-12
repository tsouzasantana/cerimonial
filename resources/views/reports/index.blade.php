<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Relatórios</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- Financeiro --}}
            <section class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Financeiro</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
                    <div class="bg-green-50 rounded-lg p-4">
                        <p class="text-xs text-green-700 uppercase tracking-wide">Recebido</p>
                        <p class="text-xl font-semibold text-green-800">R$ {{ number_format($financeiro['recebido'], 2, ',', '.') }}</p>
                    </div>
                    <div class="bg-indigo-50 rounded-lg p-4">
                        <p class="text-xs text-indigo-700 uppercase tracking-wide">A receber</p>
                        <p class="text-xl font-semibold text-indigo-800">R$ {{ number_format($financeiro['a_receber'], 2, ',', '.') }}</p>
                    </div>
                    <div class="bg-red-50 rounded-lg p-4">
                        <p class="text-xs text-red-600 uppercase tracking-wide">Parcelas em atraso</p>
                        <p class="text-xl font-semibold text-red-700">{{ $financeiro['atrasadas_count'] }} <span class="text-sm font-normal">(R$ {{ number_format($financeiro['atrasadas_total'], 2, ',', '.') }})</span></p>
                    </div>
                </div>
                <h4 class="text-sm font-medium text-gray-700 mb-2">Receita recebida por mês</h4>
                @include('reports._bar-chart', ['series' => $financeiro['receita_por_mes'], 'isCurrency' => true])
            </section>

            {{-- Contratos --}}
            <section class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Contratos</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                    @foreach ($contratos['por_status'] as $item)
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">{{ $item['label'] }}</p>
                            <p class="text-xl font-semibold text-gray-900">{{ $item['total'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Novos contratos por mês</h4>
                        @include('reports._bar-chart', ['series' => $contratos['novos_por_mes']])
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Próximos eventos (30 dias) &mdash; {{ $contratos['proximos_eventos_count'] }}</h4>
                        @if ($contratos['proximos_eventos']->isEmpty())
                            <p class="text-sm text-gray-500">Nenhum evento nos próximos 30 dias.</p>
                        @else
                            <ul class="divide-y divide-gray-100 text-sm">
                                @foreach ($contratos['proximos_eventos'] as $contract)
                                    <li class="py-2 flex justify-between">
                                        <a href="{{ route('contracts.show', $contract) }}" class="text-indigo-600 hover:text-indigo-800">{{ $contract->client->name }}</a>
                                        <span class="text-gray-500">{{ $contract->event_date->format('d/m/Y') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </section>

            {{-- Checklist --}}
            <section class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Checklist</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Total de tarefas</p>
                        <p class="text-xl font-semibold text-gray-900">{{ $checklist['total'] }}</p>
                    </div>
                    <div class="bg-red-50 rounded-lg p-4">
                        <p class="text-xs text-red-600 uppercase tracking-wide">Atrasadas (todos os contratos)</p>
                        <p class="text-xl font-semibold text-red-700">{{ $checklist['atrasadas'] }}</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <p class="text-xs text-green-700 uppercase tracking-wide">Concluídas</p>
                        <p class="text-xl font-semibold text-green-800">{{ $checklist['concluidas'] }}</p>
                    </div>
                    <div class="bg-indigo-50 rounded-lg p-4">
                        <p class="text-xs text-indigo-700 uppercase tracking-wide">Taxa de conclusão geral</p>
                        <p class="text-xl font-semibold text-indigo-800">{{ $checklist['taxa_conclusao'] }}%</p>
                    </div>
                </div>

                <h4 class="text-sm font-medium text-gray-700 mb-2">Contratos com mais tarefas atrasadas</h4>
                @if ($checklist['contratos_com_atraso']->isEmpty())
                    <p class="text-sm text-gray-500">Nenhum contrato com tarefas atrasadas. 🎉</p>
                @else
                    <ul class="divide-y divide-gray-100 text-sm">
                        @foreach ($checklist['contratos_com_atraso'] as $contract)
                            <li class="py-2 flex justify-between">
                                <a href="{{ route('contracts.show', ['contract' => $contract, 'tab' => 'checklist']) }}" class="text-indigo-600 hover:text-indigo-800">{{ $contract->client->name }}</a>
                                <span class="text-red-600 font-medium">{{ $contract->overdue_tasks_count }} atrasada(s)</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Clientes --}}
            <section class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Clientes</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Total de clientes</p>
                        <p class="text-xl font-semibold text-gray-900">{{ $clientes['total_ativos'] }}</p>
                    </div>
                </div>
                <h4 class="text-sm font-medium text-gray-700 mb-2">Novos clientes por mês</h4>
                @include('reports._bar-chart', ['series' => $clientes['novos_por_mes']])
            </section>
        </div>
    </div>
</x-app-layout>
