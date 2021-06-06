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

use Symfony\Component\OptionsResolver\OptionsResolver;

use Harel\TableBundle\Model\Row;

class LinkColumn extends TextColumn
{
    public function getType()
    {
        return 'link';
    }
    
    public function getRowData(Row $row, $export = false)
    {
        parent::getRowData($row, $export);
        
        if($this->options['link'] !== null) {
            $link = $this->options['link']($row);
            if($link) {
                $row->addLink($this->identifier, $link);
            }
        }
    }
    
    public function serialize()
    {
        $data = parent::serialize();
        
        $data['link'] = $this->identifier;
        
        if($this->options['target'] !== null) {
            $data['target'] = $this->options['target'];
        }
        
        return $data;
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        
        $resolver
            ->setDefaults(array(
                'link' => null,
                'target' => null,
            ))
            ->setAllowedTypes('link', ['callable', 'null']);
    }
}
