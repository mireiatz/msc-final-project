<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Http\Middleware\HandleCors;


class Kernel extends HttpKernel
{
    protected $middleware = [
        HandleCors::class,
    ];
}
