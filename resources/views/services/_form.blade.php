@php $service = $service ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div>
        <x-input-label for="name" value="Nome do serviço" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $service?->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="code" value="Código" />
        <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code', $service?->code)" />
        <x-input-error :messages="$errors->get('code')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="price" value="Valor (R$)" />
        <x-text-input id="price" name="price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('price', $service?->price)" required />
        <x-input-error :messages="$errors->get('price')" class="mt-2" />
    </div>

    <div class="flex items-center mt-6">
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="hidden" name="active" value="0">
            <input type="checkbox" name="active" value="1" @checked(old('active', $service?->active ?? true))>
            Ativo (disponível para novos contratos)
        </label>
    </div>
</div>

<div class="mt-6">
    <x-input-label for="notes" value="Observações" />
    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notes', $service?->notes) }}</textarea>
    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
</div>
