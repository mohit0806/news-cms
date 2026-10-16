<ul class="cms-block cms-block--list">
    @foreach($node['content'] ?? [] as $item)
        <li>{!! $renderer->renderInline($item['content'] ?? []) !!} {{-- xss-ok: schema-validated renderer output; leaves escaped in BodyRenderer. --}}</li>
    @endforeach
</ul>
