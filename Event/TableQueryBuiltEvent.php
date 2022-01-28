<?php

/*
 * This file is part of the TableBundle package.
 *
 * (c) Harel Systems
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Harel\TableBundle\Event;

use Doctrine\ORM\QueryBuilder;
use Harel\TableBundle\Service\Table;

class TableQueryBuiltEvent
{
    const NAME = 'table_query_built';
    
    protected $table;
    protected $queryBuilder;
    
    public function __construct(Table $table, QueryBuilder $queryBuilder)
    {
        $this->table = $table;
        $this->queryBuilder = $queryBuilder;
    }
    
    public function getTable(): Table
    {
        return $this->table;
    }
    
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }
}
