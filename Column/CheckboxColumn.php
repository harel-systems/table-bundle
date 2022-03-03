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
use Harel\TableBundle\Model\Row;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckboxColumn extends BaseColumn
{
    public function getType()
    {
        return 'checkbox';
    }
    
    public function serialize()
    {
        $column = parent::serialize();
        
        if($this->options['input']) {
            $column['input']['type'] = 'checkbox';
        }
        $column['trueText'] = $this->options['trueText'];
        $column['falseText'] = $this->options['falseText'];
        
        return $column;
    }
    
    public function getExportData(Row $row, $format)
    {
        return parent::getExportData($row, $format) ? '1' : '0';
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        
        $resolver->setDefaults(array(
            'trueText' => '{icon yes, color=green}',
            'falseText' => '{icon no, color=red}',
        ));
    }
}
