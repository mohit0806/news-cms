<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\SystemStatus;
use Illuminate\Contracts\View\View;

class SystemStatusController extends Controller
{
    public function __invoke(): View
    {
        return view('system-status', ['checks' => SystemStatus::checks()]);
    }
}
