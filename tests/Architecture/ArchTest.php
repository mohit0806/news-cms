<?php

declare(strict_types=1);

arch('application code uses strict types')
    ->expect(['App\\', 'Database\\'])
    ->toUseStrictTypes();

arch('application code contains no debug functions')
    ->expect(['App\\', 'Database\\'])
    ->not->toUse(['dd', 'dump', 'die', 'exit', 'var_dump', 'ray']);

// Future P0/P1 rules (add with the code they protect; TRD §23.2, ARCH §11):
// - public query classes apply the published() scope;
// - no `{!! !!}` outside the x-article-body component (also enforced by CI grep);
// - capability code does not write another capability's tables;
// - Livewire actions authorize + validate server-side.
