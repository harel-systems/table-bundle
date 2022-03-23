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
                if(null !== $category = $this->column->getEntity($value)) {
                    $conditions[] = $queryBuilder->expr()->like($this->column->getSelector() . '.slug', ':slug' . $category->getId());
                    $queryBuilder->setParameter('slug' . $category->getId(), $category->getSlug() . '%');
                }
            }
            $queryBuilder->andWhere($queryBuilder->expr()->orX(...$conditions));
            return;
        }
        
        if(false !== $index = array_search('null', $values)) {
            array_splice($values, $index, 1);
            if(empty($values)) {
                $queryBuilder->andWhere($this->column->getSelector() . ' IS NULL');
            } else {
                $queryBuilder
                    ->andWhere($this->column->getSelector() . ' IS NULL OR ' . $this->column->getSelector() . ' IN (:' . $identifier . ')')
                    ->setParameter($identifier, $values);
            }
        } else {
            $queryBuilder
                ->andWhere($this->column->getSelector() . ' IN (:' . $identifier . ')')
                ->setParameter($identifier, $values);
        }
    }
}
