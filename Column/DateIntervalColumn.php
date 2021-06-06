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

use Harel\TableBundle\Model\Row;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateIntervalColumn extends TextColumn
{
    public function getType()
    {
        return 'date_interval';
    }
    
    public function serialize()
    {
        $column = parent::serialize();

        $column['defaultSortOrder'] = $this->options['defaultSortOrder'];
        $column['input'] = array('type' => 'date_interval');

        return $column;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'format' => 'L',
            'defaultSortOrder' => 'DESC',
            'getter' => function(Row $row) {
                $dates = call_user_func($this->options['dates'], $row);
                if($dates instanceof Collection) {
                    $dates = $dates->toArray();
                }

                return $dates;
            },
            'exportGetter' => function(Row $row) {
                $dates = call_user_func($this->options['dates'], $row);
                if($dates instanceof Collection) {
                    $dates = $dates->toArray();
                }

                $dates = array_filter($dates);

                if(empty($dates)) {
                    return null;
                }

                $first = min($dates);
                $last = max($dates);

                if($first->format('Ymd') === $last->format('Ymd')) {
                    return $first->format('Y-m-d');
                }

                return $first->format('Y-m-d') . ' - ' . $last->format('Y-m-d');
            },
        ))
        ->setRequired(['dates']);
    }
}
