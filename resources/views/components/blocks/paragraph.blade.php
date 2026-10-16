<p class="cms-block cms-block--paragraph">{!! $renderer->renderInline($node['content'] ?? []) !!} {{-- xss-ok: schema-validated renderer output; leaves escaped in BodyRenderer. --}}</p>
