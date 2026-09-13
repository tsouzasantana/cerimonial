@php
    use App\Models\Installment;
    use App\Models\Vendor;

    $isPublic = $isPublic ?? false;
    $token = $token ?? null;
    $vendors = $contract->vendors;

    $storeUrl = $isPublic
        ? route('public.vendors.store', $token)
        : route('vendors.store', $contract);

    $updateUrl = fn ($vendor) => $isPublic
        ? route('public.vendors.update', ['token' => $token, 'vendor' => $vendor])
        : route('vendors.update', [$contract, $vendor]);

    $destroyUrl = fn ($vendor) => $isPublic
        ? route('public.vendors.destroy', ['token' => $token, 'vendor' => $vendor])
        : route('vendors.destroy', [$contract, $vendor]);

    $docFormAction = $isPublic ? route('public.documents.store', $token) : route('documents.store');

    $installmentStoreUrl = fn ($vendor) => $isPublic
        ? route('public.vendors.installments.store', ['token' => $token, 'vendor' => $vendor])
        : route('vendor-installments.store', [$contract, $vendor]);

    $installmentBatchUrl = fn ($vendor) => $isPublic
        ? route('public.vendors.installments.store-batch', ['token' => $token, 'vendor' => $vendor])
        : route('vendor-installments.store-batch', [$contract, $vendor]);

    $installmentUpdateUrl = fn ($vendor, $installment) => $isPublic
        ? route('public.vendors.installments.update', ['token' => $token, 'vendor' => $vendor, 'installment' => $installment])
        : route('vendor-installments.update', [$contract, $vendor, $installment]);

    $installmentDestroyUrl = fn ($vendor, $installment) => $isPublic
        ? route('public.vendors.installments.destroy', ['token' => $token, 'vendor' => $vendor, 'installment' => $installment])
        : route('vendor-installments.destroy', [$contract, $vendor, $installment]);
@endphp

@if ($vendors->isEmpty())
    <p class="text-sm text-gray-500 mb-6">Nenhum fornecedor cadastrado.</p>
@else
    <div class="space-y-4 mb-6">
        @foreach ($vendors as $vendor)
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex flex-wrap justify-between items-start gap-3">
                    <div>
                        <p class="font-medium text-gray-900">{{ $vendor->name }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $vendor->document ?: 'sem documento' }} &middot; {{ $vendor->vendorServiceType->name }}
                            @if ($vendor->contract_value !== null)
                                &middot; Valor do contrato: R$ {{ number_format($vendor->contract_value, 2, ',', '.') }}
                            @endif
                        </p>
                        @if ($vendor->updatedByLabel())
                            <p class="text-xs text-gray-400 mt-1">Última atualização: {{ $vendor->updatedByLabel() }} em {{ $vendor->updated_at->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" class="text-gray-600 hover:text-gray-900 text-sm" x-data x-on:click="$dispatch('open-modal', 'edit-vendor-{{ $vendor->id }}')">Editar</button>
                        <form method="POST" action="{{ $destroyUrl($vendor) }}" onsubmit="return confirm('Inativar este fornecedor?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Inativar</button>
                        </form>
                    </div>
                </div>

                <form method="POST" action="{{ $updateUrl($vendor) }}" class="mt-3 flex flex-wrap items-center gap-3">
                    @csrf
                    @method('PUT')
                    @unless ($isPublic)
                        <input type="hidden" name="name" value="{{ $vendor->name }}">
                        <input type="hidden" name="document" value="{{ $vendor->document }}">
                        <input type="hidden" name="vendor_service_type_id" value="{{ $vendor->vendor_service_type_id }}">
                    @endunless
                    <input type="hidden" name="notes" value="{{ $vendor->notes }}">

                    <select name="status" class="border-gray-300 rounded-md shadow-sm text-xs"
                            data-original-value="{{ $vendor->status }}"
                            onchange="{{ $isPublic ? "confirmAndSubmit(this, 'Atualizar o status deste fornecedor?')" : 'this.form.submit()' }}">
                        @foreach (Vendor::statusOptions() as $value => $label)
                            <option value="{{ $value }}" @selected($vendor->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="payment_status" class="border-gray-300 rounded-md shadow-sm text-xs"
                            data-original-value="{{ $vendor->payment_status }}"
                            onchange="{{ $isPublic ? "confirmAndSubmit(this, 'Atualizar a situação do pagamento deste fornecedor?')" : 'this.form.submit()' }}">
                        @foreach (Vendor::paymentStatusOptions() as $value => $label)
                            <option value="{{ $value }}" @selected($vendor->payment_status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>

                @if ($vendor->notes)
                    <p class="mt-2 text-sm text-gray-600">{{ $vendor->notes }}</p>
                @endif

                <div class="mt-3 border-t pt-3" x-data="{ showInstallments: false }">
                    <button type="button" class="text-sm text-brand-600 hover:text-brand-800" x-on:click="showInstallments = ! showInstallments">
                        Parcelas do fornecedor ({{ $vendor->installments->count() }})
                    </button>
                    <div x-show="showInstallments" x-cloak class="mt-3">
                        @if ($vendor->installments->isNotEmpty())
                            <div class="overflow-x-auto mb-3">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead>
                                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <th class="py-2 pr-3">#</th>
                                            <th class="py-2 pr-3">Vencimento</th>
                                            <th class="py-2 pr-3">Valor</th>
                                            <th class="py-2 pr-3">Status</th>
                                            <th class="py-2 pr-3">Pago em</th>
                                            <th class="py-2 pr-3"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($vendor->installments as $installment)
                                            <tr>
                                                <td class="py-2 pr-3">{{ $installment->number }}</td>
                                                <td class="py-2 pr-3 {{ $installment->isOverdue() ? 'text-red-600 font-medium' : '' }}">{{ $installment->due_date->format('d/m/Y') }}</td>
                                                <td class="py-2 pr-3">R$ {{ number_format($installment->amount, 2, ',', '.') }}</td>
                                                <td class="py-2 pr-3">
                                                    <form method="POST" action="{{ $installmentUpdateUrl($vendor, $installment) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="number" value="{{ $installment->number }}">
                                                        <input type="hidden" name="amount" value="{{ $installment->amount }}">
                                                        <input type="hidden" name="due_date" value="{{ $installment->due_date->format('Y-m-d') }}">
                                                        <select name="status" class="border-gray-300 rounded-md shadow-sm text-xs" onchange="this.form.submit()">
                                                            @foreach (Installment::statusOptions() as $value => $label)
                                                                <option value="{{ $value }}" @selected($installment->status === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </form>
                                                </td>
                                                <td class="py-2 pr-3">{{ optional($installment->paid_at)->format('d/m/Y') ?: '—' }}</td>
                                                <td class="py-2 pr-3 text-right">
                                                    <form method="POST" action="{{ $installmentDestroyUrl($vendor, $installment) }}" onsubmit="return confirm('Inativar esta parcela?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs">Inativar</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <form method="POST" action="{{ $installmentStoreUrl($vendor) }}" class="flex flex-wrap items-end gap-3">
                            @csrf
                            <div>
                                <x-input-label for="vendor-{{ $vendor->id }}-installment-number" value="Parcela nº" />
                                <x-text-input id="vendor-{{ $vendor->id }}-installment-number" name="number" type="number" min="1" class="mt-1 block w-20" required />
                            </div>
                            <div>
                                <x-input-label for="vendor-{{ $vendor->id }}-installment-amount" value="Valor" />
                                <x-currency-input name="amount" class="mt-1 block w-32" required />
                            </div>
                            <div>
                                <x-input-label for="vendor-{{ $vendor->id }}-installment-due_date" value="Vencimento" />
                                <x-text-input id="vendor-{{ $vendor->id }}-installment-due_date" name="due_date" type="date" class="mt-1 block w-40" required />
                            </div>
                            <x-secondary-button type="submit">Adicionar parcela</x-secondary-button>
                        </form>

                        <div class="mt-3" x-data="{ openBatch: false }">
                            <button type="button" class="text-sm text-brand-600 hover:text-brand-800" x-on:click="openBatch = ! openBatch">
                                Gerar parcelas em lote
                            </button>
                            <form x-show="openBatch" x-cloak method="POST" action="{{ $installmentBatchUrl($vendor) }}"
                                    class="mt-3 flex flex-wrap items-end gap-3" onsubmit="return confirm('Gerar as parcelas informadas?');">
                                @csrf
                                <div>
                                    <x-input-label for="vendor-{{ $vendor->id }}-total_amount" value="Valor total" />
                                    <x-currency-input name="total_amount" class="mt-1 block w-32" required />
                                </div>
                                <div>
                                    <x-input-label for="vendor-{{ $vendor->id }}-installment_count" value="Qtde de parcelas" />
                                    <x-text-input id="vendor-{{ $vendor->id }}-installment_count" name="installment_count" type="number" min="1" max="60" class="mt-1 block w-28" required />
                                </div>
                                <div>
                                    <x-input-label for="vendor-{{ $vendor->id }}-first_due_date" value="1º vencimento" />
                                    <x-text-input id="vendor-{{ $vendor->id }}-first_due_date" name="first_due_date" type="date" class="mt-1 block w-40" required />
                                </div>
                                <div>
                                    <x-input-label for="vendor-{{ $vendor->id }}-interval_months" value="Intervalo (meses)" />
                                    <x-text-input id="vendor-{{ $vendor->id }}-interval_months" name="interval_months" type="number" min="1" max="12" value="1" class="mt-1 block w-24" required />
                                </div>
                                <x-secondary-button type="submit">Gerar parcelas</x-secondary-button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="mt-3 border-t pt-3" x-data="{ showDocs: false }">
                    <button type="button" class="text-sm text-brand-600 hover:text-brand-800" x-on:click="showDocs = ! showDocs">
                        Documentos ({{ $vendor->documents->count() }})
                    </button>
                    <div x-show="showDocs" x-cloak class="mt-3">
                        @include('documents._form', [
                            'vendorId' => $vendor->id,
                            'documentTypes' => $documentTypes,
                            'formAction' => $docFormAction,
                        ])
                        @include('documents._list', ['documents' => $vendor->documents, 'isPublic' => $isPublic, 'token' => $token])
                    </div>
                </div>
            </div>

            <x-modal name="edit-vendor-{{ $vendor->id }}" maxWidth="md">
                <form method="POST" action="{{ $updateUrl($vendor) }}" class="p-6 space-y-4"
                        x-data="{ svc: '{{ old('vendor_service_type_id', $vendor->vendor_service_type_id) }}' }">
                    @csrf
                    @method('PUT')
                    <h3 class="text-lg font-medium text-gray-900">Editar fornecedor</h3>

                    @if ($isPublic)
                        <p class="text-sm text-gray-900">{{ $vendor->name }}</p>
                        <p class="text-xs text-gray-500">{{ $vendor->document ?: 'sem documento' }} &middot; {{ $vendor->vendorServiceType->name }}</p>
                    @else
                        <div>
                            <x-input-label for="vendor-{{ $vendor->id }}-name" value="Nome" />
                            <x-text-input id="vendor-{{ $vendor->id }}-name" name="name" type="text" class="mt-1 block w-full" value="{{ $vendor->name }}" required />
                        </div>
                        <div>
                            <x-input-label for="vendor-{{ $vendor->id }}-document" value="CPF/CNPJ" />
                            <x-text-input id="vendor-{{ $vendor->id }}-document" name="document" type="text" data-mask="document" inputmode="numeric" class="mt-1 block w-full" value="{{ $vendor->document }}" />
                        </div>
                        <div>
                            <x-input-label for="vendor-{{ $vendor->id }}-contract_value" value="Valor total do contrato" />
                            <x-currency-input id="vendor-{{ $vendor->id }}-contract_value" name="contract_value" :value="$vendor->contract_value" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="vendor-{{ $vendor->id }}-vendor_service_type_id" value="Tipo de serviço" />
                            <select id="vendor-{{ $vendor->id }}-vendor_service_type_id" name="vendor_service_type_id" x-model="svc" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Outro (digitar abaixo)</option>
                                @foreach ($vendorServiceTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                            <div x-show="svc === ''" x-cloak class="mt-2">
                                <x-input-label for="vendor-{{ $vendor->id }}-new_service_type" value="Novo tipo de serviço" class="sr-only" />
                                <x-text-input id="vendor-{{ $vendor->id }}-new_service_type" name="new_service_type" type="text" placeholder="Digite o novo tipo de serviço" class="block w-full" />
                            </div>
                        </div>
                    @endif

                    <div>
                        <x-input-label for="vendor-{{ $vendor->id }}-status" value="Status" />
                        <select id="vendor-{{ $vendor->id }}-status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            @foreach (Vendor::statusOptions() as $value => $label)
                                <option value="{{ $value }}" @selected($vendor->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="vendor-{{ $vendor->id }}-payment_status" value="Situação do pagamento" />
                        <select id="vendor-{{ $vendor->id }}-payment_status" name="payment_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            @foreach (Vendor::paymentStatusOptions() as $value => $label)
                                <option value="{{ $value }}" @selected($vendor->payment_status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="vendor-{{ $vendor->id }}-notes" value="Observações" />
                        <textarea id="vendor-{{ $vendor->id }}-notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm">{{ $vendor->notes }}</textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" class="text-sm text-gray-600 hover:text-gray-900" x-data x-on:click="$dispatch('close-modal', 'edit-vendor-{{ $vendor->id }}')">Cancelar</button>
                        <x-primary-button type="submit">Salvar</x-primary-button>
                    </div>
                </form>
            </x-modal>
        @endforeach
    </div>
@endif

<div class="border-t pt-4" x-data="{ svc: '' }">
    <h4 class="text-sm font-medium text-gray-900 mb-3">Adicionar fornecedor</h4>
    <form method="POST" action="{{ $storeUrl }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @csrf
        <div>
            <x-input-label for="new-vendor-name" value="Nome" />
            <x-text-input id="new-vendor-name" name="name" type="text" class="mt-1 block w-full" required />
        </div>
        <div>
            <x-input-label for="new-vendor-document" value="CPF/CNPJ" />
            <x-text-input id="new-vendor-document" name="document" type="text" data-mask="document" inputmode="numeric" class="mt-1 block w-full" />
        </div>
        <div>
            <x-input-label for="new-vendor-contract_value" value="Valor total do contrato" />
            <x-currency-input id="new-vendor-contract_value" name="contract_value" class="mt-1 block w-full" />
        </div>
        <div>
            <x-input-label for="new-vendor-vendor_service_type_id" value="Tipo de serviço" />
            <select id="new-vendor-vendor_service_type_id" name="vendor_service_type_id" x-model="svc" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">Outro (digitar abaixo)</option>
                @foreach ($vendorServiceTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
            <div x-show="svc === ''" x-cloak class="mt-2">
                <x-input-label for="new-vendor-new_service_type" value="Novo tipo de serviço" class="sr-only" />
                <x-text-input id="new-vendor-new_service_type" name="new_service_type" type="text" placeholder="Digite o novo tipo de serviço" class="block w-full" />
            </div>
        </div>
        <div>
            <x-input-label for="new-vendor-status" value="Status" />
            <select id="new-vendor-status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @foreach (Vendor::statusOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="new-vendor-payment_status" value="Situação do pagamento" />
            <select id="new-vendor-payment_status" name="payment_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @foreach (Vendor::paymentStatusOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="new-vendor-notes" value="Observações" />
            <textarea id="new-vendor-notes" name="notes" rows="2" class="mt-1 block w-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm"></textarea>
        </div>
        <div class="sm:col-span-2 text-right">
            <x-primary-button type="submit">Adicionar fornecedor</x-primary-button>
        </div>
    </form>
</div>
