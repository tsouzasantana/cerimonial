@php
    use App\Models\Contract;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Busca</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('search.index') }}" class="flex gap-3">
                    <x-text-input name="q" type="text" class="block w-full" value="{{ $term }}"
                            placeholder="Nome, CPF/CNPJ, e-mail, telefone, local do evento..." autofocus />
                    <x-primary-button type="submit">Buscar</x-primary-button>
                </form>
            </div>

            @if ($term === '')
                <p class="text-sm text-gray-500">Digite um termo para buscar clientes, contratos e fornecedores.</p>
            @elseif ($clients->isEmpty() && $contracts->isEmpty() && $vendors->isEmpty())
                <p class="text-sm text-gray-500">Nenhum resultado encontrado para "{{ $term }}".</p>
            @else
                @if ($clients->isNotEmpty())
                    <section class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Clientes</h3>
                        <ul class="divide-y divide-gray-100">
                            @foreach ($clients as $client)
                                <li class="py-2">
                                    <a href="{{ route('clients.show', $client) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                        {{ $client->name }}
                                    </a>
                                    <span class="text-xs text-gray-500">
                                        {{ $client->document ?: 'sem documento' }}
                                        @if ($client->email) &middot; {{ $client->email }} @endif
                                        @if ($client->phone) &middot; {{ $client->phone }} @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($contracts->isNotEmpty())
                    <section class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Contratos</h3>
                        <ul class="divide-y divide-gray-100">
                            @foreach ($contracts as $contract)
                                <li class="py-2">
                                    <a href="{{ route('contracts.show', $contract) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                        #{{ $contract->id }} &mdash; {{ $contract->client->name }}
                                    </a>
                                    <span class="text-xs text-gray-500">
                                        {{ Contract::statusOptions()[$contract->status] ?? $contract->status }}
                                        &middot; evento em {{ $contract->event_date->format('d/m/Y') }}
                                        @if ($contract->event_location) &middot; {{ $contract->event_location }} @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($vendors->isNotEmpty())
                    <section class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Fornecedores</h3>
                        <ul class="divide-y divide-gray-100">
                            @foreach ($vendors as $vendor)
                                <li class="py-2">
                                    <a href="{{ route('contracts.show', ['contract' => $vendor->contract_id, 'tab' => 'fornecedores']) }}"
                                       class="text-indigo-600 hover:text-indigo-800 font-medium">
                                        {{ $vendor->name }}
                                    </a>
                                    <span class="text-xs text-gray-500">
                                        {{ $vendor->document ?: 'sem documento' }}
                                        &middot; contrato #{{ $vendor->contract_id }} &mdash; {{ $vendor->contract->client->name }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
