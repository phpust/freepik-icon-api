<?php

namespace Freepik\IconApi\Facades;

use Illuminate\Support\Facades\Facade;

class Freepik extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'freepik-icon-api';
    }
}
