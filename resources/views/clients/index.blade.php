<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Clientes</h2>
            <a href="{{ route('clients.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Novo cliente
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="flex flex-wrap items-center gap-3 mb-4">
                    <x-text-input name="search" type="text" placeholder="Buscar por nome, documento ou e-mail" class="max-w-sm" :value="request('search')" />
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="trashed" value="1" @checked(request()->boolean('trashed')) onchange="this.form.submit()">
                        Mostrar inativados
                    </label>
                    <x-secondary-button type="submit">Filtrar</x-secondary-button>
                </form>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <th class="py-2 pr-3">Nome</th>
                            <th class="py-2 pr-3">Documento</th>
                            <th class="py-2 pr-3">Telefone</th>
                            <th class="py-2 pr-3">E-mail</th>
                            <th class="py-2 pr-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($clients as $client)
                            <tr>
                                <td class="py-2 pr-3">
                                    <a href="{{ route('clients.show', $client) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ $client->name }}</a>
                                </td>
                                <td class="py-2 pr-3 text-gray-600">{{ $client->document }}</td>
                                <td class="py-2 pr-3 text-gray-600">{{ $client->phone }}</td>
                                <td class="py-2 pr-3 text-gray-600">{{ $client->email }}</td>
                                <td class="py-2 pr-3 text-right space-x-3">
                                    @if ($client->trashed())
                                        <form method="POST" action="{{ route('clients.restore', $client->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:text-green-800 text-sm">Reativar</button>
                                        </form>
                                    @else
                                        <a href="{{ route('clients.edit', $client) }}" class="text-gray-600 hover:text-gray-900 text-sm">Editar</a>
                                        <form method="POST" action="{{ route('clients.destroy', $client) }}" class="inline" onsubmit="return confirm('Inativar este cliente?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Inativar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">Nenhum cliente encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $clients->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
