<?php

namespace Nazalas\PersistentTableFilters\Facades;

use Illuminate\Support\Facades\Facade;

class PersistentTableFilters extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'persistent-table-filters';
    }
}
