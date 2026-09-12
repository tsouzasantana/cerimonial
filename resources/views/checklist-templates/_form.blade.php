@php $template = $template ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div>
        <x-input-label for="name" value="Nome da tarefa" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $template?->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="days_offset" value="Dias antes do evento" />
        <x-text-input id="days_offset" name="days_offset" type="number" class="mt-1 block w-full" :value="old('days_offset', $template?->days_offset)" required />
        <p class="mt-1 text-xs text-gray-500">Use 0 para o dia do evento e número negativo para dias <strong>depois</strong> do evento (ex.: -7 = uma semana depois).</p>
        <x-input-error :messages="$errors->get('days_offset')" class="mt-2" />
    </div>
</div>
