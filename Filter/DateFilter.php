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
        $value = (string)$this->value;
        $comparison = substr($value, 0, 1);
        if(in_array($comparison, ['<', '>', '='])) {
            $value = substr($value, 1);
            if(substr($value, 0, 1) === '=') {
                $comparison .= '=';
                $value = substr($value, 1);
            }
        } else {
            $comparison = '=';
        }
        
        $identifier = $this->column->getFilterValuePlaceholder($this);
        $queryBuilder
            ->andWhere('DATE(' . $this->column->getFilterSelector() . ') ' . $comparison . ' DATE(:' . $identifier . ')')
            ->setParameter($identifier, (new \DateTime())->setTimestamp($value));
    }
}
