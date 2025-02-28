<?php

namespace Vnideas\Initial\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Vnideas\Initial\Initial
 */
class Initial extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Vnideas\Initial\Initial::class;
    }
}
