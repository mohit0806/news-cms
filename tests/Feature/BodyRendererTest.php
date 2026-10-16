<?php

declare(strict_types=1);

use App\Support\Editor\BlockSchema;
use App\Support\Editor\BodyRenderer;

function sampleDoc(array ...$nodes): array
{
    return ['schema' => 1, 'type' => 'doc', 'content' => $nodes];
}

function samplePara(string $text, array $marks = []): array
{
    $node = ['type' => 'text', 'text' => $text];

    if ($marks !== []) {
        $node['marks'] = $marks;
    }

    return ['type' => 'paragraph', 'content' => [$node]];
}

test('renders paragraphs, headings, and lists with escaped text', function () {
    $html = (new BodyRenderer)->render(sampleDoc(
        samplePara('Hello <script>alert(1)</script>'),
        ['type' => 'heading', 'attrs' => ['level' => '2'], 'content' => [['type' => 'text', 'text' => 'Title & more']]],
        ['type' => 'bullet_list', 'content' => [
            ['type' => 'list_item', 'content' => [['type' => 'text', 'text' => 'one']]],
            ['type' => 'list_item', 'content' => [['type' => 'text', 'text' => 'two']]],
        ]],
    ))->html;

    expect($html)->toContain('<p class="cms-block cms-block--paragraph">Hello &lt;script&gt;')
        ->not->toContain('<script>alert')
        ->toContain('<h2 class="cms-block cms-block--heading">')
        ->toContain('Title &amp; more')
        ->toContain('<ul class="cms-block cms-block--list">')
        ->toContain('<li>one');
});

test('renders links safely with rel and external target', function () {
    $html = (new BodyRenderer)->render(sampleDoc(
        samplePara('click', [['type' => 'link', 'attrs' => ['href' => 'https://example.com/x']]]),
        samplePara('bad', [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]]),
    ))->html;

    expect($html)->toContain('<a href="https://example.com/x" rel="noopener noreferrer" target="_blank">click</a>')
        ->toContain('<a href="#" rel="noopener noreferrer">bad</a>')
        ->not->toContain('javascript:');
});

test('rejects unknown block types', function () {
    expect(fn () => (new BodyRenderer)->render(sampleDoc(['type' => 'evil_widget'])))
        ->toThrow(InvalidArgumentException::class, 'Unknown block type: evil_widget.');
});

test('rejects unknown attributes and bad enum values', function () {
    expect(fn () => (new BodyRenderer)->render(sampleDoc(
        ['type' => 'heading', 'attrs' => ['level' => '2', 'onclick' => 'x'], 'content' => []]
    )))->toThrow(InvalidArgumentException::class, 'rejects attribute: onclick');

    expect(fn () => (new BodyRenderer)->render(sampleDoc(
        ['type' => 'heading', 'attrs' => ['level' => '9'], 'content' => []]
    )))->toThrow(InvalidArgumentException::class, 'rejects value');
});

test('rejects wrong schema versions and malformed docs', function () {
    $renderer = new BodyRenderer;

    expect(fn () => $renderer->render(['schema' => 2, 'type' => 'doc', 'content' => []]))
        ->toThrow(InvalidArgumentException::class, 'schema version');

    expect(fn () => $renderer->render(['type' => 'doc']))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $renderer->render('not json{'))
        ->toThrow(InvalidArgumentException::class, 'not valid JSON');
});

test('rejects bodies over 2 MB', function () {
    expect(fn () => (new BodyRenderer)->render(str_repeat('x', BlockSchema::MAX_BODY_BYTES + 1)))
        ->toThrow(InvalidArgumentException::class, '2 MB');
});

test('extracts text, reading time, and media references', function () {
    $words = implode(' ', array_fill(0, 450, 'word'));

    $rendered = (new BodyRenderer)->render(sampleDoc(
        samplePara($words),
        ['type' => 'image', 'attrs' => ['media_id' => 7, 'alt' => 'A photo']],
        ['type' => 'image', 'attrs' => ['media_id' => 7, 'alt' => 'Same photo again']],
        ['type' => 'embed', 'attrs' => ['url' => 'https://www.youtube.com/watch?v=abc']],
    ));

    expect($rendered->readingTimeMin)->toBe(3)
        ->and($rendered->text)->toContain('word word')
        ->and($rendered->mediaIds)->toBe([7])
        ->and($rendered->embeds)->toBe(['https://www.youtube.com/watch?v=abc'])
        ->and($rendered->html)->toContain('data-media-id="7"');
});

test('minimum reading time is one minute', function () {
    $rendered = (new BodyRenderer)->render(sampleDoc(samplePara('Hi')));

    expect($rendered->readingTimeMin)->toBe(1);
});

test('degraded blocks render placeholders, raw html stays escaped', function () {
    $html = (new BodyRenderer)->render(sampleDoc(
        ['type' => 'gallery', 'attrs' => ['gallery_id' => 3]],
        ['type' => 'poll', 'attrs' => ['poll_id' => 5]],
        ['type' => 'raw_html', 'attrs' => ['html' => '<script>alert(1)</script><p>kept?</p>']],
    ))->html;

    expect($html)->toContain('data-gallery-id="3"')
        ->toContain('data-poll-id="5"')
        ->toContain('&lt;script&gt;')
        ->not->toContain('<script>alert');
});

test('article body component echoes renderer output once', function () {
    $html = (new BodyRenderer)->render(sampleDoc(samplePara('Shown')))->html;

    $view = Blade::render('<x-article-body :html="$html" />', ['html' => $html]);

    expect($view)->toContain('<div class="cms-article-body">')
        ->toContain('<p class="cms-block cms-block--paragraph">')
        ->toContain('Shown');
});
