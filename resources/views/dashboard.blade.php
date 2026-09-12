<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Contratos ativos</p>
                    <p class="text-3xl font-semibold text-gray-900">{{ $activeContracts }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Clientes cadastrados</p>
                    <p class="text-3xl font-semibold text-gray-900">{{ $totalClients }}</p>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Próximos eventos</h3>
                @if ($upcomingEvents->isEmpty())
                    <p class="text-sm text-gray-500">Nenhum evento futuro cadastrado.</p>
                @else
                    <ul class="divide-y divide-gray-200">
                        @foreach ($upcomingEvents as $contract)
                            <li class="py-3 flex justify-between items-center">
                                <div>
                                    <a href="{{ route('contracts.show', $contract) }}" class="font-medium text-brand-600 hover:text-brand-800">
                                        {{ $contract->client->name }}
                                    </a>
                                    <p class="text-sm text-gray-500">{{ $contract->event_date->format('d/m/Y') }} &mdash; {{ $contract->event_location }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Parcelas em atraso</h3>
                @if ($overdueInstallments->isEmpty())
                    <p class="text-sm text-gray-500">Nenhuma parcela em atraso.</p>
                @else
                    <ul class="divide-y divide-gray-200">
                        @foreach ($overdueInstallments as $installment)
                            <li class="py-3 flex justify-between items-center">
                                <div>
                                    <a href="{{ route('contracts.show', $installment->contract) }}" class="font-medium text-brand-600 hover:text-brand-800">
                                        {{ $installment->contract->client->name }}
                                    </a>
                                    <p class="text-sm text-gray-500">Parcela {{ $installment->number }} &mdash; vencida em {{ $installment->due_date->format('d/m/Y') }}</p>
                                </div>
                                <span class="text-sm font-medium text-red-600">R$ {{ number_format($installment->amount, 2, ',', '.') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
