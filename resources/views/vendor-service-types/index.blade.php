<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tipos de serviço de fornecedor</h2>
            <x-link-button href="{{ route('vendor-service-types.create') }}">
                Novo tipo
            </x-link-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-4">
                    Tipos de serviço usados para classificar fornecedores dentro de um contrato
                    (buffet, decoração, fotografia, etc). O tipo "Outro" também pode ser digitado
                    livremente na hora de cadastrar um fornecedor.
                </p>
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <th class="py-2 pr-3">Nome</th>
                            <th class="py-2 pr-3">Fornecedores</th>
                            <th class="py-2 pr-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($types as $type)
                            <tr>
                                <td class="py-2 pr-3 font-medium text-gray-900">{{ $type->name }}</td>
                                <td class="py-2 pr-3 text-gray-600">{{ $type->vendors_count }}</td>
                                <td class="py-2 pr-3 text-right space-x-3">
                                    <a href="{{ route('vendor-service-types.edit', $type) }}" class="text-gray-600 hover:text-gray-900 text-sm">Editar</a>
                                    <form method="POST" action="{{ route('vendor-service-types.destroy', $type) }}" class="inline" onsubmit="return confirm('Remover este tipo de serviço?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-4 text-center text-gray-500">Nenhum tipo cadastrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>

                <div class="mt-4">
                    {{ $types->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
