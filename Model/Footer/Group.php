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
    private array $buttons = array();
    private int $priority;
    
    public function __construct(int $priority)
    {
        $this->priority = $priority;
    }
    
    public function addButton(string $className, string $icon, string $title, string $priority, array $options = array())
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
    
    public function toArray()
    {
        $buttons = array_values($this->buttons);
        usort($buttons, function($a, $b) {
            return $a->getPriority() - $b->getPriority();
        });
        foreach($buttons as $i => $button) {
            $buttons[$i] = $button->toArray();
        }
        return $buttons;
    }
    
    public function getPriority(): int
    {
        return $this->priority;
    }

    public function get(string $key): ?Button
    {
        return $this->buttons[$key] ?? null;
    }

    public function remove(string $key): static
    {
        unset($this->buttons[$key]);
        return $this;
    }
}
