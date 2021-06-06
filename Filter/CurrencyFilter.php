<?php

/*
 * This file is part of the TableBundle package.
 *
 * (c) Harel Systems
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Harel\TableBundle\Filter;

use Harel\TableBundle\Model\Filter as BaseFilter;

class CurrencyFilter extends BaseFilter
{
    public function serialize()
    {
        return array_merge(parent::serialize(), array(
            'label' => $this->value,
        ));
    }
    
    public function apply($queryBuilder)
    {
        $identifier = $this->column->getFilterValuePlaceholder($this);
        $queryBuilder
            ->andWhere($this->column->getFilterSelector() . ' = :' . $identifier)
            ->setParameter($identifier, $this->value);
    }
}
