@php
    $max = max(1, $series->max('total'));
    $isCurrency = $isCurrency ?? false;
@endphp

<div class="flex items-end gap-3 h-40">
    @foreach ($series as $point)
        @php $heightPct = max(4, round($point['total'] / $max * 100)); @endphp
        <div class="flex-1 flex flex-col items-center justify-end h-full">
            <span class="text-xs text-gray-600 mb-1">
                {{ $isCurrency ? 'R$ '.number_format($point['total'], 0, ',', '.') : $point['total'] }}
            </span>
            <div class="w-full bg-brand-500 rounded-t" style="height: {{ $heightPct }}%"></div>
            <span class="text-xs text-gray-500 mt-1">{{ $point['label'] }}</span>
        </div>
    @endforeach
</div>
