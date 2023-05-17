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


class Filter
{
    protected $column;
    protected $value;
    protected $options = array();
    
    public function setColumn($column = null)
    {
        $this->column = $column;
        return $this;
    }
    
    public function setValue($value = null)
    {
        $this->value = $value;
        return $this;
    }
    
    public function setOptions(array $options = array())
    {
        $this->options = $options;
        return $this;
    }
    
    public function serialize()
    {
        return array(
            'filter' => static::class,
            'column' => $this->column->getIdentifier(),
            'value' => $this->value,
            'icon' => $this->column->getFilterIcon($this->value),
            'img' => $this->column->getFilterImage($this->value),
            'className' => $this->column->getFilterClassname($this->value),
        );
    }
}
