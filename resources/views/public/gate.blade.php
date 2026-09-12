<x-guest-layout>
    <div class="text-center mb-4">
        <h1 class="text-lg font-semibold text-gray-900">{{ config('cerimonial.company_name') }}</h1>
        <p class="text-sm text-gray-500 mt-1">Acesso do cliente ao contrato</p>
    </div>

    <p class="text-sm text-gray-600 mb-4">
        Para acessar o checklist e os documentos do seu evento, confirme o CPF cadastrado no seu contrato.
    </p>

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700 border border-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('public.verify', $token) }}">
        @csrf
        <div>
            <x-input-label for="document" value="CPF" />
            <x-text-input id="document" name="document" type="text" class="mt-1 block w-full" placeholder="000.000.000-00" required autofocus />
        </div>

        <div class="mt-4">
            <x-primary-button class="w-full justify-center">Acessar</x-primary-button>
        </div>
    </form>
</x-guest-layout>
