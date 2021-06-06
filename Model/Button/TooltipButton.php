<?php

/*
 * This file is part of the TableBundle package.
 *
 * (c) Harel Systems
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Harel\TableBundle\Model\Button;

/**
 * @deprecated
 */
class TooltipButton
{
    private $options;
    
    public function __construct($options)
    {
        $this->options = $options;
    }
    
    public function serialize()
    {
        return array_merge($this->options, array(
            'type' => 'tooltip',
        ));
    }
}
