<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar contrato</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('contracts.update', $contract) }}"
                        x-data="{
                            originalDate: '{{ $contract->event_date->format('Y-m-d') }}',
                            showDateModal: false,
                            decided: false,
                        }"
                        @submit="if (! decided && $refs.eventDate.value !== originalDate && {{ $hasChecklistTasks ? 'true' : 'false' }}) {
                            $event.preventDefault();
                            showDateModal = true;
                        }">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="shift_checklist_dates" x-ref="shiftField" value="0">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="sm:col-span-2">
                            <x-input-label for="client_id" value="Cliente" />
                            <select id="client_id" name="client_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}" @selected(old('client_id', $contract->client_id) == $client->id)>{{ $client->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="event_date" value="Data do evento" />
                            <x-text-input id="event_date" x-ref="eventDate" name="event_date" type="date" class="mt-1 block w-full" :value="old('event_date', $contract->event_date->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('event_date')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="event_location" value="Local do evento" />
                            <x-text-input id="event_location" name="event_location" type="text" class="mt-1 block w-full" :value="old('event_location', $contract->event_location)" />
                            <x-input-error :messages="$errors->get('event_location')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="status" value="Status" />
                            <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $contract->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="signed_at" value="Data de assinatura" />
                            <x-text-input id="signed_at" name="signed_at" type="date" class="mt-1 block w-full" :value="old('signed_at', optional($contract->signed_at)->format('Y-m-d'))" />
                            <x-input-error :messages="$errors->get('signed_at')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mt-6">
                        <x-input-label for="notes" value="Observações" />
                        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm">{{ old('notes', $contract->notes) }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <a href="{{ route('contracts.show', $contract) }}" class="text-sm text-gray-600 hover:text-gray-900 self-center">Cancelar</a>
                        <x-primary-button>Salvar</x-primary-button>
                    </div>

                    {{-- Confirmação de recálculo dos prazos do checklist --}}
                    <div x-show="showDateModal" x-cloak
                            class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 flex items-center justify-center">
                        <div class="fixed inset-0 bg-gray-500 opacity-75" x-on:click="showDateModal = false"></div>
                        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">A data do evento mudou</h3>
                            <p class="text-sm text-gray-600 mb-6">
                                Você alterou a data do evento. O que fazer com os prazos das tarefas do checklist
                                deste contrato? A diferença de dias será aplicada a todos os prazos igualmente.
                            </p>
                            <div class="flex flex-col gap-2">
                                <button type="button"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 bg-brand-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-500"
                                        x-on:click="$refs.shiftField.value = '1'; decided = true; showDateModal = false; $el.closest('form').submit();">
                                    Atualizar prazos conforme nova data
                                </button>
                                <button type="button"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                                        x-on:click="$refs.shiftField.value = '0'; decided = true; showDateModal = false; $el.closest('form').submit();">
                                    Manter os prazos originais
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
