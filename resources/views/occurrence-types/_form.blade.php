@php $type = $type ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div>
        <x-input-label for="name" value="Nome do tipo de ocorrência" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $type?->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="contract_status" value="Status do contrato ao registrar (opcional)" />
        <select id="contract_status" name="contract_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="">Não alterar status</option>
            @foreach ($statusOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('contract_status', $type?->contract_status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('contract_status')" class="mt-2" />
    </div>
</div>
