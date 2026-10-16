<?php

declare(strict_types=1);

test('empty state renders icon, title, body, and action', function () {
    $html = (string) $this->blade(
        '<x-ui.empty-state title="No users" body="Invite someone."><x-slot:action><button>Invite</button></x-slot:action></x-ui.empty-state>'
    );

    expect($html)->toContain('No users')
        ->toContain('Invite someone.')
        ->toContain('<button>Invite</button>');
});

test('denied state is announced as an alert with a default title', function () {
    $html = (string) $this->blade('<x-ui.denied-state />');

    expect($html)->toContain('role="alert"')
        ->toContain('Not permitted');
});

test('index toolbar composes filter, action, and meta slots', function () {
    $html = (string) $this->blade(
        '<x-ui.index-toolbar><x-slot:filters><input name="q"></x-slot:filters><x-slot:actions><button>Go</button></x-slot:actions><x-slot:meta>3 results</x-slot:meta></x-ui.index-toolbar>'
    );

    expect($html)->toContain('name="q"')
        ->toContain('>Go</button>')
        ->toContain('3 results');
});

test('index toolbar renders without optional slots', function () {
    $html = (string) $this->blade('<x-ui.index-toolbar />');

    expect($html)->not->toContain('Exception');
});
