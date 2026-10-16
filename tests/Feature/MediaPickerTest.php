<?php

declare(strict_types=1);

test('media picker composes preview and trigger slots', function () {
    $html = (string) $this->blade(
        '<x-ui.media-picker label="Cover"><x-slot:preview><img src="/t.jpg"></x-slot:preview><x-slot:trigger><button>Pick</button></x-slot:trigger></x-ui.media-picker>'
    );

    expect($html)->toContain('Cover')
        ->toContain('/t.jpg')
        ->toContain('>Pick</button>');
});
