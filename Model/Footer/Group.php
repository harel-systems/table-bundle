<?php

/*
 * This file is part of the TableBundle package.
 *
 * (c) Harel Systems
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Harel\TableBundle\Model\Footer;

class Group
{
    private $buttons = [];
    private $priority;
    private $formName;
    
    public function __construct($priority, $options = array())
    {
        $this->priority = $priority;
        $this->formName = $options['formName'];
    }
    
    public function addButton($class, $icon, $title, $priority, $options = array())
    {
        $options = array_merge(array(
            'formName' => $this->formName,
        ), $options);
        
        $button = new Button($class, $icon, $title, $priority, $options);
        
        $this->buttons[] = $button;
    }
    
    public function toArray()
    {
        usort($this->buttons, function($a, $b) {
            return $a->getPriority() - $b->getPriority();
        });
        foreach($this->buttons as $i => $button) {
            $this->buttons[$i] = $button->toArray();
        }
        return $this->buttons;
    }
    
    public function getPriority()
    {
        return $this->priority;
    }
}
