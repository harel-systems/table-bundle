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

class EntityFilter extends BaseFilter
{
    public function serialize()
    {
        return array_merge(parent::serialize(), array(
            'label' => (string)$this->value,
            'value' => $this->value->getId(),
        ));
    }
    
    public function apply($queryBuilder)
    {
        if(null !== $filter = $this->column->getFilteringCallback()) {
            $filter($queryBuilder, $this->value);
            return;
        }
        
        $identifier = $this->column->getFilterValuePlaceholder($this);
        $values = is_array($this->value) ? $this->value : [$this->value];
        
        if($this->column->getSlugFilter()) {
            $conditions = [];
            foreach($values as $value) {
                $category = $this->column->getEntity($value);
                $conditions[] = $queryBuilder->expr()->like($this->column->getSelector() . '.slug', ':slug' . $category->getId());
                $queryBuilder->setParameter('slug' . $category->getId(), $category->getSlug() . '%');
            }
            $queryBuilder->andWhere($queryBuilder->expr()->orX(...$conditions));
            return;
        }
        
        $queryBuilder
            ->andWhere($this->column->getSelector() . ' IN (:' . $identifier . ')')
            ->setParameter($identifier, $values);
    }
}
