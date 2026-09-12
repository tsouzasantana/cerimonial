<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Documentos</h2>
            <a href="{{ route('document-types.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Gerenciar tipos de documento</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="flex flex-wrap items-center gap-3 mb-4">
                    <x-text-input name="search" type="text" placeholder="Buscar por título" class="max-w-sm" :value="request('search')" />
                    <select name="document_type_id" class="border-gray-300 rounded-md shadow-sm text-sm" onchange="this.form.submit()">
                        <option value="">Todos os tipos</option>
                        @foreach ($documentTypes as $type)
                            <option value="{{ $type->id }}" @selected(request('document_type_id') == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="trashed" value="1" @checked(request()->boolean('trashed')) onchange="this.form.submit()">
                        Mostrar inativados
                    </label>
                    <x-secondary-button type="submit">Filtrar</x-secondary-button>
                </form>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <th class="py-2 pr-3">Título</th>
                            <th class="py-2 pr-3">Tipo</th>
                            <th class="py-2 pr-3">Vinculado a</th>
                            <th class="py-2 pr-3">Enviado em</th>
                            <th class="py-2 pr-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($documents as $document)
                            <tr>
                                <td class="py-2 pr-3">
                                    @if ($document->isLink())
                                        <a href="{{ $document->url }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ $document->title }} ↗</a>
                                    @else
                                        <a href="{{ route('documents.download', $document) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ $document->title }}</a>
                                    @endif
                                </td>
                                <td class="py-2 pr-3 text-gray-600">{{ $document->documentType->name }}</td>
                                <td class="py-2 pr-3 text-gray-600">
                                    @if ($document->contract)
                                        <a href="{{ route('contracts.show', $document->contract) }}" class="hover:text-indigo-800">Contrato #{{ $document->contract->id }}</a>
                                    @elseif ($document->client)
                                        <a href="{{ route('clients.show', $document->client) }}" class="hover:text-indigo-800">{{ $document->client->name }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="py-2 pr-3 text-gray-600">{{ $document->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-2 pr-3 text-right space-x-3">
                                    @if ($document->trashed())
                                        <form method="POST" action="{{ route('documents.restore', $document->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:text-green-800 text-sm">Reativar</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('documents.send-email', $document) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-indigo-600 hover:text-indigo-800 text-sm">Enviar e-mail</button>
                                        </form>
                                        <form method="POST" action="{{ route('documents.destroy', $document) }}" class="inline" onsubmit="return confirm('Inativar este documento?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Inativar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">Nenhum documento encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $documents->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
