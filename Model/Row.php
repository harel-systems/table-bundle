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

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;

class Row
{
    private $data;
    private $rawData;
    
    private $values = array();
    private $links = array();
    private $icons = array();
    private $classes = array();
    
    private $rows = [];
    
    public function __construct($data)
    {
        $this->rawData = $data;
        if(is_array($data) && array_key_exists('0', $data)) {
            $this->data = $data[0];
        } else {
            $this->data = $data;
        }
    }
    
    public function __destruct()
    {
        unset($this->data);
        unset($this->rawData);
        unset($this->values);
        unset($this->links);
        unset($this->classes);
    }
    
    public function getData()
    {
        return $this->data;
    }
    
    public function get($key)
    {
        return isset($this->values[$key]) ? $this->values[$key] : null;
    }
    
    public function getRawData()
    {
        return $this->rawData;
    }
    
    public function add(string $path, $value, $export = false)
    {
        if($export) {
            // NOTE Export keys are flat
            $this->values[$path] = $value;
        } else {
            $keys = explode('.', $path);
            $lastKey = array_pop($keys);
            
            $data = &$this->values;
            foreach($keys as $k) {
                $data = &$data[$k];
            }
            
            $data[$lastKey] = $value;
        }
        
        return $this;
    }
    
    public function addRow($values)
    {
        $this->rows[] = $values;
        
        return $this;
    }
    
    public function merge($values)
    {
        $this->values = array_replace_recursive($this->values, $values);
        
        return $this;
    }
    
    public function addLink(string $path, $value)
    {
        if($value) {
            $keys = explode('.', $path);
            $lastKey = array_pop($keys);
            
            $data = &$this->links;
            foreach($keys as $k) {
                $data = &$data[$k];
            }
            
            $data[$lastKey] = $value;
        }
        
        return $this;
    }
    
    public function addIcon(string $column, string $name, array $options)
    {
        $this->icons[$column][] = array_merge(array(
            'name' => $name,
        ), $options);
        
        return $this;
    }
    
    public function addClass(string $class)
    {
        $this->classes[] = $class;
        
        return $this;
    }
    
    public function serialize()
    {
        $row = $this->values;
        
        $row['links'] = $this->links;
        
        if(!empty($this->classes)) {
            $row['_class'] = implode(' ', $this->classes);
        }
        
        $row['_icons'] = $this->icons;
        
        return $row;
    }
    
    public function serializeForExport()
    {
        if(empty($this->rows)) {
            $rows = [$this->values];
        } else {
            $rows = $this->rows;
        }
        foreach($rows as $i => $cells) {
            foreach($cells as $key => $cell) {
                if(!$cell instanceof Cell) {
                    $rows[$i][$key] = WriterEntityFactory::createCell($cell ?? '');
                }
            }
        }
        return $rows;
    }
}
