@props(['name', 'id' => null, 'value' => null, 'required' => false])
@php
    $id = $id ?? $name;
    $rawValue = old($name, $value);
    $displayValue = ($rawValue !== null && $rawValue !== '')
        ? number_format((float) $rawValue, 2, ',', '.')
        : '';
@endphp
<div class="relative">
    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm">R$</span>
    <input type="text" inputmode="decimal" data-currency-input data-target="{{ $id }}"
            id="{{ $id }}_display" value="{{ $displayValue }}" @if ($required) required @endif
            {{ $attributes->merge(['class' => 'border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm pl-9']) }}>
    <input type="hidden" name="{{ $name }}" id="{{ $id }}" value="{{ $rawValue }}">
</div>
