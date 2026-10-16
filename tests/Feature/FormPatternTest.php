<?php

declare(strict_types=1);

test('form errors render an assertive summary list', function () {
    $html = (string) $this->blade(
        '<x-ui.form-errors :messages="$messages" />',
        ['messages' => ['Name is required.', 'Email is invalid.']]
    );

    expect($html)->toContain('role="alert"')
        ->toContain('Name is required.')
        ->toContain('Email is invalid.');
});

test('form errors render nothing without messages', function () {
    expect((string) $this->blade('<x-ui.form-errors />'))->not->toContain('role="alert"');
});
