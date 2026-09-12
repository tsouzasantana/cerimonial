@if ($log->changes)
    <ul class="list-disc list-inside">
        @foreach ($log->changes as $field => $change)
            <li>
                @if (is_array($change) && array_key_exists('old', $change) && array_key_exists('new', $change))
                    {{ $field }}:
                    <span class="line-through text-gray-400">{{ is_scalar($change['old']) ? $change['old'] : json_encode($change['old']) }}</span>
                    &rarr;
                    {{ is_scalar($change['new']) ? $change['new'] : json_encode($change['new']) }}
                @else
                    {{ $field }}: {{ is_scalar($change) ? $change : json_encode($change) }}
                @endif
            </li>
        @endforeach
    </ul>

    @if ($log->isRevertible())
        <form method="POST" action="{{ route('audit-logs.revert', $log) }}" class="mt-1"
                onsubmit="return confirm('Reverter esta alteração para os valores anteriores?');">
            @csrf
            <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Reverter</button>
        </form>
    @endif
@else
    —
@endif
