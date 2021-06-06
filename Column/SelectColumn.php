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

use Harel\TableBundle\Filter\TextFilter;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SelectColumn extends TextColumn
{
    public function getType()
    {
        return 'select';
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        
        $resolver->setDefaults(array(
            'options' => '',
            'value' => null,
            'label' => null,
            'placeholder' => null,
            'filterOptions' => null,
            'searchable' => false,
            'clearable' => false,
            'normalizer' => function($value) {
                return (string)$value;
            },
        ));
    }
    
    public function serialize()
    {
        $column = parent::serialize();
        
        $column['input']['type'] = 'select';
        $column['input']['options'] = $this->options['options'];
        $column['input']['clearable'] = $this->options['clearable'];
        $column['input']['searchable'] = $this->options['searchable'];
        $column['input']['value'] = $this->options['value'] ?? $this->identifier;
        $column['input']['placeholder'] = $this->options['placeholder'];
        if($this->options['label'] !== null) {
            $column['input']['label'] = $this->options['label'];
        }
        
        return $column;
    }
    
    public function getApplicableFilters(string $value)
    {
        if(null !== $options = $this->options['filterOptions']) {
            $filters = [];
            foreach($options as $option) {
                if(strpos(strtolower($option['label']), strtolower($value)) !== false) {
                    $filters[] = array(
                        'title' => $this->options['title'],
                        'class' => TextFilter::class,
                        'column' => $this->identifier,
                        'value' => $this->identifier . '.' . $option['value'],
                        'val' => $option['value'],
                        'label' => $option['label'],
                    );
                }
            }
            return $filters;
        }
        return [];
    }
}
