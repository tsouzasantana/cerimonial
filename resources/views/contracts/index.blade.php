<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Contratos de cerimonial</h2>
            <a href="{{ route('contracts.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Novo contrato
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="flex flex-wrap items-center gap-3 mb-4">
                    <x-text-input name="search" type="text" placeholder="Buscar por cliente" class="max-w-sm" :value="request('search')" />
                    <select name="status" class="border-gray-300 rounded-md shadow-sm text-sm" onchange="this.form.submit()">
                        <option value="">Todos os status</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="trashed" value="1" @checked(request()->boolean('trashed')) onchange="this.form.submit()">
                        Mostrar inativados
                    </label>
                    <x-secondary-button type="submit">Filtrar</x-secondary-button>
                </form>

                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <th class="py-2 pr-3">Cliente</th>
                            <th class="py-2 pr-3">Data do evento</th>
                            <th class="py-2 pr-3">Status</th>
                            <th class="py-2 pr-3">Valor total</th>
                            <th class="py-2 pr-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($contracts as $contract)
                            <tr>
                                <td class="py-2 pr-3">
                                    <a href="{{ route('contracts.show', $contract) }}" class="text-brand-600 hover:text-brand-800 font-medium">{{ $contract->client->name }}</a>
                                </td>
                                <td class="py-2 pr-3 text-gray-600">{{ $contract->event_date->format('d/m/Y') }}</td>
                                <td class="py-2 pr-3 text-gray-600">{{ $statusOptions[$contract->status] ?? $contract->status }}</td>
                                <td class="py-2 pr-3 text-gray-600">R$ {{ number_format($contract->total, 2, ',', '.') }}</td>
                                <td class="py-2 pr-3 text-right space-x-3">
                                    @if ($contract->trashed())
                                        <form method="POST" action="{{ route('contracts.restore', $contract->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:text-green-800 text-sm">Reativar</button>
                                        </form>
                                    @else
                                        <a href="{{ route('contracts.edit', $contract) }}" class="text-gray-600 hover:text-gray-900 text-sm">Editar</a>
                                        <form method="POST" action="{{ route('contracts.destroy', $contract) }}" class="inline" onsubmit="return confirm('Inativar este contrato?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Inativar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">Nenhum contrato encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>

                <div class="mt-4">
                    {{ $contracts->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
