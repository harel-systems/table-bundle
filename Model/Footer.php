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

use Harel\TableBundle\Model\Footer\Group;
use Harel\TableBundle\Model\Footer\Button;

class Footer
{
    private $buttons = [];
    private $messages = [];
    private $formName;
    
    public function __construct($formName = null)
    {
        $this->formName = $formName;
    }
    
    public function addButton($className, $icon, $title, int $priority, $options = array())
    {
        $options = array_merge(array(
            'formName' => $this->formName,
        ), $options);
        
        $button = new Button($className, $icon, $title, $priority, $options);
        
        $this->buttons[] = $button;
    }
    
    public function addGroup($priority, $options = array())
    {
        $options = array_merge(array(
            'formName' => $this->formName,
        ), $options);
        
        $group = new Group($priority, $options);
        
        $this->buttons[] = $group;
        
        return $group;
    }
    
    public function toArray()
    {
        usort($this->buttons, function($a, $b) {
            return $a->getPriority() - $b->getPriority();
        });
        foreach($this->buttons as $i => $button) {
            $this->buttons[$i] = $button->toArray();
        }
        $array = array(
            'actions' => [$this->buttons],
        );
        if(!empty($this->messages)) {
            $array['messages'] = [];
            foreach($this->messages as $message) {
                $array['messages'][] = $message->toArray();
            }
        }
        return $array;
    }
}
