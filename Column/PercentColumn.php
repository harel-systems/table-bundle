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

class PercentColumn extends NumberColumn
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        
        $resolver->setDefaults(array(
            'suffix' => array('value' => '%'),
            'nullValue' => '-',
            'width' => 70,
        ));
    }
}
