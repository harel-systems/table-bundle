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

use Harel\TableBundle\Model\Column as BaseColumn;
use Harel\TableBundle\Filter\TextFilter;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextColumn extends BaseColumn
{
    public function getType()
    {
        return 'text';
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        
        $resolver->setDefaults(array(
            'markdown' => false,
            'placeholder' => null,
            'normalizer' => function($value) {
                return is_array($value) ? $value : (string)$value;
            },
            'multiline' => false,
        ));
    }
    
    public function serialize()
    {
        $column = parent::serialize();
        
        $column['markdown'] = $this->options['markdown'];
        
        if($this->options['input']) {
            $column['input']['type'] = 'text';
            if($this->options['multiline']) {
                $column['input']['multiline'] = true;
            }
        }
        
        return $column;
    }
    
    public function getApplicableFilters(string $value)
    {
        return [
            array(
                'title' => $this->options['title'],
                'class' => TextFilter::class,
                'column' => $this->identifier,
                'value' => $this->identifier . '.' . $value,
                'val' => $value,
                'label' => $value,
            ),
        ];
    }
}
