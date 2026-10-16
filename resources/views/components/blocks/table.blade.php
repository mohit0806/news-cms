<div class="cms-block cms-block--table">
<table>
    @foreach($node['content'] ?? [] as $row)
        <tr>
            @foreach($row['content'] ?? [] as $cell)
                <td>{!! $renderer->renderInline($cell['content'] ?? []) !!} {{-- xss-ok: schema-validated renderer output; leaves escaped in BodyRenderer. --}}</td>
            @endforeach
        </tr>
    @endforeach
</table>
</div>
