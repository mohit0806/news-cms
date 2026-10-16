<?php

declare(strict_types=1);

test('confirm dialog renders title, body, and both actions', function () {
    $html = (string) $this->blade(
        '<x-ui.confirm-dialog name="del" title="Delete?"><x-slot:body><p>Gone forever.</p></x-slot:body><x-slot:confirm><button>Yes</button></x-slot:confirm></x-ui.confirm-dialog>'
    );

    expect($html)->toContain('Delete?')
        ->toContain('Gone forever.')
        ->toContain('>Yes</button>')
        ->toContain('Cancel');
});

test('language tabs mark the active language and link the rest', function () {
    $html = (string) $this->blade(
        '<x-ui.language-tabs :items="$items" />',
        ['items' => [
            ['label' => 'English', 'url' => '/en', 'active' => true],
            ['label' => 'Hindi', 'url' => '/hi'],
        ]]
    );

    expect($html)->toContain('aria-current="true"')
        ->toContain('/hi')
        ->toContain('Hindi');
});

test('language tabs render nothing for a single language', function () {
    $html = (string) $this->blade(
        '<x-ui.language-tabs :items="$items" />',
        ['items' => [['label' => 'English', 'url' => '/en', 'active' => true]]]
    );

    expect($html)->not->toContain('<nav');
});

test('datetime field shows the site timezone hint', function () {
    $html = (string) $this->blade('<x-ui.datetime-field name="at" label="Publish at" />');

    expect($html)->toContain('type="datetime-local"')
        ->toContain('Publish at')
        ->toContain('Asia/Kolkata');
});

test('slug field renders mono input with regenerate slot', function () {
    $html = (string) $this->blade(
        '<x-ui.slug-field name="slug" label="Slug" value="abc"><x-slot:regenerate><button>Again</button></x-slot:regenerate></x-ui.slug-field>'
    );

    expect($html)->toContain('name="slug"')
        ->toContain('font-mono')
        ->toContain('>Again</button>');
});

test('code editor renders readonly mono textarea with hint', function () {
    $html = (string) $this->blade(
        '<x-ui.code-editor name="code" label="Code" hint="Read only" readonly />'
    );

    expect($html)->toContain('<textarea')
        ->toContain('font-mono')
        ->toContain('readonly')
        ->toContain('Read only');
});
