<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Serviços do cerimonial</h2>
            <a href="{{ route('services.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Novo serviço
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="flex flex-wrap items-center gap-3 mb-4">
                    <x-text-input name="search" type="text" placeholder="Buscar por nome" class="max-w-sm" :value="request('search')" />
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
                            <th class="py-2 pr-3">Código</th>
                            <th class="py-2 pr-3">Valor</th>
                            <th class="py-2 pr-3">Status</th>
                            <th class="py-2 pr-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($services as $service)
                            <tr>
                                <td class="py-2 pr-3 font-medium text-gray-900">{{ $service->name }}</td>
                                <td class="py-2 pr-3 text-gray-600">{{ $service->code }}</td>
                                <td class="py-2 pr-3 text-gray-600">R$ {{ number_format($service->price, 2, ',', '.') }}</td>
                                <td class="py-2 pr-3">
                                    @if ($service->trashed())
                                        <span class="text-xs text-red-600">Inativado</span>
                                    @elseif ($service->active)
                                        <span class="text-xs text-green-600">Ativo</span>
                                    @else
                                        <span class="text-xs text-gray-500">Desativado</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-3 text-right space-x-3">
                                    @if ($service->trashed())
                                        <form method="POST" action="{{ route('services.restore', $service->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:text-green-800 text-sm">Reativar</button>
                                        </form>
                                    @else
                                        <a href="{{ route('services.edit', $service) }}" class="text-gray-600 hover:text-gray-900 text-sm">Editar</a>
                                        <form method="POST" action="{{ route('services.destroy', $service) }}" class="inline" onsubmit="return confirm('Inativar este serviço?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Inativar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">Nenhum serviço cadastrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $services->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
