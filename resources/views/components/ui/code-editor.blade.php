@props(['name', 'label', 'value' => null, 'readonly' => false, 'hint' => null])

<flux:field>
    <flux:label>{{ $label }}</flux:label>
    <flux:textarea :name="$name" :value="$value" :readonly="$readonly" class="font-mono text-[13px]" rows="8" />
    @if ($hint !== null)
        <flux:description>{{ $hint }}</flux:description>
    @endif
    <flux:error :name="$name" />
</flux:field>
