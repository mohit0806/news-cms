@props(['name', 'label', 'value' => null, 'hint' => null])

<flux:field>
    <flux:label>{{ $label }}</flux:label>
    <div class="flex items-center gap-2">
        <flux:input :name="$name" :value="$value" class="font-mono" />
        {{ $regenerate ?? '' }}
    </div>
    @if ($hint !== null)
        <flux:description>{{ $hint }}</flux:description>
    @endif
    <flux:error :name="$name" />
</flux:field>
