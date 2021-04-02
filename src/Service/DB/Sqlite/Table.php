<?php

namespace Owl\Service\DB\Sqlite;

use Owl\Service\DB\Table as BaseTable;

// @FIXME
class Table extends BaseTable
{
    protected function listColumns(): array
    {
        return [];
    }

    protected function listIndexes(): array
    {
        return [];
    }

    protected function listForeignKeys(): array
    {
        return [];
    }
}
