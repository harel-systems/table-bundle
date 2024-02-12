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

use Harel\TableBundle\Filter\DateFilter;
use OpenSpout\Writer\Common\Creator\Style\StyleBuilder;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateColumn extends TextColumn
{
    public function getType()
    {
        return 'date';
    }
    
    public function serialize()
    {
        $column = parent::serialize();
        
        $column['format'] = $this->options['format'];
        $column['relative'] = $this->options['relative'];
        
        $column['defaultSortOrder'] = $this->options['defaultSortOrder'];
        
        if($this->options['input']) {
            $column['input']['type'] = 'date';
        }
        
        return $column;
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        
        $resolver->setDefaults(array(
            'format' => 'L',
            'relative' => false,
            'normalizer' => function($value) {
                return $value;
            },
            'defaultSortOrder' => 'DESC',
            'exportNormalizer' => function($value, $row, $format) {
                return self::getExportCell($value, $format);
            },
        ));
    }
    
    static function getExportCell($value, $format = null)
    {
        $value = $value === null ? null : (is_string($value) ? new \DateTime($value) : $value);
        
        if($format === 'csv') {
            return WriterEntityFactory::createCell($value ? $value->format('Y-m-d') : null);
        }
        
        $style = (new StyleBuilder())
            ->setFormat('yyyy-mm-dd')
            ->build();
        
        return WriterEntityFactory::createCell($value, $style);
    }
    
    public function getApplicableFilters(string $value)
    {
        $pattern = strtolower((new \IntlDateFormatter(\Locale::getDefault(), \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT))->getPattern());
        if($pattern[0] === 'y') {
            $format = 'YMD';
        } elseif($pattern[0] === 'd') {
            $format = 'DMY';
        } else {
            $format = 'MDY';
        }
        
        preg_match_all('/[0-9]+/', $value, $numbers);
        $numbers = array_filter($numbers[0]);
        preg_match_all('/[^0-9]+/', $value, $separators);
        $separators = array_filter($separators[0]);
        
        $count = count($numbers);
        if($count !== 2 && $count !== 3) {
            return [];
        }
        
        /**
         * Extract year
         */
        if($count === 2) {
            $year = date('Y');
        } else {
            $year = null;
            // Take any 4-digit number
            foreach($numbers as $i => $number) {
                if(strlen($number) === 4 || $number > 31) {
                    $year = $number;
                    array_splice($numbers, $i, 1);
                    break;
                }
            }
            // If no 4-digit number found, take the last number
            if($year === null) {
                if($format[0] === 'Y') {
                    $year = array_shift($numbers);
                } else {
                    $year = array_pop($numbers);
                }
            }
        }
        
        if(strlen($year) === 2) {
            $year = (int)$year + 2000;
        }
        
        $format = str_replace('Y', '', $format);
        
        /**
         * Extract day and month
         */
        if($format[0] === 'D') {
            $day = $numbers[0];
            $month = $numbers[1];
        } else {
            $day = $numbers[1];
            $month = $numbers[0];
        }
        
        if($month > 12 && $day <= 12) {
            list($day, $month) = [$month, $day];
        }
        
        if($day > 31 || $month > 12) {
            return [];
        }
        
        $date = (new \DateTime())->setDate($year, $month, $day);
        
        $formatter = new \IntlDateFormatter(\Locale::getDefault(), \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);
        
        return [
            array(
                'title' => $this->options['title'],
                'class' => DateFilter::class,
                'column' => $this->identifier,
                'value' => $this->identifier . '.' . $date->format('Ymd'),
                'val' => $date->format('U'),
                'label' => $formatter->format($date),
            ),
        ];
    }
}
