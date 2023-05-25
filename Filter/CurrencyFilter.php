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
        $value = (float)$value;
        
        $identifier = $this->column->getFilterValuePlaceholder($this);
        $queryBuilder
            ->andWhere($this->column->getFilterSelector() . ' ' . $comparison . ' :' . $identifier)
            ->setParameter($identifier, $value);
    }
}
