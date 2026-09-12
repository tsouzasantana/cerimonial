<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Checklist padrão</h2>
            <a href="{{ route('checklist-templates.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Nova tarefa
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-4">
                    Essas tarefas são copiadas automaticamente para o checklist de todo novo contrato de
                    cerimonial, com o prazo calculado a partir da data do evento. Alterar aqui não muda
                    contratos já criados.
                </p>
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <th class="py-2 pr-3">Tarefa</th>
                            <th class="py-2 pr-3">Prazo</th>
                            <th class="py-2 pr-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($templates as $template)
                            <tr>
                                <td class="py-2 pr-3 font-medium text-gray-900">{{ $template->name }}</td>
                                <td class="py-2 pr-3 text-gray-600">
                                    @if ($template->days_offset > 0)
                                        {{ $template->days_offset }} {{ Str::plural('dia', $template->days_offset) }} antes do evento
                                    @elseif ($template->days_offset < 0)
                                        {{ abs($template->days_offset) }} {{ Str::plural('dia', abs($template->days_offset)) }} depois do evento
                                    @else
                                        No dia do evento
                                    @endif
                                </td>
                                <td class="py-2 pr-3 text-right space-x-3">
                                    <a href="{{ route('checklist-templates.edit', $template) }}" class="text-gray-600 hover:text-gray-900 text-sm">Editar</a>
                                    <form method="POST" action="{{ route('checklist-templates.destroy', $template) }}" class="inline" onsubmit="return confirm('Remover esta tarefa padrão?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-4 text-center text-gray-500">Nenhuma tarefa padrão cadastrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>

                <div class="mt-4">
                    {{ $templates->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
