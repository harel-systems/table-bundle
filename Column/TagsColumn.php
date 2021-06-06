<?php

/*
 * This file is part of the TableBundle package.
 *
 * (c) Harel Systems
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Harel\TableBundle\Column;

use Harel\TableBundle\Model\Row;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagsColumn extends TextColumn
{
    public function getType()
    {
        return 'tags';
    }
    
    public function configureoptions(optionsresolver $resolver)
    {
        parent::configureoptions($resolver);
        
        $resolver
            ->setdefaults(array(
                'getter' => function(Row $row) {
                    return null;
                },
                'filter' => false,
                'sort' => false,
                'export' => false,
            ))
        ;
    }
    
    public function serialize()
    {
        $column = parent::serialize();
        
        $column['input']['type'] = 'tags';
        
        return $column;
    }
    
    public function getApplicableFilters(string $value)
    {
        return [];
    }
}
