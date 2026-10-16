<blockquote class="cms-block cms-block--quote">
    {!! $renderer->renderInline($node['content'] ?? []) !!} {{-- xss-ok: schema-validated renderer output; leaves escaped in BodyRenderer. --}}
    @if(!empty($node['attrs']['attribution'] ?? null))
        <cite>{{ $node['attrs']['attribution'] }}</cite>
    @endif
</blockquote>
