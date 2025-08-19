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

use Harel\TableBundle\Filter\CurrencyFilter;
use Harel\TableBundle\Model\Column as BaseColumn;
use OpenSpout\Writer\Common\Creator\Style\StyleBuilder;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NumberColumn extends BaseColumn
{
    public function getType()
    {
        return 'number';
    }
    
    public function serialize()
    {
        $column = parent::serialize();
        
        if($this->options['nullValue'] !== null) {
            $column['nullValue'] = $this->options['nullValue'];
        }
        
        if($this->options['forceSign'] !== null) {
            $column['forceSign'] = $this->options['forceSign'];
        }
        
        if($this->options['decimals'] !== null) {
            $column['decimals'] = $this->options['decimals'];
        }
        
        if($this->options['suffix'] !== null) {
            $column['suffix'] = $this->options['suffix'];
        }
        
        if($this->options['input']) {
            $column['input']['type'] = 'text';
        }
        
        return $column;
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        
        $resolver->setDefaults(array(
            'markdown' => false,
            'placeholder' => null,
            'suffix' => null,
            'nullValue' => null,
            'minimum' => null,
            'maximum' => null,
            'forceSign' => null,
            'decimals' => null,
            'filter' => false,
            'exportNormalizer' => function($value) {
                $style = (new StyleBuilder())
                    ->setFormat('0.00')
                    ->build();
                
                return WriterEntityFactory::createCell($value);
            },
        ));
    }
    
    public function getApplicableFilters(string $value)
    {
        $value = str_replace(',', '.', $value);
        if(is_numeric($value)) {
            return [
                array(
                    'title' => $this->options['title'],
                    'class' => CurrencyFilter::class,
                    'column' => $this->identifier,
                    'value' => $this->identifier . '.=.' . $value,
                    'val' => '<' . (float)$value,
                    'label' => strtr('%title% < %price%', array('%title%' => $this->options['title'], '%price%' => $value)),
                ),
                array(
                    'title' => $this->options['title'],
                    'class' => CurrencyFilter::class,
                    'column' => $this->identifier,
                    'value' => $this->identifier . '.<.' . $value,
                    'val' => (float)$value,
                    'label' => strtr('%title% = %price%', array('%title%' => $this->options['title'], '%price%' => $value)),
                ),
                array(
                    'title' => $this->options['title'],
                    'class' => CurrencyFilter::class,
                    'column' => $this->identifier,
                    'value' => $this->identifier . '.>.' . $value,
                    'val' => '>' . (float)$value,
                    'label' => strtr('%title% > %price%', array('%title%' => $this->options['title'], '%price%' => $value)),
                ),
            ];
        }
        return [];
    }
}
