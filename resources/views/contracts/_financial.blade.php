@php
    use App\Models\Installment;

    $isPublic = $isPublic ?? false;
    $token = $token ?? null;
    $vendors = $contract->vendors;

    $storeUrl = $isPublic ? route('public.financial-entries.store', $token) : route('financial-entries.store', $contract);

    $updateUrl = fn ($entry) => $isPublic
        ? route('public.financial-entries.update', ['token' => $token, 'financialEntry' => $entry])
        : route('financial-entries.update', [$contract, $entry]);

    $destroyUrl = fn ($entry) => $isPublic
        ? route('public.financial-entries.destroy', ['token' => $token, 'financialEntry' => $entry])
        : route('financial-entries.destroy', [$contract, $entry]);

    $companyName = config('cerimonial.company_name', 'Assessoria');

    $rows = $contract->financialEntries
        ->map(fn ($entry) => [
            'source' => 'entry',
            'model' => $entry,
            'description' => $entry->description,
            'origin' => $entry->vendor?->name ?? 'Gasto geral',
            'due_date' => $entry->due_date,
            'amount' => $entry->amount,
            'status' => $entry->status,
            'paid_at' => $entry->paid_at,
        ])
        ->concat(
            $vendors->flatMap(fn ($vendor) => $vendor->installments->map(fn ($installment) => [
                'source' => 'vendor_installment',
                'model' => $installment,
                'description' => "Parcela nº {$installment->number}",
                'origin' => $vendor->name,
                'due_date' => $installment->due_date,
                'amount' => $installment->amount,
                'status' => $installment->status,
                'paid_at' => $installment->paid_at,
            ]))
        )
        ->concat(
            $contract->installments->map(fn ($installment) => [
                'source' => 'contract_installment',
                'model' => $installment,
                'description' => "Parcela nº {$installment->number}",
                'origin' => $companyName,
                'due_date' => $installment->due_date,
                'amount' => $installment->amount,
                'status' => $installment->status,
                'paid_at' => $installment->paid_at,
            ])
        )
        ->sortBy(fn ($row) => $row['due_date']?->format('Y-m-d') ?? '9999-99-99')
        ->values();

    $total = $rows->sum('amount');
    $totalPago = $rows->where('status', Installment::STATUS_PAGO)->sum('amount');
    $totalPendente = $total - $totalPago;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="border border-gray-200 rounded-lg p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wider">Total de gastos</p>
        <p class="mt-1 text-lg font-semibold text-gray-900">R$ {{ number_format($total, 2, ',', '.') }}</p>
    </div>
    <div class="border border-gray-200 rounded-lg p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wider">Já pago</p>
        <p class="mt-1 text-lg font-semibold text-green-700">R$ {{ number_format($totalPago, 2, ',', '.') }}</p>
    </div>
    <div class="border border-gray-200 rounded-lg p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wider">Em aberto</p>
        <p class="mt-1 text-lg font-semibold text-yellow-700">R$ {{ number_format($totalPendente, 2, ',', '.') }}</p>
    </div>
</div>

<div class="overflow-x-auto mb-6">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead>
            <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <th class="py-2 pr-3">Descrição</th>
                <th class="py-2 pr-3">Fornecedor / Origem</th>
                <th class="py-2 pr-3">Vencimento</th>
                <th class="py-2 pr-3">Valor</th>
                <th class="py-2 pr-3">Status</th>
                <th class="py-2 pr-3">Pago em</th>
                <th class="py-2 pr-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($rows as $row)
                <tr>
                    <td class="py-2 pr-3">{{ $row['description'] }}</td>
                    <td class="py-2 pr-3">{{ $row['origin'] }}</td>
                    <td class="py-2 pr-3">{{ $row['due_date']?->format('d/m/Y') ?? '—' }}</td>
                    <td class="py-2 pr-3">R$ {{ number_format($row['amount'], 2, ',', '.') }}</td>
                    <td class="py-2 pr-3">
                        <x-status-badge :variant="$row['model']->statusBadgeVariant()">{{ Installment::statusOptions()[$row['status']] ?? $row['status'] }}</x-status-badge>
                    </td>
                    <td class="py-2 pr-3">{{ optional($row['paid_at'])->format('d/m/Y') ?: '—' }}</td>
                    <td class="py-2 pr-3 text-right">
                        @if ($row['source'] === 'entry')
                            <button type="button" class="text-gray-600 hover:text-gray-900 text-xs" x-data x-on:click="$dispatch('open-modal', 'edit-financial-entry-{{ $row['model']->id }}')">Editar</button>
                            <form method="POST" action="{{ $destroyUrl($row['model']) }}" onsubmit="return confirm('Inativar este lançamento?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs ml-2">Inativar</button>
                            </form>
                        @elseif ($row['source'] === 'vendor_installment')
                            <span class="text-xs text-gray-400">Ver na aba Fornecedores</span>
                        @else
                            <span class="text-xs text-gray-400">Ver na aba Serviços e pagamentos</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-3 text-center text-gray-500">Nenhum lançamento financeiro.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@foreach ($contract->financialEntries as $entry)
    <x-modal name="edit-financial-entry-{{ $entry->id }}" maxWidth="md">
        <form method="POST" action="{{ $updateUrl($entry) }}" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <h3 class="text-lg font-medium text-gray-900">Editar lançamento</h3>

            <div>
                <x-input-label for="financial-entry-{{ $entry->id }}-description" value="Descrição" />
                <x-text-input id="financial-entry-{{ $entry->id }}-description" name="description" type="text" class="mt-1 block w-full" value="{{ $entry->description }}" required />
            </div>
            <div>
                <x-input-label for="financial-entry-{{ $entry->id }}-vendor_id" value="Fornecedor (opcional)" />
                <select id="financial-entry-{{ $entry->id }}-vendor_id" name="vendor_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Nenhum</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected($entry->vendor_id === $vendor->id)>{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="financial-entry-{{ $entry->id }}-amount" value="Valor" />
                <x-currency-input id="financial-entry-{{ $entry->id }}-amount" name="amount" :value="$entry->amount" class="mt-1 block w-full" required />
            </div>
            <div>
                <x-input-label for="financial-entry-{{ $entry->id }}-due_date" value="Vencimento" />
                <x-text-input id="financial-entry-{{ $entry->id }}-due_date" name="due_date" type="date" class="mt-1 block w-full" :value="optional($entry->due_date)->format('Y-m-d')" />
            </div>
            <div>
                <x-input-label for="financial-entry-{{ $entry->id }}-payment_method" value="Forma de pagamento" />
                <select id="financial-entry-{{ $entry->id }}-payment_method" name="payment_method" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">—</option>
                    @foreach (Installment::paymentMethodOptions() as $value => $label)
                        <option value="{{ $value }}" @selected($entry->payment_method === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="financial-entry-{{ $entry->id }}-status" value="Status" />
                <select id="financial-entry-{{ $entry->id }}-status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @foreach (Installment::statusOptions() as $value => $label)
                        <option value="{{ $value }}" @selected($entry->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="financial-entry-{{ $entry->id }}-notes" value="Observações" />
                <textarea id="financial-entry-{{ $entry->id }}-notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm">{{ $entry->notes }}</textarea>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" class="text-sm text-gray-600 hover:text-gray-900" x-data x-on:click="$dispatch('close-modal', 'edit-financial-entry-{{ $entry->id }}')">Cancelar</button>
                <x-primary-button type="submit">Salvar</x-primary-button>
            </div>
        </form>
    </x-modal>
@endforeach

<div class="border-t pt-4">
    <h4 class="text-sm font-medium text-gray-900 mb-3">Adicionar lançamento</h4>
    <form method="POST" action="{{ $storeUrl }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @csrf
        <div>
            <x-input-label for="new-entry-description" value="Descrição" />
            <x-text-input id="new-entry-description" name="description" type="text" class="mt-1 block w-full" required />
        </div>
        <div>
            <x-input-label for="new-entry-vendor_id" value="Fornecedor (opcional)" />
            <select id="new-entry-vendor_id" name="vendor_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">Nenhum</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="new-entry-amount" value="Valor" />
            <x-currency-input id="new-entry-amount" name="amount" class="mt-1 block w-full" required />
        </div>
        <div>
            <x-input-label for="new-entry-due_date" value="Vencimento" />
            <x-text-input id="new-entry-due_date" name="due_date" type="date" class="mt-1 block w-full" />
        </div>
        <div>
            <x-input-label for="new-entry-payment_method" value="Forma de pagamento" />
            <select id="new-entry-payment_method" name="payment_method" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">—</option>
                @foreach (Installment::paymentMethodOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="new-entry-status" value="Status" />
            <select id="new-entry-status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @foreach (Installment::statusOptions() as $value => $label)
                    <option value="{{ $value }}" @selected($value === Installment::STATUS_PENDENTE)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="new-entry-notes" value="Observações" />
            <textarea id="new-entry-notes" name="notes" rows="2" class="mt-1 block w-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm"></textarea>
        </div>
        <div class="sm:col-span-2 text-right">
            <x-primary-button type="submit">Adicionar lançamento</x-primary-button>
        </div>
    </form>
</div>
