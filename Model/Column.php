<?php

/*
 * This file is part of the TableBundle package.
 *
 * (c) Harel Systems
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Harel\TableBundle\Model;

use Harel\TableBundle\Model\Filter;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class Column
{
    protected $identifier;
    protected $options = array();
    protected $buttons = [];
    protected $filters = array();
    
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        
        return $this;
    }
    
    public function getIdentifier()
    {
        return $this->identifier;
    }
    
    public function setDisplay(bool $display)
    {
        $this->options['display'] = $display;
        
        return $this;
    }
    
    public function getType()
    {
        return null;
    }
    
    public function getDisplayable()
    {
        return !$this->options['filterOnly'];
    }
    
    public function getDisplay($export)
    {
        return $this->options['display'] && ($export ? $this->options['export'] : true);
    }
    
    public function getTitle()
    {
        return $this->options['title'];
    }
    
    public function setOptions(array $options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
        
        return $this;
    }
    
    protected function getRowValue(Row $row, $export = false)
    {
        $value = null;
        
        // Get value
        if($export && $this->options['exportGetter'] !== null) {
            $value = call_user_func($this->options['exportGetter'], $row);
        } elseif($this->options['getter'] !== null) {
            $value = call_user_func($this->options['getter'], $row);
        } else {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            if($propertyAccessor->isReadable($row->getData(), $this->identifier)) {
                $value = $propertyAccessor->getValue($row->getData(), $this->identifier);
            } elseif(is_array($row->getRawData()) && isset($row->getRawData()[$this->getSelector()])) {
                $value = $row->getRawData()[$this->getSelector()];
            } else {
                $value = null;
            }
        }
        
        // Normalize value
        if($export && $this->options['exportNormalizer'] !== null) {
            $value = call_user_func($this->options['exportNormalizer'], $value, $row);
        } elseif($this->options['normalizer'] !== null) {
            $value = call_user_func($this->options['normalizer'], $value, $row);
        }
        
        return $value;
    }
    
    public function getRowData(Row $row, $export = false)
    {
        $row->add($this->identifier, $this->getRowValue($row, $export), $export);
    }
    
    public function getExportData(Row $row)
    {
        return $this->getRowData($row, true);
    }
    
    public function getFilterPlaceholder()
    {
        if($this->options['filter'] !== false && $this->options['filterPlaceholder'] !== false) {
            return is_string($this->options['filterPlaceholder']) ? $this->options['filterPlaceholder'] : $this->options['title'];
        }
        return null;
    }
    
    public final function getFilters(string $query)
    {
        if($this->options['filter'] !== false) {
            return $this->getApplicableFilters($query);
        }
        return [];
    }
    
    public function getApplicableFilters(string $query)
    {
        return [];
    }
    
    public function addButton($identifier, $button)
    {
        $this->buttons[$identifier] = $button;
    }
    
    public function serialize()
    {
        $column = array(
            'type' => $this->getType(),
            'title' => $this->options['title'],
            'display' => $this->options['display'],
            'identifier' => $this->identifier,
        );
        if(!$this->options['sort']) {
            $column['sort'] = false;
        }
        if($this->options['help'] !== null) {
            $column['help'] = $this->options['help'];
        }
        if(!empty($this->buttons)) {
            foreach($this->buttons as $identifier => $button) {
                $column['buttons'][$identifier] = $button->serialize();
            }
        }
        if($this->options['input']) {
            $column['input'] = array();
        }
        if($this->options['width'] !== null) {
            $column['width'] = $this->options['width'];
        }
        if($this->options['maxWidth'] !== null) {
            $column['maxWidth'] = $this->options['maxWidth'];
        }
        if($this->options['minWidth'] !== null) {
            $column['minWidth'] = $this->options['minWidth'];
        }
        if($this->options['className'] !== null) {
            $column['class'] = $this->options['className'];
        }
        if($this->options['contentClassProperty'] !== null) {
            $column['contentClass'] = $this->options['contentClassProperty'];
        }
        if(is_array($this->options['sortSelector'])) {
            $column['sortOptions'] = $this->options['sortSelector'];
        }
        if(is_array($this->options['conditions'])) {
            $column['conditions'] = $this->options['conditions'];
        }
        if($this->options['history']) {
            $column['history'] = true;
        }
        return $column;
    }
    
    public function getSelector()
    {
        return $this->options['selector'];
    }
    
    public function getFilterSelector()
    {
        return $this->options['filterSelector'] ?? $this->options['selector'];
    }
    
    public function getFilterValuePlaceholder(Filter $filter)
    {
        if(false === $index = array_search($filter, $this->filters, true)) {
            $index = count($this->filters);
            $this->filters[] = $filter;
        }
        return str_replace('.', '_', $this->getIdentifier()). '_' . $index;
    }
    
    public function getSortSelector($sortOption = null)
    {
        if($sortOption && isset($this->options['sortSelector'][$sortOption])) {
            return $sortOption;
        } elseif(is_array($this->options['sortSelector'])) {
            return array_keys($this->options['sortSelector'])[0];
        } else {
            return $this->options['sortSelector'];
        }
    }
    
    public function isSortable()
    {
        return $this->options['sort'];
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(array(
                'title' => null,
                'className' => null,
                'conditions' => null,
                'optional' => true,
                'help' => null,
                'display' => true,
                'input' => false,
                'getter' => null,
                'exportGetter' => null,
                'normalizer' => null,
                'exportNormalizer' => null,
                'filterOnly' => false,
                'filter' => true,
                'filterPlaceholder' => false,
                'filterSelector' => null,
                'export' => true,
                'contentClassProperty' => null,
                'selector' => function(Options $options) {
                    return 'o.' . $this->identifier;
                },
                'sort' => true,
                'sortSelector' => function(Options $options) {
                    return $options['selector'];
                },
                'width' => null,
                'maxWidth' => null,
                'minWidth' => null,
                'history' => false,
            ))
            ->setAllowedTypes('filter', ['boolean', 'array'])
            ->setAllowedTypes('sort', ['boolean'])
            ->setAllowedTypes('input', ['boolean'])
        ;
    }
}
