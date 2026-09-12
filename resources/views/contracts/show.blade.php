@php
    use App\Models\Contract;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Contrato #{{ $contract->id }} &mdash; {{ $contract->client->name }}
            </h2>
            <div class="space-x-3">
                <a href="{{ route('contracts.edit', $contract) }}" class="text-sm text-gray-600 hover:text-gray-900">Editar</a>
                <a href="{{ route('contracts.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Voltar</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12" x-data="{ tab: '{{ request('tab', 'resumo') }}' }">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap justify-between gap-4">
                    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm flex-1">
                        <div><dt class="text-gray-500">Status</dt><dd class="text-gray-900 font-medium">{{ Contract::statusOptions()[$contract->status] ?? $contract->status }}</dd></div>
                        <div><dt class="text-gray-500">Data do evento</dt><dd class="text-gray-900">{{ $contract->event_date->format('d/m/Y') }}</dd></div>
                        <div><dt class="text-gray-500">Local</dt><dd class="text-gray-900">{{ $contract->event_location ?: '—' }}</dd></div>
                        <div><dt class="text-gray-500">Assinado em</dt><dd class="text-gray-900">{{ optional($contract->signed_at)->format('d/m/Y') ?: 'Não assinado' }}</dd></div>
                    </dl>
                    <div class="flex flex-col gap-2">
                        <form method="POST" action="{{ route('contracts.pdf', $contract) }}">
                            @csrf
                            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                                Gerar PDF do contrato
                            </button>
                        </form>
                        <a href="{{ route('contracts.pdf.download', $contract) }}" class="text-center text-sm text-indigo-600 hover:text-indigo-800">Baixar último PDF</a>
                        <form method="POST" action="{{ route('contracts.send-email', $contract) }}" onsubmit="return confirm('Enviar o PDF do contrato para o e-mail do cliente?');">
                            @csrf
                            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                Enviar por e-mail
                            </button>
                        </form>
                    </div>
                </div>
                @if ($contract->notes)
                    <p class="mt-4 text-sm text-gray-600"><strong>Observações:</strong> {{ $contract->notes }}</p>
                @endif
                @if ($contract->updatedByLabel())
                    <p class="mt-2 text-xs text-gray-400">Última atualização: {{ $contract->updatedByLabel() }} em {{ $contract->updated_at->format('d/m/Y H:i') }}</p>
                @endif

                <div class="mt-4 pt-4 border-t flex flex-wrap items-center justify-between gap-3">
                    <div class="text-sm">
                        <span class="text-gray-500">Link público do cliente (checklist + documentos, sem login):</span>
                        <input type="text" readonly onclick="this.select()" value="{{ route('public.gate', $contract->public_token) }}"
                                class="ml-2 w-72 text-xs border-gray-300 rounded-md bg-gray-50 text-gray-700">
                    </div>
                    <form method="POST" action="{{ route('contracts.regenerate-public-link', $contract) }}" onsubmit="return confirm('Gerar um novo link invalida o link atual. Continuar?');">
                        @csrf
                        <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Gerar novo link</button>
                    </form>
                </div>
            </div>

            <div class="border-b border-gray-200">
                <nav class="-mb-px flex flex-wrap gap-6">
                    @foreach (['resumo' => 'Serviços e pagamentos', 'documentos' => 'Documentos', 'ocorrencias' => 'Ocorrências', 'checklist' => 'Checklist', 'fornecedores' => 'Fornecedores', 'atividades' => 'Atividades'] as $key => $label)
                        <button type="button" @click="tab = '{{ $key }}'"
                                :class="tab === '{{ $key }}' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                            {{ $label }}
                        </button>
                    @endforeach
                </nav>
            </div>

            {{-- Serviços / itens + Parcelas --}}
            <div x-show="tab === 'resumo'" class="space-y-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Serviços contratados</h3>

                    <table class="min-w-full divide-y divide-gray-200 mb-4">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <th class="py-2 pr-3">Serviço</th>
                                <th class="py-2 pr-3">Qtde</th>
                                <th class="py-2 pr-3">Valor unitário</th>
                                <th class="py-2 pr-3">Total</th>
                                <th class="py-2 pr-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($contract->items as $item)
                                <tr>
                                    <td class="py-2 pr-3">{{ $item->service->name }}</td>
                                    <td class="py-2 pr-3">{{ $item->quantity }}</td>
                                    <td class="py-2 pr-3">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                    <td class="py-2 pr-3">R$ {{ number_format($item->total_price, 2, ',', '.') }}</td>
                                    <td class="py-2 pr-3 text-right">
                                        <form method="POST" action="{{ route('contract-items.destroy', [$contract, $item]) }}" onsubmit="return confirm('Remover este item?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Remover</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-3 text-center text-gray-500">Nenhum serviço adicionado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <form method="POST" action="{{ route('contract-items.store', $contract) }}" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div>
                            <x-input-label for="service_id" value="Serviço" />
                            <select id="service_id" name="service_id" class="mt-1 block w-64 border-gray-300 rounded-md shadow-sm" required>
                                <option value="">Selecione...</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}">{{ $service->name }} (R$ {{ number_format($service->price, 2, ',', '.') }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="quantity" value="Quantidade" />
                            <x-text-input id="quantity" name="quantity" type="number" min="1" value="1" class="mt-1 block w-24" required />
                        </div>
                        <x-secondary-button type="submit">Adicionar</x-secondary-button>
                    </form>

                    <div class="mt-6 border-t pt-4 flex justify-end">
                        <dl class="text-sm space-y-1 text-right">
                            <div><dt class="inline text-gray-500">Subtotal:</dt> <dd class="inline text-gray-900 ml-2">R$ {{ number_format($contract->subtotal, 2, ',', '.') }}</dd></div>
                            <div><dt class="inline text-gray-500">Desconto:</dt> <dd class="inline text-gray-900 ml-2">R$ {{ number_format($contract->discount, 2, ',', '.') }}</dd></div>
                            <div><dt class="inline text-gray-700 font-semibold">Total:</dt> <dd class="inline text-gray-900 ml-2 font-semibold">R$ {{ number_format($contract->total, 2, ',', '.') }}</dd></div>
                        </dl>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Parcelas de pagamento</h3>

                    <table class="min-w-full divide-y divide-gray-200 mb-4">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <th class="py-2 pr-3">#</th>
                                <th class="py-2 pr-3">Vencimento</th>
                                <th class="py-2 pr-3">Valor</th>
                                <th class="py-2 pr-3">Status</th>
                                <th class="py-2 pr-3">Pago em</th>
                                <th class="py-2 pr-3">Forma</th>
                                <th class="py-2 pr-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($contract->installments as $installment)
                                <tr>
                                    <td class="py-2 pr-3">{{ $installment->number }}</td>
                                    <td class="py-2 pr-3 {{ $installment->isOverdue() ? 'text-red-600 font-medium' : '' }}">{{ $installment->due_date->format('d/m/Y') }}</td>
                                    <td class="py-2 pr-3">R$ {{ number_format($installment->amount, 2, ',', '.') }}</td>
                                    <td class="py-2 pr-3">
                                        <form method="POST" action="{{ route('installments.update', [$contract, $installment]) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="number" value="{{ $installment->number }}">
                                            <input type="hidden" name="amount" value="{{ $installment->amount }}">
                                            <input type="hidden" name="due_date" value="{{ $installment->due_date->format('Y-m-d') }}">
                                            <select name="status" class="border-gray-300 rounded-md shadow-sm text-xs" onchange="this.form.submit()">
                                                @foreach ($installmentStatuses as $value => $label)
                                                    <option value="{{ $value }}" @selected($installment->status === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                    </td>
                                    <td class="py-2 pr-3">{{ optional($installment->paid_at)->format('d/m/Y') ?: '—' }}</td>
                                    <td class="py-2 pr-3">
                                            <select name="payment_method" class="border-gray-300 rounded-md shadow-sm text-xs" onchange="this.form.submit()">
                                                <option value="">—</option>
                                                @foreach ($paymentMethods as $value => $label)
                                                    <option value="{{ $value }}" @selected($installment->payment_method === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    </td>
                                    <td class="py-2 pr-3 text-right">
                                        <form method="POST" action="{{ route('installments.destroy', [$contract, $installment]) }}" onsubmit="return confirm('Inativar esta parcela?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Inativar</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-3 text-center text-gray-500">Nenhuma parcela cadastrada.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <form method="POST" action="{{ route('installments.store', $contract) }}" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div>
                            <x-input-label for="number" value="Parcela nº" />
                            <x-text-input id="number" name="number" type="number" min="1" class="mt-1 block w-20" required />
                        </div>
                        <div>
                            <x-input-label for="amount" value="Valor (R$)" />
                            <x-text-input id="amount" name="amount" type="number" step="0.01" min="0" class="mt-1 block w-32" required />
                        </div>
                        <div>
                            <x-input-label for="due_date" value="Vencimento" />
                            <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-40" required />
                        </div>
                        <div>
                            <x-input-label for="status" value="Status" />
                            <select id="status" name="status" class="mt-1 block w-32 border-gray-300 rounded-md shadow-sm">
                                @foreach ($installmentStatuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-secondary-button type="submit">Adicionar parcela</x-secondary-button>
                    </form>

                    <div class="mt-4 pt-4 border-t" x-data="{ open: false }">
                        <button type="button" class="text-sm text-indigo-600 hover:text-indigo-800" x-on:click="open = ! open">
                            Gerar parcelas em lote
                        </button>
                        <form x-show="open" x-cloak method="POST" action="{{ route('installments.store-batch', $contract) }}"
                                class="mt-3 flex flex-wrap items-end gap-3" onsubmit="return confirm('Gerar as parcelas informadas?');">
                            @csrf
                            <div>
                                <x-input-label for="total_amount" value="Valor total (R$)" />
                                <x-text-input id="total_amount" name="total_amount" type="number" step="0.01" min="0.01" class="mt-1 block w-32" required />
                            </div>
                            <div>
                                <x-input-label for="installment_count" value="Qtde de parcelas" />
                                <x-text-input id="installment_count" name="installment_count" type="number" min="1" max="60" class="mt-1 block w-28" required />
                            </div>
                            <div>
                                <x-input-label for="first_due_date" value="1º vencimento" />
                                <x-text-input id="first_due_date" name="first_due_date" type="date" class="mt-1 block w-40" required />
                            </div>
                            <div>
                                <x-input-label for="interval_months" value="Intervalo (meses)" />
                                <x-text-input id="interval_months" name="interval_months" type="number" min="1" max="12" value="1" class="mt-1 block w-24" required />
                            </div>
                            <x-secondary-button type="submit">Gerar parcelas</x-secondary-button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Documentos --}}
            <div x-show="tab === 'documentos'" class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Documentos do contrato</h3>

                @include('documents._form', ['contractId' => $contract->id, 'documentTypes' => $documentTypes])
                @include('documents._list', ['documents' => $contract->documents])
            </div>

            {{-- Ocorrências / histórico --}}
            <div x-show="tab === 'ocorrencias'" class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Histórico de ocorrências</h3>

                <form method="POST" action="{{ route('occurrences.store', $contract) }}" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                    @csrf
                    <div>
                        <x-input-label for="occurrence_type_id" value="Tipo" />
                        <select id="occurrence_type_id" name="occurrence_type_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Selecione...</option>
                            @foreach ($occurrenceTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="occurrence_date" value="Data" />
                        <x-text-input id="occurrence_date" name="occurrence_date" type="date" class="mt-1 block w-full" value="{{ now()->format('Y-m-d') }}" required />
                    </div>
                    <div>
                        <x-input-label for="deadline" value="Prazo (opcional)" />
                        <x-text-input id="deadline" name="deadline" type="date" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="attachment" value="Anexo (opcional)" />
                        <input id="attachment" name="attachment" type="file" class="mt-1 block w-full text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="description" value="Descrição" />
                        <textarea id="description" name="description" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required></textarea>
                    </div>
                    <div class="sm:col-span-2 text-right">
                        <x-primary-button type="submit">Registrar ocorrência</x-primary-button>
                    </div>
                </form>

                @if ($contract->occurrences->isEmpty())
                    <p class="text-sm text-gray-500">Nenhuma ocorrência registrada.</p>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($contract->occurrences as $occurrence)
                            <li class="py-3">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $occurrence->type->name }} &mdash; {{ $occurrence->occurrence_date->format('d/m/Y') }}</p>
                                        <p class="text-sm text-gray-600">{{ $occurrence->description }}</p>
                                        @if ($occurrence->deadline)
                                            <p class="text-xs text-gray-500">Prazo: {{ $occurrence->deadline->format('d/m/Y') }}</p>
                                        @endif
                                        @if ($occurrence->attachment_path)
                                            <a href="{{ route('occurrences.attachment', [$contract, $occurrence]) }}" class="text-xs text-indigo-600 hover:text-indigo-800">Baixar anexo</a>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('occurrences.destroy', [$contract, $occurrence]) }}" onsubmit="return confirm('Inativar esta ocorrência?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs">Inativar</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Checklist --}}
            <div x-show="tab === 'checklist'" class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Checklist do evento</h3>
                @include('contracts._checklist', ['isPublic' => false])
            </div>

            {{-- Fornecedores --}}
            <div x-show="tab === 'fornecedores'" class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Fornecedores</h3>
                @include('contracts._vendors', ['isPublic' => false])
            </div>

            {{-- Atividades --}}
            <div x-show="tab === 'atividades'" class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Histórico de atividades</h3>
                @include('contracts._activity-log', ['logs' => $activityLogs])
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if ($contract->trashed())
                    <form method="POST" action="{{ route('contracts.restore', $contract->id) }}">
                        @csrf
                        <button type="submit" class="text-green-600 hover:text-green-800 text-sm">Reativar contrato</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('contracts.destroy', $contract) }}" onsubmit="return confirm('Inativar este contrato?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Inativar contrato</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
