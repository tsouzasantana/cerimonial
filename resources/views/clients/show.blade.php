<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $client->name }}</h2>
            <div class="space-x-3">
                <a href="{{ route('clients.edit', $client) }}" class="text-sm text-gray-600 hover:text-gray-900">Editar</a>
                <a href="{{ route('clients.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Voltar</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Dados do cliente</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-gray-500">Documento</dt><dd class="text-gray-900">{{ $client->document ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">RG</dt><dd class="text-gray-900">{{ $client->rg ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">E-mail</dt><dd class="text-gray-900">{{ $client->email ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">Telefone</dt><dd class="text-gray-900">{{ $client->phone ?: '—' }}</dd></div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">Endereço</dt>
                        <dd class="text-gray-900">
                            {{ trim("{$client->address_street}, {$client->address_number} {$client->address_complement}", ', ') }}
                            @if($client->address_district) &mdash; {{ $client->address_district }} @endif
                            @if($client->address_city) &mdash; {{ $client->address_city }}/{{ $client->address_state }} @endif
                            @if($client->address_zipcode) &mdash; CEP {{ $client->address_zipcode }} @endif
                        </dd>
                    </div>
                    @if ($client->notes)
                        <div class="sm:col-span-2"><dt class="text-gray-500">Observações</dt><dd class="text-gray-900">{{ $client->notes }}</dd></div>
                    @endif
                </dl>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Contratos</h3>
                    <a href="{{ route('contracts.create') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Novo contrato</a>
                </div>
                @if ($client->contracts->isEmpty())
                    <p class="text-sm text-gray-500">Nenhum contrato cadastrado.</p>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($client->contracts as $contract)
                            <li class="py-2 flex justify-between items-center">
                                <a href="{{ route('contracts.show', $contract) }}" class="text-indigo-600 hover:text-indigo-800">
                                    Evento em {{ $contract->event_date->format('d/m/Y') }}
                                </a>
                                <span class="text-sm text-gray-500">{{ \App\Models\Contract::statusOptions()[$contract->status] ?? $contract->status }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Documentos do cliente</h3>

                @include('documents._form', ['clientId' => $client->id, 'documentTypes' => $documentTypes])
                @include('documents._list', ['documents' => $client->documents])
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if ($client->trashed())
                    <form method="POST" action="{{ route('clients.restore', $client->id) }}">
                        @csrf
                        <button type="submit" class="text-green-600 hover:text-green-800 text-sm">Reativar cliente</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('clients.destroy', $client) }}" onsubmit="return confirm('Inativar este cliente?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Inativar cliente</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
