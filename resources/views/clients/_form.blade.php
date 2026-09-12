@php $client = $client ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div>
        <x-input-label for="name" value="Nome completo" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $client?->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="document" value="CPF/CNPJ" />
        <x-text-input id="document" name="document" type="text" class="mt-1 block w-full" :value="old('document', $client?->document)" />
        <x-input-error :messages="$errors->get('document')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="rg" value="RG" />
        <x-text-input id="rg" name="rg" type="text" class="mt-1 block w-full" :value="old('rg', $client?->rg)" />
        <x-input-error :messages="$errors->get('rg')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="birth_date" value="Data de nascimento" />
        <x-text-input id="birth_date" name="birth_date" type="date" class="mt-1 block w-full" :value="old('birth_date', optional($client?->birth_date)->format('Y-m-d'))" />
        <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email" value="E-mail" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $client?->email)" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="phone" value="Telefone" />
        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $client?->phone)" />
        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="phone_alt" value="Telefone alternativo" />
        <x-text-input id="phone_alt" name="phone_alt" type="text" class="mt-1 block w-full" :value="old('phone_alt', $client?->phone_alt)" />
        <x-input-error :messages="$errors->get('phone_alt')" class="mt-2" />
    </div>
</div>

<h3 class="mt-6 text-sm font-semibold text-gray-700 uppercase">Endereço</h3>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mt-2">
    <div class="sm:col-span-2">
        <x-input-label for="address_street" value="Rua" />
        <x-text-input id="address_street" name="address_street" type="text" class="mt-1 block w-full" :value="old('address_street', $client?->address_street)" />
        <x-input-error :messages="$errors->get('address_street')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="address_number" value="Número" />
        <x-text-input id="address_number" name="address_number" type="text" class="mt-1 block w-full" :value="old('address_number', $client?->address_number)" />
        <x-input-error :messages="$errors->get('address_number')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="address_complement" value="Complemento" />
        <x-text-input id="address_complement" name="address_complement" type="text" class="mt-1 block w-full" :value="old('address_complement', $client?->address_complement)" />
        <x-input-error :messages="$errors->get('address_complement')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="address_district" value="Bairro" />
        <x-text-input id="address_district" name="address_district" type="text" class="mt-1 block w-full" :value="old('address_district', $client?->address_district)" />
        <x-input-error :messages="$errors->get('address_district')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="address_city" value="Cidade" />
        <x-text-input id="address_city" name="address_city" type="text" class="mt-1 block w-full" :value="old('address_city', $client?->address_city)" />
        <x-input-error :messages="$errors->get('address_city')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="address_state" value="UF" />
        <x-text-input id="address_state" name="address_state" type="text" maxlength="2" class="mt-1 block w-full" :value="old('address_state', $client?->address_state)" />
        <x-input-error :messages="$errors->get('address_state')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="address_zipcode" value="CEP" />
        <x-text-input id="address_zipcode" name="address_zipcode" type="text" class="mt-1 block w-full" :value="old('address_zipcode', $client?->address_zipcode)" />
        <x-input-error :messages="$errors->get('address_zipcode')" class="mt-2" />
    </div>
</div>

<div class="mt-6">
    <x-input-label for="notes" value="Observações" />
    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm">{{ old('notes', $client?->notes) }}</textarea>
    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
</div>
