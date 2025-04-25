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
    
    public function addButton(string $className, string $icon, string $title, int $priority, array $options = array())
    {
        $options = array_merge(array(
            'identifier' => null,
        ), $options);
        
        $button = new Button($className, $icon, $title, $priority, $options);
        
        if($options['identifier'] === null) {
            $this->buttons[] = $button;
        } else {
            $this->buttons[$options['identifier']] = $button;
        }
    }
    
    public function addGroup(int $priority, ?string $identifier = null)
    {
        $group = new Group($priority);
        
        if($identifier === null) {
            $this->buttons[] = $group;
        } else {
            $this->buttons[$identifier] = $group;
        }
        
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

    public function get(string $key): Button|Group|null
    {
        return $this->buttons[$key] ?? null;
    }

    public function remove(string $key): static
    {
        unset($this->buttons[$key]);
        return $this;
    }
}
