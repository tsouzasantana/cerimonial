@php
    // $documentTypes, and one of $clientId / $contractId / $vendorId must be provided by the including view.
    $clientId = $clientId ?? null;
    $contractId = $contractId ?? null;
    $vendorId = $vendorId ?? null;
    $formAction = $formAction ?? route('documents.store');
    // Unique id prefix: this partial can be included more than once per page
    // (e.g. once per vendor in the fornecedores tab), so plain ids would collide.
    $idPrefix = 'doc-form-'.($vendorId ?? $contractId ?? $clientId ?? 'default');
@endphp

<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data"
        x-data="{ mode: 'file' }" class="mb-6 space-y-3">
    @csrf
    @if ($clientId)
        <input type="hidden" name="client_id" value="{{ $clientId }}">
    @endif
    @if ($contractId)
        <input type="hidden" name="contract_id" value="{{ $contractId }}">
    @endif
    @if ($vendorId)
        <input type="hidden" name="vendor_id" value="{{ $vendorId }}">
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
            <x-input-label for="{{ $idPrefix }}-title" value="Título" />
            <x-text-input id="{{ $idPrefix }}-title" name="title" type="text" class="mt-1 block w-full" required />
        </div>
        <div>
            <x-input-label for="{{ $idPrefix }}-document_type_id" value="Tipo de documento" />
            <select id="{{ $idPrefix }}-document_type_id" name="document_type_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                <option value="">Selecione...</option>
                @foreach ($documentTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <span class="block font-medium text-sm text-gray-700">Anexar como</span>
            <div class="mt-2 flex items-center gap-4 text-sm text-gray-700">
                <label class="flex items-center gap-1.5">
                    <input type="radio" name="mode" value="file" x-model="mode"> Arquivo
                </label>
                <label class="flex items-center gap-1.5">
                    <input type="radio" name="mode" value="url" x-model="mode"> Link na nuvem
                </label>
            </div>
        </div>
    </div>

    <div x-show="mode === 'file'">
        <x-input-label for="{{ $idPrefix }}-file" value="Arquivo" />
        <input id="{{ $idPrefix }}-file" name="file" type="file" class="mt-1 block w-full text-sm">
    </div>
    <div x-show="mode === 'url'" x-cloak>
        <x-input-label for="{{ $idPrefix }}-url" value="Link (Google Drive, Dropbox, etc.)" />
        <x-text-input id="{{ $idPrefix }}-url" name="url" type="url" placeholder="https://drive.google.com/..." class="mt-1 block w-full" />
    </div>

    <div>
        <x-primary-button type="submit" data-loading-text="Enviando...">Salvar documento</x-primary-button>
    </div>
</form>
