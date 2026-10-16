@php($level = in_array($node['attrs']['level'] ?? null, ['2', '3', '4'], true) ? $node['attrs']['level'] : '2')
<h{{ $level }} class="cms-block cms-block--heading">{!! $renderer->renderInline($node['content'] ?? []) !!} {{-- xss-ok: schema-validated renderer output; leaves escaped in BodyRenderer. --}}</h{{ $level }}>
