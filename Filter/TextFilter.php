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

class TextFilter extends BaseFilter
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
        $value = is_array($this->value) ? $this->value : [$this->value];
        
        $expr = $queryBuilder->expr()->andX();
        foreach($value as $i => $v) {
            $expr->add($this->column->getFilterSelector() . ' LIKE :' . $identifier . '_' . $i);
            $queryBuilder->setParameter($identifier . '_' . $i, '%' . $v . '%');
        }
        
        $queryBuilder->andWhere($expr);
    }
}
