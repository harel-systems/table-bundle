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
use Symfony\Contracts\EventDispatcher\Event;

class EntityFilterQueryBuiltEvent extends Event
{
    const NAME = 'entity_filter_query_built';
    
    protected $entityClass;
    protected $queryBuilder;
    protected $selector;
    
    public function __construct(string $entityClass, QueryBuilder $queryBuilder, string $selector = 'o')
    {
        $this->entityClass = $entityClass;
        $this->queryBuilder = $queryBuilder;
        $this->selector = $selector;
    }
    
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }
    
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }
    
    public function getSelector(): string
    {
        return $this->selector;
    }
}
