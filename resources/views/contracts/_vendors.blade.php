@php
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

                    <select name="status" class="border-gray-300 rounded-md shadow-sm text-xs" onchange="this.form.submit()">
                        @foreach (Vendor::statusOptions() as $value => $label)
                            <option value="{{ $value }}" @selected($vendor->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="payment_status" class="border-gray-300 rounded-md shadow-sm text-xs" onchange="this.form.submit()">
                        @foreach (Vendor::paymentStatusOptions() as $value => $label)
                            <option value="{{ $value }}" @selected($vendor->payment_status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>

                @if ($vendor->notes)
                    <p class="mt-2 text-sm text-gray-600">{{ $vendor->notes }}</p>
                @endif

                <div class="mt-3 border-t pt-3" x-data="{ showDocs: false }">
                    <button type="button" class="text-sm text-indigo-600 hover:text-indigo-800" x-on:click="showDocs = ! showDocs">
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
                            <x-input-label value="Nome" />
                            <x-text-input name="name" type="text" class="mt-1 block w-full" value="{{ $vendor->name }}" required />
                        </div>
                        <div>
                            <x-input-label value="CPF/CNPJ" />
                            <x-text-input name="document" type="text" class="mt-1 block w-full" value="{{ $vendor->document }}" />
                        </div>
                        <div>
                            <x-input-label value="Tipo de serviço" />
                            <select name="vendor_service_type_id" x-model="svc" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Outro (digitar abaixo)</option>
                                @foreach ($vendorServiceTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                            <div x-show="svc === ''" x-cloak class="mt-2">
                                <x-text-input name="new_service_type" type="text" placeholder="Digite o novo tipo de serviço" class="block w-full" />
                            </div>
                        </div>
                    @endif

                    <div>
                        <x-input-label value="Status" />
                        <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            @foreach (Vendor::statusOptions() as $value => $label)
                                <option value="{{ $value }}" @selected($vendor->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label value="Situação do pagamento" />
                        <select name="payment_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            @foreach (Vendor::paymentStatusOptions() as $value => $label)
                                <option value="{{ $value }}" @selected($vendor->payment_status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label value="Observações" />
                        <textarea name="notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ $vendor->notes }}</textarea>
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
            <x-input-label value="Nome" />
            <x-text-input name="name" type="text" class="mt-1 block w-full" required />
        </div>
        <div>
            <x-input-label value="CPF/CNPJ" />
            <x-text-input name="document" type="text" class="mt-1 block w-full" />
        </div>
        <div>
            <x-input-label value="Tipo de serviço" />
            <select name="vendor_service_type_id" x-model="svc" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">Outro (digitar abaixo)</option>
                @foreach ($vendorServiceTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
            <div x-show="svc === ''" x-cloak class="mt-2">
                <x-text-input name="new_service_type" type="text" placeholder="Digite o novo tipo de serviço" class="block w-full" />
            </div>
        </div>
        <div>
            <x-input-label value="Status" />
            <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @foreach (Vendor::statusOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label value="Situação do pagamento" />
            <select name="payment_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @foreach (Vendor::paymentStatusOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2">
            <x-input-label value="Observações" />
            <textarea name="notes" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
        </div>
        <div class="sm:col-span-2 text-right">
            <x-primary-button type="submit">Adicionar fornecedor</x-primary-button>
        </div>
    </form>
</div>
