<?php

namespace Bramato\Uploadeasy\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Bramato\Uploadeasy\Uploadeasy
 */
class Uploadeasy extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'uploadeasy';
    }
}
