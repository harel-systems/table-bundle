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

class ConstantFilter extends BaseFilter
{
    public function apply($queryBuilder)
    {
        $identifier = $this->column->getFilterValuePlaceholder($this);
        $queryBuilder
            ->andWhere($this->column->getFilterSelector() . ' IN (:' . $identifier. ')')
            ->setParameter($identifier, is_array($this->value) ? $this->value : [$this->value]);
    }
}
