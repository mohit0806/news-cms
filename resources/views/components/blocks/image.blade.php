<figure class="cms-block cms-block--image cms-block--align-{{ $node['attrs']['alignment'] ?? 'center' }}" data-media-id="{{ $node['attrs']['media_id'] ?? '' }}">
    <x-picture :media-id="$node['attrs']['media_id'] ?? null" :alt="$node['attrs']['alt'] ?? ''" />
    @if(!empty($node['attrs']['caption'] ?? null) || !empty($node['attrs']['credit'] ?? null))
        <figcaption>{{ $node['attrs']['caption'] ?? '' }}@if(!empty($node['attrs']['credit'] ?? null)) <span class="cms-credit">({{ $node['attrs']['credit'] }})</span>@endif</figcaption>
    @endif
</figure>
