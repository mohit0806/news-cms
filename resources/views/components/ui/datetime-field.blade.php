@props(['name', 'label', 'value' => null, 'hint' => null])

<flux:field>
    <flux:label>{{ $label }}</flux:label>
    <flux:input type="datetime-local" :name="$name" :value="$value" />
    <flux:description>{{ $hint ?? __('Shown in :tz.', ['tz' => config('app.site_timezone', 'Asia/Kolkata')]) }}</flux:description>
    <flux:error :name="$name" />
</flux:field>
