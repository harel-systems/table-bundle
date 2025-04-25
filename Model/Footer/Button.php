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

use Symfony\Component\OptionsResolver\OptionsResolver;

class Button
{
    private string $className;
    private string $icon;
    private string $title;
    private int $priority;
    private array $options = array();
    
    public function __construct(string $className, string $icon, string $title, int $priority, array $options = array())
    {
        $this->className = $className;
        $this->icon = $icon;
        $this->title = $title;
        $this->priority = $priority;
        
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(array(
                'name' => null,
                'url' => null,
                'target' => null,
                'modalOptions' => null,
                'identifier' => null,
                'callback' => null,
                'confirm' => null,
                'disabled' => false,
                'forceNavigation' => false,
                'tooltip' => null,
                'shortcut' => null,
            ))
        ;
    }
    
    public function toArray()
    {
        $button = array(
            'className' => $this->className,
            'icon' => $this->icon,
            'title' => $this->title,
        );
        if($this->options['name'] !== null) {
            $button['name'] = strlen($this->options['name']) ? $this->options['formName'] . '[' . $this->options['name'] . ']' : '';
        }
        if($this->options['identifier'] !== null) {
            $button['identifier'] = $this->options['identifier'];
        } elseif($this->options['name'] !== null) {
            $button['identifier'] = $this->options['name'];
        }
        if($this->options['callback'] !== null) {
            $button['callback'] = $this->options['callback'];
        }
        if($this->options['confirm'] !== null) {
            $button['confirm'] = $this->options['confirm'];
        }
        if($this->options['forceNavigation']) {
            $button['forceNavigation'] = true;
        }
        if($this->options['disabled']) {
            $button['disabled'] = true;
        }
        if($this->options['tooltip']) {
            $button['tooltip'] = $this->options['tooltip'];
        }
        if($this->options['shortcut']) {
            $button['shortcut'] = $this->options['shortcut'];
        }
        if($this->options['url'] !== null) {
            $button['url'] = $this->options['url'];
            if($this->options['target'] !== null) {
                $button['target'] = $this->options['target'];
                if($this->options['modalOptions'] !== null) {
                    $button['modal_options'] = $this->options['modalOptions'];
                }
            }
        }
        return $button;
    }
    
    public function getPriority()
    {
        return $this->priority;
    }
}
