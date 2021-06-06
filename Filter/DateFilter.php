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

class DateFilter extends BaseFilter
{
    public function apply($queryBuilder)
    {
        $identifier = $this->column->getFilterValuePlaceholder($this);
        $queryBuilder
            ->andWhere('DATE(' . $this->column->getFilterSelector() . ') = DATE(:' . $identifier . ')')
            ->setParameter($identifier, (new \DateTime())->setTimestamp($this->value));
    }
}
