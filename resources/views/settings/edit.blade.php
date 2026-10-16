<x-layouts::app :title="__(':group settings', ['group' => $label])">
    <x-ui.breadcrumbs :items="[['label' => 'Settings', 'url' => route('settings.index')], ['label' => $label]]" />

    <x-ui.page-header :title="__(':group settings', ['group' => $label])" />

    <form method="POST" action="{{ route('settings.update', $group) }}" class="max-w-2xl space-y-6">
        @csrf
        @method('PUT')

        <x-ui.form-errors :messages="$errors->all()" />

        @foreach ($fields as $field)
            @if ($field['type'] === 'toggle')
                <flux:switch
                    name="{{ $field['property'] }}"
                    :label="__($field['label'])"
                    :checked="old($field['property'], $values[$field['property']] ?? false)"
                />
            @elseif ($field['type'] === 'number')
                <flux:input
                    name="{{ $field['property'] }}"
                    :label="__($field['label'])"
                    type="number"
                    :value="old($field['property'], $values[$field['property']] ?? '')"
                />
                <flux:error :name="$field['property']" />
            @elseif ($field['type'] === 'language')
                <flux:fieldset>
                    <flux:legend>{{ __($field['label']) }}</flux:legend>
                    @foreach ($languages as $language)
                        <flux:input
                            name="{{ $field['property'] }}[{{ $language }}]"
                            :label="$language"
                            :value="old($field['property'].'.'.$language, ($values[$field['property']] ?? [])[$language] ?? '')"
                        />
                    @endforeach
                    <flux:error :name="$field['property']" />
                </flux:fieldset>
            @elseif ($field['type'] === 'profiles-json')
                <flux:textarea
                    name="{{ $field['property'] }}"
                    :label="__($field['label'])"
                    :value="old($field['property'], json_encode($values[$field['property']] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))"
                    rows="6"
                    class="font-mono text-[13px]"
                />
                <flux:error :name="$field['property']" />
            @elseif ($field['type'] === 'languages-csv')
                <flux:input
                    name="{{ $field['property'] }}"
                    :label="__($field['label'])"
                    :value="old($field['property'], implode(',', $values[$field['property']] ?? []))"
                />
                <flux:error :name="$field['property']" />
            @else
                <flux:input
                    name="{{ $field['property'] }}"
                    :label="__($field['label'])"
                    :value="old($field['property'], $values[$field['property']] ?? '')"
                />
                <flux:error :name="$field['property']" />
            @endif
        @endforeach

        <div class="flex items-center gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save settings') }}</flux:button>
        </div>
    </form>
</x-layouts::app>
