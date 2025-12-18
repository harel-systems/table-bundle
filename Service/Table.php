<?php

/*
 * This file is part of the TableBundle package.
 *
 * (c) Harel Systems
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Harel\TableBundle\Service;

use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Harel\TableBundle\Event\TableBuiltEvent;
use Harel\TableBundle\Event\TableQueryBuiltEvent;
use Harel\TableBundle\Service\TableBuilder;
use Spatie\Url\Url;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

abstract class Table
{
    protected TableBuilder $tableBuilder;
    protected ?EventDispatcherInterface $dispatcher = null;
    protected array $pagination;
    protected bool $configUpdated = false;
    protected ?string $url = null;
    protected ?array $options = null;
    
    private const RESERVED_QUERY_PARAMETERS = ['h', 's', '_data'];
    
    /**
     * @required
     */
    public function setTableBuilder(TableBuilder $tableBuilder): void
    {
        $this->tableBuilder = $tableBuilder;
    }
    
    /**
     * @required
     */
    public function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): void
    {
        $this->dispatcher = $eventDispatcher;
    }
    
    protected function dispatchEvent($event, $name)
    {
        if($this->dispatcher !== null) {
            return $this->dispatcher->dispatch($event, $name);
        }
    }
    
    public function setOptions(array $options): static
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
        return $this;
    }
    
    protected function configureOptions(OptionsResolver $resolver)
    {
        
    }
    
    protected function getCurrentConfig()
    {
        return false;
    }
    
    protected function updatePagination(?Request $request = null): void
    {
        $oldConfig = $this->getCurrentConfig();
        
        if($oldConfig === false) {
            $oldConfig = array(
                'filters' => $this->tableBuilder->serializeDefaultQuickFilters(),
            );
        }
        
        if($request !== null) {
            $requestConfig = $request->request->all('_table_pagination') ?? array();
            $request->request->remove('_table_pagination');
            // Convert header configuration to a non-associative array
            if(null !== $_headerConfig = $request->query->get('h')) {
                foreach(explode(';', $_headerConfig) as $column) {
                    list($identifier, $display, $group) = explode(':', $column);
                    $requestConfig['header'][] = array(
                        'identifier' => $identifier,
                        'display' => $display,
                        'group' => $group,
                    );
                }
            }
        } else {
            $requestConfig = array();
        }
            
        $resolver = new OptionsResolver();
        $this->configurePagination($resolver);
        $this->pagination = $resolver->resolve(array_merge($oldConfig, $requestConfig));
        
        $this->pagination['count'] = (int)$this->pagination['count'];
        $this->pagination['page'] = (int)$this->pagination['page'];
        
        $oldConfig = json_encode($oldConfig);
        
        $this->configUpdated = $oldConfig !== json_encode($this->pagination);
    }
    
    public function configurePagination(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'count' => 25,
            'page' => 1,
            'sort' => null,
            'sortOption' => null,
            'order' => 'ASC',
            'filters' => [],
            'header' => [],
            'groups' => null,
            'template' => false,
        ));
        $resolver
            ->setNormalizer('template', fn(Options $options, $value) => $value == 1)
            ->setIgnoreUndefined(true);
    }
    
    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }
    
    protected function getUrl(string $format, $parameters = null): string
    {
        if($this->url === null || is_array($parameters)) {
            $url = Url::fromString($this->request_stack->getCurrentRequest()->getUri());
            
            foreach(self::RESERVED_QUERY_PARAMETERS as $p) {
                $url = $url->withoutQueryParameter($p);
            }
            
            if(is_array($parameters)) {
                foreach($parameters as $key => $value) {
                    $url = $url->withQueryParameter($key, $value);
                }
            }
            
            $this->url = urldecode((string)$url);
        }
        
        $url = str_replace(['.html', '.json', '.csv'], ['.'.$format, '.'.$format, '.'.$format], $this->url);
        
        if(strpos($url, '.'.$format) === false) {
            if(strpos($url, '?') !== false) {
                $url = str_replace('?', '.'.$format.'?', $url);
            } else {
                $url .= '.'.$format;
            }
        }
        
        return $url;
    }
    
    protected function handlePostRequest(Request $request, $queryBuilder)
    {
        throw new MethodNotAllowedException(['GET'], 'This table doesn\'t support posting a form.');
    }
    
    public function handleRequest(Request $request): array|Response
    {
        if($this->options === null) {
            $this->setOptions(array());
        }
        
        $this->tableBuilder->resetBuild();
        $this->build($this->tableBuilder);
        
        $this->dispatchEvent(new TableBuiltEvent($this, $this->tableBuilder), TableBuiltEvent::NAME);
        
        $this->updatePagination($request);
        
        if(null !== $query = $request->query->get('s')) {
            return new JsonResponse($this->tableBuilder->serializeFilters($query));
        }
        
        $queryBuilder = $this->getBuilder();
        
        $this->dispatchEvent(new TableQueryBuiltEvent($this, $queryBuilder), TableQueryBuiltEvent::NAME);
        
        $pagination = $this->pagination;
        
        $pagination['quick_filters'] = $this->tableBuilder->serializeQuickFilters();
        $pagination['filters'] = $this->getAppliedFilters();
        
        $_dataOnly = $request->query->get('_data');
        
        if($request->getMethod() !== 'POST' || empty($request->request->all())) {
            $_data = $this->tableBuilder->serializeAggregatedData(clone $queryBuilder, false, $_dataOnly);
            
            $this->tableBuilder->filterData($queryBuilder, $pagination['filters']);
        } else {
            // NOTE On POST, data must be filtered before handling forms to allow affecting
            //      all filtered results, so the builder is filtered, then processed, then reset
            //      and filtered again to generate the correct _data
            // NOTE Also, the table itself must be rebuilt because the build stage typically depends
            //      on the underlying data for editable tables
            $this->tableBuilder->filterData($queryBuilder, $pagination['filters']);
            $additionalData = $this->tableBuilder->handlePostRequest($request, $queryBuilder);
            
            // Rebuild table with new data
            $this->tableBuilder->resetBuild();
            $this->build($this->tableBuilder);
        
            $this->dispatchEvent(new TableBuiltEvent($this, $this->tableBuilder), TableBuiltEvent::NAME);
            
            $queryBuilder = $this->getBuilder();
            
            $this->dispatchEvent(new TableQueryBuiltEvent($this, $queryBuilder), TableQueryBuiltEvent::NAME);
            
            $_data = $this->tableBuilder->serializeAggregatedData(clone $queryBuilder, false, $_dataOnly);
            
            $this->tableBuilder->filterData($queryBuilder, $pagination['filters']);
        }
        
        $_data = array_merge($_data, $this->tableBuilder->serializeAggregatedData(clone $queryBuilder, true, $_dataOnly));
        
        if($_dataOnly !== null) {
            return new JsonResponse($_data);
        }
        
        $pagination['loading'] = false;
        
        $data = array(
            'data' => $this->tableBuilder->serializeData($queryBuilder, $pagination),
            '_data' => $_data,
            'columns' => $this->normalizeHeader($this->tableBuilder->serializeHeader($pagination), $pagination),
            'groups' => $this->tableBuilder->serializeGroups($pagination),
            'permissions' => $this->tableBuilder->getPermissions(),
            'pagination' => $pagination,
            'params' => $this->tableBuilder->serializeParams(),
            'footer' => $this->tableBuilder->serializeFooter(),
            'mods' => $this->tableBuilder->serializeMods(),
        );
        
        if(isset($additionalData)) {
            $data = array_merge($data, $additionalData);
        }
        
        $data['params']['filter_url'] = $this->getUrl('json');
        $data['params']['config_url'] = $data['params']['filter_url'];
        $data['params']['export_url'] = $this->getUrl('csv');
        $data['params']['options_url'] = $this->getUrl('options');
        
        $url = $data['params']['filter_url'];
        
        return $data;
    }

    protected function normalizeHeader(array $columns, array $pagination) : array
    {
        return $columns;
    }
    
    public function getNavigation($id): array
    {
        $this->setOptions(array());
        
        $this->tableBuilder->resetBuild();
        $this->build($this->tableBuilder);
        
        $this->dispatchEvent(new TableBuiltEvent($this, $this->tableBuilder), TableBuiltEvent::NAME);
        
        $this->updatePagination();
        
        $queryBuilder = $this->getBuilder();
        
        $this->dispatchEvent(new TableQueryBuiltEvent($this, $queryBuilder), TableQueryBuiltEvent::NAME);
        
        return $this->tableBuilder->getNavigation($id, $queryBuilder, $this->pagination);
    }
    
    public function getSelection($property = 'DISTINCT o.id'): array
    {
        $this->tableBuilder->resetBuild();
        $this->build($this->tableBuilder);
        
        $this->dispatchEvent(new TableBuiltEvent($this, $this->tableBuilder), TableBuiltEvent::NAME);
        
        $this->updatePagination();
        
        $queryBuilder = $this->getBuilder();
        
        $this->dispatchEvent(new TableQueryBuiltEvent($this, $queryBuilder), TableQueryBuiltEvent::NAME);
        
        return $this->tableBuilder->getSelection($queryBuilder, $this->pagination, $property);
    }
    
    public function getSelectionFromString($selection, $property = 'DISTINCT o.id'): array
    {
        if($selection === 'all') {
            return $this->getSelection($property);
        }
        return explode(',', $selection);
    }
    
    protected function getAppliedFilters()
    {
        return  $this->pagination['filters'] ?: [];
    }
    
    protected function getAggregationQueryBuilder($queryBuilder, string $class, string $selector, string $inSelector, array $params = array(), array $joins = array()): QueryBuilder
    {
        $queryBuilder = clone $queryBuilder;
        $parameters = $queryBuilder->getParameters();
        foreach($params as $key => $value) {
            $parameters->add(new Parameter($key, $value));
        }
        
        $_queryBuilder = $this->em->createQueryBuilder()
            ->from($class, '_a_')
            ->select(str_replace('o.', '_a_.', $selector))
            ->where(str_replace('o.', '_a_.', $inSelector) . ' IN (' . $queryBuilder->select('DISTINCT(o.id)')->getQuery()->getDQL() . ')')
            ->setParameters($parameters)
        ;
        
        foreach($joins as $join => $identifier) {
            if($join[0] === '!') {
                $_queryBuilder->join(str_replace('o.', '_a_.', substr($join, 1)), $identifier);
            } else {
                $_queryBuilder->leftJoin(str_replace('o.', '_a_.', $join), $identifier);
            }
        }
        return $_queryBuilder;
    }
    
    protected function getAggregationQuery($queryBuilder, $selector, $params = array(), $joins = array()): Query
    {
        return $this->getAggregationQueryBuilder($queryBuilder, $queryBuilder->getRootEntities()[0], $selector, '_a_.id', $params, $joins)->getQuery();
    }
    
    protected function getAggregatedValue($queryBuilder, $selector, $params = array(), $joins = array())
    {
        return $this->getAggregationQuery($queryBuilder, $selector, $params, $joins)->getSingleScalarResult();
    }
    
    protected function getAggregatedValues($queryBuilder, $selector, $params = array(), $joins = array())
    {
        return $this->getAggregationQuery($queryBuilder, $selector, $params, $joins)->getScalarResult()[0];
    }
}
