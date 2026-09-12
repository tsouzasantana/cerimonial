@php $type = $type ?? null; @endphp

<div>
    <x-input-label for="name" value="Nome do tipo de serviço" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $type?->name)" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>
