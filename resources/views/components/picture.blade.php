{{-- Responsive media output (PRD §7.3; P2-T05.3). Data comes prepared from the controller: no queries or authorization here. Until T05.4 wires real uploads, pass a media id to render the pending hook. --}}
@props(['media' => null, 'mediaId' => null, 'crop' => 'card', 'alt' => '', 'eager' => false])

@php
    use App\Support\Media\MediaUrl;

    $srcsets = ['avif' => [], 'webp' => []];
    $fallback = null;
    $width = null;
    $height = null;

    if ($media instanceof \Spatie\MediaLibrary\MediaCollections\Models\Media) {
        foreach (config('media.responsive_widths', []) as $w) {
            $w = (int) $w;

            if ($media->hasGeneratedConversion("w-{$w}-avif")) {
                $srcsets['avif'][] = MediaUrl::to($media, "w-{$w}-avif")." {$w}w";
            }

            if ($media->hasGeneratedConversion("w-{$w}")) {
                $srcsets['webp'][] = MediaUrl::to($media, "w-{$w}")." {$w}w";
            }
        }

        $fallback = $media->hasGeneratedConversion($crop)
            ? MediaUrl::to($media, $crop)
            : MediaUrl::to($media);
        $width = $media->getCustomProperty('width');
        $height = $media->getCustomProperty('height');
        $alt = $alt !== '' ? $alt : (string) $media->getCustomProperty('alt', '');
    }
@endphp

@if ($media instanceof \Spatie\MediaLibrary\MediaCollections\Models\Media)
    <picture>
        @if ($srcsets['avif'] !== [])
            <source type="image/avif" srcset="{{ implode(', ', $srcsets['avif']) }}" />
        @endif
        @if ($srcsets['webp'] !== [])
            <source type="image/webp" srcset="{{ implode(', ', $srcsets['webp']) }}" />
        @endif
        <img src="{{ $fallback }}" alt="{{ $alt }}"
            @if ($width && $height) width="{{ $width }}" height="{{ $height }}" @endif
            @if ($eager) fetchpriority="high" @else loading="lazy" @endif />
    </picture>
@else
    <img data-media-id="{{ $mediaId }}" alt="{{ $alt }}" class="cms-picture-pending" />
@endif
