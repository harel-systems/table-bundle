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

class ProgressColumn extends TextColumn
{
    public function getType()
    {
        return 'progress';
    }
    
    public function configureoptions(optionsresolver $resolver)
    {
        parent::configureoptions($resolver);
        
        $resolver
            ->setdefaults(array(
                'definition' => null,
                'zones' => [
                    array('value' => 0, 'style' => 'primary'),
                ],
                'getter' => function(Row $row) {
                    return null;
                },
                'filter' => false,
                'sort' => false,
            ))
        ;
    }
    
    public function serialize()
    {
        $column = parent::serialize();
        
        $column['input']['type'] = 'progress';
        $column['input']['definition'] = $this->options['definition'] ?? $this->identifier;
        $column['zones'] = $this->options['zones'];
        
        return $column;
    }
    
    public function getApplicableFilters(string $value)
    {
        return [];
    }
}
