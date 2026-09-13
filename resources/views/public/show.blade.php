@php
    use App\Models\Contract;
@endphp
<x-public-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Contrato de cerimonial &mdash; {{ $contract->client->name }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ tab: '{{ request('tab', 'resumo') }}' }">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div><dt class="text-gray-500">Status</dt><dd class="mt-1"><x-status-badge :variant="$contract->statusBadgeVariant()">{{ Contract::statusOptions()[$contract->status] ?? $contract->status }}</x-status-badge></dd></div>
                    <div><dt class="text-gray-500">Data do evento</dt><dd class="text-gray-900">{{ $contract->event_date->format('d/m/Y') }}</dd></div>
                    <div><dt class="text-gray-500">Local</dt><dd class="text-gray-900">{{ $contract->event_location ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">Valor total</dt><dd class="text-gray-900">R$ {{ number_format($contract->total, 2, ',', '.') }}</dd></div>
                </dl>
                @if ($contract->pdf_path)
                    <a href="{{ route('contracts.pdf.download', $contract) }}" class="inline-block mt-4 text-sm text-brand-600 hover:text-brand-800">Baixar contrato em PDF</a>
                @endif
            </div>

            <div class="border-b border-gray-200">
                <nav class="-mb-px flex flex-wrap gap-6">
                    @foreach (['resumo' => 'Serviços e pagamentos', 'documentos' => 'Documentos', 'ocorrencias' => 'Ocorrências', 'checklist' => 'Checklist', 'fornecedores' => 'Fornecedores', 'financeiro' => 'Controle financeiro'] as $key => $label)
                        <button type="button" @click="tab = '{{ $key }}'"
                                :class="tab === '{{ $key }}' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                            {{ $label }}
                        </button>
                    @endforeach
                </nav>
            </div>

            {{-- Serviços / pagamentos (somente leitura) --}}
            <div x-show="tab === 'resumo'" class="space-y-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Serviços contratados</h3>
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <th class="py-2 pr-3">Serviço</th>
                                <th class="py-2 pr-3">Qtde</th>
                                <th class="py-2 pr-3">Valor total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($contract->items as $item)
                                <tr>
                                    <td class="py-2 pr-3">{{ $item->service->name }}</td>
                                    <td class="py-2 pr-3">{{ $item->quantity }}</td>
                                    <td class="py-2 pr-3">R$ {{ number_format($item->total_price, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-3 text-center text-gray-500">Nenhum serviço adicionado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                    <div class="mt-4 pt-4 border-t flex justify-end text-sm">
                        <dl class="space-y-1 text-right">
                            <div><dt class="inline text-gray-500">Subtotal:</dt> <dd class="inline text-gray-900 ml-2">R$ {{ number_format($contract->subtotal, 2, ',', '.') }}</dd></div>
                            <div><dt class="inline text-gray-500">Desconto:</dt> <dd class="inline text-gray-900 ml-2">R$ {{ number_format($contract->discount, 2, ',', '.') }}</dd></div>
                            <div><dt class="inline text-gray-700 font-semibold">Total:</dt> <dd class="inline text-gray-900 ml-2 font-semibold">R$ {{ number_format($contract->total, 2, ',', '.') }}</dd></div>
                        </dl>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Parcelas de pagamento</h3>
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <th class="py-2 pr-3">#</th>
                                <th class="py-2 pr-3">Vencimento</th>
                                <th class="py-2 pr-3">Valor</th>
                                <th class="py-2 pr-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($contract->installments as $installment)
                                <tr>
                                    <td class="py-2 pr-3">{{ $installment->number }}</td>
                                    <td class="py-2 pr-3">{{ $installment->due_date->format('d/m/Y') }}</td>
                                    <td class="py-2 pr-3">R$ {{ number_format($installment->amount, 2, ',', '.') }}</td>
                                    <td class="py-2 pr-3">
                                        <x-status-badge :variant="$installment->statusBadgeVariant()">{{ \App\Models\Installment::statusOptions()[$installment->status] ?? $installment->status }}</x-status-badge>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-3 text-center text-gray-500">Nenhuma parcela cadastrada.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            {{-- Documentos --}}
            <div x-show="tab === 'documentos'" class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Documentos</h3>
                @include('documents._form', [
                    'documentTypes' => $documentTypes,
                    'formAction' => route('public.documents.store', $token),
                ])
                @include('documents._list', ['documents' => $contract->documents, 'isPublic' => true, 'token' => $token])
            </div>

            {{-- Ocorrências (somente leitura) --}}
            <div x-show="tab === 'ocorrencias'" class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Histórico</h3>
                @if ($contract->occurrences->isEmpty())
                    <p class="text-sm text-gray-500">Nenhuma ocorrência registrada.</p>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($contract->occurrences as $occurrence)
                            <li class="py-3">
                                <p class="text-sm font-medium text-gray-900">{{ $occurrence->type->name }} &mdash; {{ $occurrence->occurrence_date->format('d/m/Y') }}</p>
                                <p class="text-sm text-gray-600">{{ $occurrence->description }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Checklist --}}
            <div x-show="tab === 'checklist'" class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Checklist do evento</h3>
                @include('contracts._checklist', ['isPublic' => true])
            </div>

            {{-- Fornecedores --}}
            <div x-show="tab === 'fornecedores'" class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Fornecedores</h3>
                @include('contracts._vendors', ['isPublic' => true])
            </div>

            {{-- Controle financeiro --}}
            <div x-show="tab === 'financeiro'" class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Controle financeiro</h3>
                @include('contracts._financial', ['isPublic' => true])
            </div>
        </div>
    </div>
</x-public-layout>
