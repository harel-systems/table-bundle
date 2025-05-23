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

use Harel\TableBundle\Column\LinkColumn;
use Harel\TableBundle\Event\EntityFilterQueryBuiltEvent;
use Harel\TableBundle\Filter\EntityFilter;
use Harel\TableBundle\Filter\TextFilter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EntityColumn extends LinkColumn
{
    const MAX_RESULTS = 5;
    
    private $em;
    private $translator = null;
    private $dispatcher = null;

    public function __construct(EntityManagerInterface $em, ?EventDispatcherInterface $dispatcher = null, ?TranslatorInterface $translator = null)
    {
        $this->em = $em;
        $this->translator = $translator;
        $this->dispatcher = $dispatcher;
    }
    
    private function trans($message, $params, $domain)
    {
        if($this->translator === null) {
            return strtr($message, $params);
        }
        return /** @Ignore */$this->translator->trans($message, $params, $domain);
    }
    
    public function getFilterSelector()
    {
        return $this->options['matchingFilterSelector'] ?? parent::getFilterSelector();
    }
    
    public function getFilterEntityIdentifier()
    {
        return $this->options['filterEntityIdentifier'] ?? $this->options['selector'];
    }
    
    public function getApplicableFilters(string $value)
    {
        $filters = [];
        
        $selector = $this->getFilterEntityIdentifier();
        
        $queryBuilder = $this->em
            ->getRepository($this->options['class'])
            ->createQueryBuilder($selector)
            ->setMaxResults(self::MAX_RESULTS);
        
        if($this->options['filterSelector']) {
            $queryBuilder
                ->where($this->options['filterSelector'] . ' LIKE :text_' . str_replace('.', '_', $this->identifier))
                ->setParameter('text_' . str_replace('.', '_', $this->identifier), '%' . $value . '%');
        }
        
        if($this->options['filterCallback']) {
            $this->options['filterCallback']($queryBuilder, $value);
        }
        
        if($this->dispatcher !== null) {
            $this->dispatcher->dispatch(new EntityFilterQueryBuiltEvent($this->options['class'], $queryBuilder, $selector), EntityFilterQueryBuiltEvent::NAME);
        }
        
        try {
            $results = $queryBuilder->getQuery()->getResult();
        } catch(\Exception $e) {
            throw new \Exception('An error occurred while looking for entities with the following query: ' . $queryBuilder->getQuery()->getDQL() . PHP_EOL . $e->getMessage());
        }
        
        foreach($results as $result) {
            $filters[] = array_filter(array(
                'title' => $this->options['title'],
                'class' => EntityFilter::class,
                'column' => $this->identifier,
                'value' => $this->identifier . '.' . $result->getId(),
                'val' => $result->getId(),
                'label' => (string)$result,
                'multiple' => true,
                'img' => $this->getFilterImage($result),
                'icon' => $this->getFilterIcon($result),
            ));
        }
        
        if($this->options['nullFilter'] !== false) {
            $filters[] = array(
                'title' => $this->options['title'],
                'class' => EntityFilter::class,
                'column' => $this->identifier,
                'value' => $this->identifier . '.null',
                'val' => 'null',
                'label' => $this->options['nullFilter'],
                'multiple' => true,
                'icon' => $this->getFilterIcon(null),
            );
        }
        
        if($this->options['matchingFilter']) {
            $filters[] = array(
                'title' => $this->options['title'],
                'class' => TextFilter::class,
                'column' => $this->identifier,
                'value' => $this->identifier . '.' . $value,
                'val' => $value,
                'label' => $this->trans('Matching "%query%"', array('%query%' => (string)$value), 'HarelTableBundle'),
                'multiple' => true,
                'icon' => $this->getFilterIcon((string)$value),
            );
        }
        
        return $filters;
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        
        $resolver
            ->setDefaults(array(
                'filterCallback' => null,
                'label' => null,
                'filteringCallback' => null,
                'filterEntityIdentifier' => null,
                'matchingFilterSelector' => null,
                'slugFilter' => false,
                'matchingFilter' => true,
                'sortSelector' => function(Options $options) {
                    return ($options['filterEntityIdentifier'] ?? $options['selector']) . '.id';
                },
                'nullFilter' => false,
            ))
            ->setRequired(['class', 'selector'])
            ->setAllowedTypes('class', ['string'])
            ->setAllowedTypes('filterCallback', ['callable', 'null']);
    }
    
    public function serialize()
    {
        $column = parent::serialize();
        
        if($this->options['label'] !== null) {
            $column['label'] = $this->options['label'];
        }
        
        return $column;
    }
    
    public function getFilteringCallback()
    {
        return $this->options['filteringCallback'];
    }
    
    public function getSlugFilter()
    {
        return $this->options['slugFilter'];
    }
    
    public function getEntity($id)
    {
        return $this->em->getRepository($this->options['class'])->find($id);
    }
}
