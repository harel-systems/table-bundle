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

use Harel\TableBundle\Service\TableBuilder;
use Harel\TableBundle\Service\Table;

class TableBuiltEvent
{
    const NAME = 'table_built';
    
    protected $table;
    protected $builder;
    
    public function __construct(Table $table, TableBuilder $builder)
    {
        $this->table = $table;
        $this->builder = $builder;
    }
    
    public function getTable(): Table
    {
        return $this->table;
    }
    
    public function getBuilder(): TableBuilder
    {
        return $this->builder;
    }
}
