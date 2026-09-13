<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo contrato de cerimonial</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('contracts.store') }}">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="sm:col-span-2">
                            <x-input-label for="client_id" value="Cliente" />
                            <select id="client_id" name="client_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                <option value="">Selecione...</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>{{ $client->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="event_date" value="Data do evento" />
                            <x-text-input id="event_date" name="event_date" type="date" class="mt-1 block w-full" :value="old('event_date')" required />
                            <x-input-error :messages="$errors->get('event_date')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="event_location" value="Local do evento" />
                            <x-text-input id="event_location" name="event_location" type="text" class="mt-1 block w-full" :value="old('event_location')" />
                            <x-input-error :messages="$errors->get('event_location')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="status" value="Status" />
                            <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'rascunho') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>

                    </div>

                    <div class="mt-6">
                        <x-input-label for="notes" value="Observações" />
                        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm">{{ old('notes') }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>

                    <p class="mt-4 text-sm text-gray-500">Os serviços/itens do contrato são adicionados na tela do contrato após a criação.</p>

                    <div class="mt-6 flex justify-end gap-3">
                        <a href="{{ route('contracts.index') }}" class="text-sm text-gray-600 hover:text-gray-900 self-center">Cancelar</a>
                        <x-primary-button>Criar contrato</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
