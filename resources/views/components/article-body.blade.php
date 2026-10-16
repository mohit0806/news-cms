{{-- Article body choke point (P2-T04.1). Only BodyRenderer output (schema-validated, leaves escaped) may echo here. --}}
@props(['html' => ''])
<div class="cms-article-body">{!! $html !!} {{-- xss-ok: BodyRenderer output per contract above. --}}</div>
