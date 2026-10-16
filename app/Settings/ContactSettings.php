<?php

declare(strict_types=1);

namespace App\Settings;

class ContactSettings extends NewsSettings
{
    public ?string $email = null;

    public ?string $phone = null;

    /** @var array<string, string> */
    public array $address = [];

    public static function group(): string
    {
        return 'contact';
    }

    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['array'],
            'address.*' => ['nullable', 'string', 'max:500'],
        ];
    }
}
