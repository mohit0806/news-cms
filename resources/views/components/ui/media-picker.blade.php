{{--
    Media-picker boundary (P1-T06.5): trigger + preview composition only.
    No media-library behavior (P2-T05).

    P2 selection contract: the trigger dispatches a `media-picker:open` browser
    event; the picker answers with a `media-picker:selected` Livewire event
    carrying `{id, thumb_url, alt}`; the caller renders the selection through
    the preview slot. Upload progress surfaces through the same preview slot.
--}}
@props(['label', 'hint' => null])

<flux:field>
    <flux:label>{{ $label }}</flux:label>
    <div>{{ $preview ?? '' }}</div>
    <div class="mt-2">{{ $trigger ?? '' }}</div>
    @if ($hint !== null)
        <flux:description>{{ $hint }}</flux:description>
    @endif
</flux:field>
