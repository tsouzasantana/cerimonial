@php
    use App\Models\Contract;

    $weekdayLabels = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Calendário de eventos</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <a href="{{ route('calendar.index', ['month' => $previousMonth]) }}" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; Mês anterior</a>
                    <h3 class="text-lg font-medium text-gray-900">{{ $month->translatedFormat('F \d\e Y') }}</h3>
                    <a href="{{ route('calendar.index', ['month' => $nextMonth]) }}" class="text-sm text-indigo-600 hover:text-indigo-800">Próximo mês &rarr;</a>
                </div>

                <div class="grid grid-cols-7 gap-px bg-gray-200 text-xs font-medium text-gray-500 uppercase tracking-wide mb-px">
                    @foreach ($weekdayLabels as $label)
                        <div class="bg-gray-50 py-2 text-center">{{ $label }}</div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7 gap-px bg-gray-200">
                    @foreach ($weeks as $week)
                        @foreach ($week as $day)
                            <div class="bg-white min-h-28 p-1.5 {{ $day['inMonth'] ? '' : 'bg-gray-50' }}">
                                <p class="text-xs mb-1 {{ $day['isToday'] ? 'inline-flex items-center justify-center w-5 h-5 rounded-full bg-indigo-600 text-white font-semibold' : ($day['inMonth'] ? 'text-gray-700' : 'text-gray-400') }}">
                                    {{ $day['date']->day }}
                                </p>
                                <div class="space-y-1">
                                    @foreach ($day['contracts'] as $contract)
                                        <a href="{{ route('contracts.show', $contract) }}"
                                           class="block truncate text-xs rounded px-1.5 py-0.5
                                                {{ $contract->status === Contract::STATUS_ATIVO ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-600' }}"
                                           title="{{ $contract->client->name }} — {{ Contract::statusOptions()[$contract->status] ?? $contract->status }}">
                                            {{ $contract->client->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
