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

use Harel\TableBundle\Model\Column;
use Harel\TableBundle\Model\Footer;
use Harel\TableBundle\Model\Row;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TableBuilder
{
    private $em;
    private $columnLocator;
    private $filterLocator;
    
    public function __construct(EntityManagerInterface $em, ServiceLocator $columnLocator, ServiceLocator $filterLocator)
    {
        $this->em = $em;
        $this->columnLocator = $columnLocator;
        $this->filterLocator = $filterLocator;
    }
    
    /**
     * NOTE These functions are here only to support the serializeDateFilters method.
     * TODO find a solution to extract date filter generation
     */
    private function trans($string, $placeholders = array(), $domain = 'Base')
    {
        return strtr($string, $placeholders);
    }
    private function isGranted($role)
    {
        return true;
    }
    
    private $params = array();
    private $permissions = array();
    private $columns = array();
    private $quickFilters = array();
    private $filters = array();
    private $dateFilters = array();
    private $footer = null;
    
    private $dataNormalizers = array();
    private $exportNormalizers = array();
    private $preAggregators = array();
    private $postAggregators = array();
    private $rowLinkGetter = null;
    private $rowFilter = null;
    
    public function add($identifier, $class, $options)
    {
        if(isset($this->columns[$identifier])) {
            throw new \Exception('A column with identifier ' . $identifier . ' already exists in this table');
        }
        
        $column = (clone ($this->columnLocator->has($class) ? $this->columnLocator->get($class) : new $class()))
            ->setIdentifier($identifier)
            ->setOptions($options);
        
        $this->columns[$identifier] = $column;
        
        return $this;
    }
    
    public function addBefore($source, $identifier, $class, $options)
    {
        if(isset($this->columns[$identifier])) {
            throw new \Exception('A column with identifier ' . $identifier . ' already exists in this table');
        }
        if(!isset($this->columns[$source])) {
            throw new \Exception('Column with identifier ' . $identifier . ' doesn\'t exist in this table');
        }
        
        $column = (clone ($this->columnLocator->has($class) ? $this->columnLocator->get($class) : new $class()))
            ->setIdentifier($identifier)
            ->setOptions($options);
        
        $index = array_search($source, array_keys($this->columns));
        
        $this->columns = array_slice($this->columns, 0, $index, true) +
            array($identifier => $column) +
            array_slice($this->columns, $index, NULL, true);
        
        return $this;
    }
    
    public function addAfter($source, $identifier, $class, $options)
    {
        if(isset($this->columns[$identifier])) {
            throw new \Exception('A column with identifier ' . $identifier . ' already exists in this table');
        }
        if(!isset($this->columns[$source])) {
            throw new \Exception('Column with identifier ' . $identifier . ' doesn\'t exist in this table');
        }
        
        $column = (clone ($this->columnLocator->has($class) ? $this->columnLocator->get($class) : new $class()))
            ->setIdentifier($identifier)
            ->setOptions($options);
        
        $index = array_search($source, array_keys($this->columns)) + 1;
        
        $this->columns = array_slice($this->columns, 0, $index, true) +
            array($identifier => $column) +
            array_slice($this->columns, $index, NULL, true);
        
        return $this;
    }
    
    /**
     * @deprecated
     */
    public function addButton($column, $identifier, $class, $options)
    {
        if(!isset($this->columns[$column])) {
            throw new \Exception('Button identifier should match an existing column, got ' . $column . ' instead');
        }
        $this->columns[$column]->addButton($identifier, new $class($options));
        
        return $this;
    }
    
    public function addQuickFilter($identifier, $definition)
    {
        $this->quickFilters[$identifier] = $definition;
        
        return $this;
    }
    
    public function addFilter($identifier, $class, $definition)
    {
        $definition['filterOnly'] = true;
        return $this->add($identifier, $class, $definition);
    }
    
    public function addDateFilter($identifier, $definition)
    {
        $this->dateFilters[$identifier] = $definition;
        
        return $this;
    }
    
    public function addNormalizer(callable $normalizer, $export = false)
    {
        $this->dataNormalizers[] = $normalizer;
        
        if($export) {
            $this->exportNormalizers[] = $normalizer;
        }
        
        return $this;
    }
    
    public function addRowLink(callable $rowLinkGetter)
    {
        $this->rowLinkGetter = $rowLinkGetter;
        
        return $this;
    }
    
    public function addRowFilter(callable $rowFilter)
    {
        $this->rowFilter = $rowFilter;
        
        return $this;
    }
    
    public function addExportNormalizer(callable $normalizer)
    {
        $this->exportNormalizers[] = $normalizer;
        
        return $this;
    }
    
    public function addAggregator(callable $aggregator, $postFiltering = false)
    {
        if($postFiltering) {
            $this->postAggregators[] = $aggregator;
        } else {
            $this->preAggregators[] = $aggregator;
        }
        
        return $this;
    }
    
    public function addFooterButton($identifier, $type, $icon, $label, $priority, $options = array())
    {
        if($this->footer === null) {
            $this->footer = new Footer();
        }
        
        $this->footer->addButton($type, $icon, $label, $priority, array_merge(array('identifier' => $identifier), $options));
    }
    
    public function addFooterButtonGroup($priority)
    {
        if($this->footer === null) {
            $this->footer = new Footer();
        }
        
        return $this->footer->addGroup($priority);
    }
    
    public function resetBuild()
    {
        $this->columns = array();
        $this->quickFilters = array();
        $this->dateFilters = array();
        $this->dataNormalizers = [];
        $this->rowLinkGetter = null;
        $this->exportNormalizers = [];
        $this->postAggregators = [];
        $this->preAggregators = [];
        $this->footer = null;
    }
    
    public function setParams(array $params)
    {
        $params = array_merge($this->params, $params);
        $resolver = new OptionsResolver();
        $this->configureParams($resolver);

        $this->params = $resolver->resolve($params);
        
        return $this;
    }
    
    public function setPermissions(array $permissions)
    {
        $this->permissions = $permissions;
        
        return $this;
    }
    
    public function getPermissions()
    {
        return $this->permissions;
    }
    
    public function configureParams(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'sortable' => true,
            'filterable' => true,
            'filterPlaceholder' => null,
            'pagination' => true,
            'pagination_total' => true,
            'sidebar' => false,
            'selection' => 'single',
            'changeset' => false,
            'documentsUrl' => null,
            'sidebarUrl' => null,
            'sidebarLastSeen' => null,
            'responsive' => false,
            'showDetails' => true,
            'tableClassName' => null,
            'exportDisabled' => false,
            'paginatorFetchJoin' => true,
            'forcePaginator' => false,
        ));
    }
    
    public function getParams()
    {
        return $this->params;
    }
    
    public function serializeFooter()
    {
        if($this->footer === null) {
            return null;
        }
        
        return $this->footer->toArray();
    }
    
    public function getFilterPlaceholders()
    {
        return array_filter(array_map(function($column) {
            return $column->getFilterPlaceholder();
        }, $this->columns));
    }
    
    public function serializeParams()
    {
        return array(
            'sortable' => $this->params['sortable'],
            'pagination' => $this->params['pagination'],
            'pagination_total' => $this->params['pagination_total'],
            'filterable' => $this->params['filterable'],
            'sidebar' => $this->params['sidebar'],
            'selection' => $this->params['selection'],
            'date_filter' => $this->serializeDateFilters(),
            'documentsUrl' => $this->params['documentsUrl'],
            'sidebarUrl' => $this->params['sidebarUrl'],
            'sidebarLastSeen' => $this->params['sidebarLastSeen'],
            'responsive' => $this->params['responsive'],
            'showDetails' => $this->params['showDetails'],
            'tableClassName' => $this->params['tableClassName'],
            'export_disabled' => $this->params['exportDisabled'],
        );
    }
    
    public function getOrderedColumns($pagination)
    {
        $header = array();
        
        foreach($pagination['header'] as $definition) {
            if(!isset($this->columns[$definition['identifier']])) {
                continue;
            }
            $column = $this->columns[$definition['identifier']];
            $column->setDisplay((bool)$definition['display']);
            $header[$definition['identifier']] = $column;
        }
        
        foreach($this->columns as $identifier => $column) {
            if(isset($header[$identifier]) || !$column->getDisplayable()) {
                continue;
            }
            $header[$identifier] = $column;
        }
        
        return array_values($header);
    }
    
    public function getExportColumns($pagination)
    {
        return array_values(array_filter($this->getOrderedColumns($pagination), function($column) {
            return $column->getDisplayable() && $column->getDisplay(true);
        }));
    }
    
    public function getImportColumns()
    {
        return array_values(array_filter($this->columns, function(Column $column) {
            return $column->supportsImport();
        }));
    }
    
    public function serializeHeader($pagination)
    {
        return array_map(function($column) {
            return $column->serialize();
        }, $this->getOrderedColumns($pagination));
    }
    
    public function filterData(&$queryBuilder, $filters)
    {
        $_filters = [];
        foreach($filters as $filter) {
            foreach($_filters as $key => $_filter) {
                if(isset($_filter['column']) && isset($filter['column']) && $_filter['column'] === $filter['column'] && isset($filter['multiple'])) {
                    if(!is_array($_filter['val'])) {
                        $_filters[$key]['val'] = [$_filter['val']];
                    }
                    $_filters[$key]['val'][] =  $filter['val'];
                    continue 2;
                }
            }
            $_filters[] = $filter;
        }
        
        foreach($_filters as $_filter) {
            if(isset($_filter['range'])) {
                // TODO Reimplement date-range filter
                if(!isset($this->dateFilters[$_filter['range']])) {
                    continue;
                }
                if(!isset($_filter['start'])) {
                    switch($_filter['val']) {
                        case 'this_day':
                            $start = new \DateTime();
                            $end = new \DateTime();
                            break;
                        case 'this_week':
                            $start = new \DateTime('monday this week');
                            $end = new \DateTime('sunday this week');
                            break;
                        case 'this_month':
                            $start = new \DateTime(date('Y-m-01'));
                            $end = new \DateTime(date('Y-m-t'));
                            break;
                        case 'this_year':
                            $start = new \DateTime(date('Y-01-01'));
                            $end = new \DateTime(date('Y-12-31'));
                            break;
                        case 'last_day':
                            $start = new \DateTime('yesterday');
                            $end = new \DateTime('yesterday');
                            break;
                        case 'last_week':
                            $start = new \DateTime('monday last week');
                            $end = new \DateTime('sunday last week');
                            break;
                        case 'last_month':
                            $date = new \DateTime('last month');
                            $start = new \DateTime($date->format('Y-m-01'));
                            $end = new \DateTime($date->format('Y-m-t'));
                            break;
                        case 'last_year':
                            $date = new \DateTime('last year');
                            $start = new \DateTime($date->format('Y-01-01'));
                            $end = new \DateTime($date->format('Y-12-31'));
                            break;
                        case 'next_day':
                            $start = new \DateTime('tomorrow');
                            $end = new \DateTime('tomorrow');
                            break;
                        case 'next_week':
                            $start = new \DateTime('monday next week');
                            $end = new \DateTime('sunday next week');
                            break;
                        case 'next_month':
                            $date = new \DateTime('next month');
                            $start = new \DateTime($date->format('Y-m-01'));
                            $end = new \DateTime($date->format('Y-m-t'));
                            break;
                        case 'next_year':
                            $date = new \DateTime('next year');
                            $start = new \DateTime($date->format('Y-01-01'));
                            $end = new \DateTime($date->format('Y-12-31'));
                            break;
                    }
                } else {
                    $start = new \DateTime($_filter['start']);
                    $end = new \DateTime($_filter['end']);
                }
                $this->dateFilters[$_filter['range']]['filter']($queryBuilder, $start, $end);
            } elseif(isset($_filter['quick'])) {
                if(!isset($this->quickFilters[$_filter['quick']])) {
                    continue;
                }
                $this->quickFilters[$_filter['quick']]['filter']($queryBuilder);
            } elseif(isset($this->columns[$_filter['column']])) {
                if($this->filterLocator->has($_filter['class'])) {
                    $filter = $this->filterLocator->get($_filter['class']);
                } elseif(class_exists($_filter['class'])) {
                    $filter = new $_filter['class']();
                } else {
                    continue;
                }
                
                $column = $this->columns[$_filter['column']];
                $column->addFilterJoins($queryBuilder);
                
                (clone $filter)
                    ->setColumn($column)
                    ->setValue($_filter['val'])
                    ->apply($queryBuilder);
            }
        }
    }
    
    private function sortQueryBuilder($queryBuilder, $pagination)
    {
        if($pagination['sort'] && isset($this->columns[$pagination['sort']])) {
            $column = $this->columns[$pagination['sort']];
            if(!$column->isSortable()) {
                return;
            }
            $selector = $column->getSortSelector($pagination['sortOption']);
            
            // [T5180#111540] Avoid missing selector when queryBuilder is reused with overriden select part
            if($selector[0] === '_' && !in_array(true, array_map(function($part) use($selector) {
                return strpos(implode(', ', $part->getParts()), $selector) !== false;
            }, $queryBuilder->getDQLPart('select')), true)) {
                return;
            }
            
            $queryBuilder->orderBy($selector, $pagination['order']);
        }
    }
    
    public function serializeData($queryBuilder, &$pagination)
    {
        if($this->params['pagination']) {
            $queryBuilder
                ->setFirstResult(($pagination['page'] - 1) * $pagination['count'])
                ->setMaxResults($pagination['count']);
        }
        
        $this->sortQueryBuilder($queryBuilder, $pagination);
        
        $query = $queryBuilder->getQuery();
        
        if($this->params['pagination'] && $this->params['pagination_total']) {
            $paginator = new Paginator($query, $this->params['paginatorFetchJoin'] ?? true);
            
            $pagination['total'] = $paginator->count();
            if($pagination['total'] < ($pagination['page'] - 1) * $pagination['count']) {
                $pagination['page'] = 1;
                return $this->serializeData($queryBuilder, $pagination);
            }
        }

        if($this->params['forcePaginator']) {
            $results = $paginator;
        } else {
            $results = $query->getResult();
        }
        
        
        $data = [];
        
        foreach($results as $i => $entry) {
            $row = new Row($entry);
            
            if($this->rowFilter !== null && !call_user_func($this->rowFilter, $row)) {
                continue;
            }
            
            foreach($this->columns as $column) {
                if(!$column->getDisplayable()) {
                    continue;
                }
                $column->getRowData($row);
            }
            
            foreach($this->dataNormalizers as $normalizer) {
                call_user_func($normalizer, $row, false, $queryBuilder);
            }
            
            if($this->rowLinkGetter !== null) {
                $row->addLink('row', call_user_func($this->rowLinkGetter, $row));
            }
            
            $data[] = $row->serialize() + array('__index' => $i);
        }
        
        return $data;
    }
    
    public function serializeAggregatedData($queryBuilder, $postFiltering = false, $_data = null)
    {
        $data = array();
        if($postFiltering) {
            foreach($this->postAggregators as $aggregator) {
                $aggregator($queryBuilder, $data, $_data);
            }
        } else {
            foreach($this->preAggregators as $aggregator) {
                $aggregator($queryBuilder, $data, $_data);
            }
        }
        
        return $data;
    }
    
    public function serializeFilters(string $query)
    {
        $filters = [];
        
        foreach($this->columns as $column) {
            $filters = array_merge($filters, $column->getFilters($query));
        }
        
        return $filters;
    }
    
    public function serializeQuickFilters()
    {
        $filters = array();
        foreach($this->quickFilters as $identifier => $filter) {
            $filters[$identifier] = array(
                'title' => isset($filter['title']) ? $filter['title'] : $filter['label'],
                'label' => isset($filter['label']) ? $filter['label'] : $filter['title'],
                'quick' => $identifier,
                'excludes' => isset($filter['excludes']) ? $filter['excludes'] : [],
                'value' => 'quick-' . $identifier,
                'imgContent' => $filter['imgContent'] ?? null,
                'className' => $filter['className'] ?? null,
                'img' => $filter['img'] ?? null,
                'icon' => $filter['icon'] ?? null,
            );
        }
        
        return $filters;
    }
    
    public function serializeDefaultQuickFilters()
    {
        $filters = array();
        foreach($this->quickFilters as $identifier => $filter) {
            if(!isset($filter['default']) || !$filter['default']) {
                continue;
            }
            $filters[] = array(
                'title' => isset($filter['title']) ? $filter['title'] : $filter['label'],
                'label' => isset($filter['label']) ? $filter['label'] : $filter['title'],
                'quick' => $identifier,
                'excludes' => isset($filter['excludes']) ? $filter['excludes'] : [],
            );
        }
        
        return $filters;
    }
    
    public function serializeDateFilters()
    {
        $dateFilters = [];
        foreach($this->dateFilters as $identifier => $filter) {
            $dateFilters[$identifier] = array(
                'title' => $filter['title'],
                'key' => $identifier,
                'options' => isset($filter['periods']) ? $filter['periods'] : [],
            );
        }
        
        return $dateFilters;
    }
    
    public function export($queryBuilder, $pagination, $options = array())
    {
        if(!class_exists(WriterEntityFactory::class)) {
            throw new \Exception(strtr('Class %class% is missing. To export table, you need to add %package% as a dependency.', array('%class%' => WriterEntityFactory::class, '%package%' => 'openspout/openspout')));
        }
        
        $options = array_merge(array(
            'format' => 'xlsx',
            'batchCallback' => null,
            'file' => null,
            'noHeader' => false,
            'enclosure' => '"',
            'delimiter' => ',',
        ), $options);
        
        switch($options['format']) {
            case 'xlsx':
                $writer = WriterEntityFactory::createXLSXWriter();
                break;
            case 'ods':
                $writer = WriterEntityFactory::createODSWriter();
                break;
            case 'csv':
            default:
                $writer = WriterEntityFactory::createCSVWriter();
                $writer->setFieldEnclosure($options['enclosure']);
                $writer->setFieldDelimiter($options['delimiter']);
                break;
        }
        
        $file = $options['file'] ?? tempnam(sys_get_temp_dir(), 'table_export_');
        
        $writer->openToFile($file);
        
        $this->sortQueryBuilder($queryBuilder, $pagination);
        
        $columns = $this->getExportColumns($pagination);
        
        if(!$options['noHeader']) {
            $firstRow = array();
            foreach($columns as $column) {
                $firstRow[$column->getIdentifier()] = $column->getTitle();
            }
            
            $row = WriterEntityFactory::createRowFromArray($firstRow);
            $writer->addRow($row);
        }
        
        $this->filterData($queryBuilder, $pagination['filters'] ?: []);
        
        if(isset($this->params['pagination']) && !$this->params['pagination']) {
            $result = $queryBuilder
                ->getQuery()
                ->getResult();
                
            foreach($result as $entry) {
                $row = new Row($entry);
                
                foreach($columns as $column) {
                    $column->getExportData($row, $options['format']);
                }
                
                foreach($this->exportNormalizers as $normalizer) {
                    call_user_func($normalizer, $row, $options['format']);
                }
                
                $rowData = $row->serializeForExport();
                
                foreach($rowData as $entry) {
                    $row = WriterEntityFactory::createRow($entry);
                    $writer->addRow($row);
                }
            }
            
            $this->em->clear();
        } else {
            $total = (new Paginator($queryBuilder, $this->params['paginatorFetchJoin'] ?? true))->count();
            $start = 0;
            $step = 100;
            
            while($start <= $total) {
                $query = $queryBuilder
                    ->setFirstResult($start)
                    ->setMaxResults($step)
                    ->getQuery();
                    
                $result = new Paginator($query, $this->params['paginatorFetchJoin'] ?? true);
                foreach($result as $entry) {
                    $row = new Row($entry);
                    
                    foreach($columns as $column) {
                        $column->getExportData($row, $options['format']);
                    }
                    
                    foreach($this->exportNormalizers as $normalizer) {
                        call_user_func($normalizer, $row, $options['format']);
                    }
                    
                    $rowData = $row->serializeForExport();
                    
                    foreach($rowData as $entry) {
                        $row = WriterEntityFactory::createRow($entry);
                        $writer->addRow($row);
                    }
                }
                $this->em->clear();
                
                $start += $step;
                
                if($options['batchCallback']) {
                    call_user_func($options['batchCallback'], $start, $total);
                }
            }
        }
        
        $writer->close();
        
        return $file;
    }
    
    public function getNavigation($id, $queryBuilder, $pagination)
    {
        if($id === null) {
            return array('prev' => null, 'next' => null);
        }
        $this->sortQueryBuilder($queryBuilder, $pagination);
        
        $this->filterData($queryBuilder, $pagination['filters'] ?: []);
        
        $orderStatements = $queryBuilder->getDQLPart('orderBy');
        if(empty($orderStatements)) {
            $order = 'ASC';
            $orderCriteria = 'o.id';
        } else {
            $orderStatement = explode(' ', $orderStatements[0]);
            $order = array_pop($orderStatement);
            $orderCriteria = trim(implode(' ', $orderStatement));
        }
        
        try {
            $criteriaValue = (clone $queryBuilder)
                ->select($orderCriteria)
                ->andWhere('o.id = :nav_id')->setParameter('nav_id', $id)
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult()
            ;
        } catch(\Exception $e) {
            // NOTE $criteriaValue is impossible to find if the document is not in the
            return array('prev' => null, 'next' => null);
        }
        
        $countTotal = (new Paginator($queryBuilder, $this->params['paginatorFetchJoin'] ?? true))->count();
        
        if($order === 'DESC') {
            $countBefore = (new Paginator((clone $queryBuilder)
                ->andWhere($orderCriteria . ' > :nav_id')
                ->setParameter('nav_id', $criteriaValue), $this->params['paginatorFetchJoin'] ?? true))->count();
            
            try {
                $prevId = (clone $queryBuilder)
                    ->select('o.id')
                    ->andWhere($orderCriteria . ' > :nav_id')->setParameter('nav_id', $criteriaValue)
                    ->setFirstResult($countBefore - 1)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getSingleScalarResult()
                ;
            } catch(\Exception $e) {
                $prevId = null;
            }
            try {
                $nextId = (clone $queryBuilder)
                    ->select('o.id')
                    ->andWhere($orderCriteria . ' < :nav_id')->setParameter('nav_id', $criteriaValue)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getSingleScalarResult()
                ;
            } catch(\Exception $e) {
                $nextId = null;
            }
        } else {
            $countBefore = (new Paginator((clone $queryBuilder)
                ->andWhere($orderCriteria . ' < :nav_id')
                ->setParameter('nav_id', $criteriaValue), $this->params['paginatorFetchJoin'] ?? true))->count();
            
            try {
                $nextId = (clone $queryBuilder)
                    ->select('o.id')
                    ->andWhere($orderCriteria . ' > :nav_id')->setParameter('nav_id', $criteriaValue)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getSingleScalarResult()
                ;
            } catch(\Exception $e) {
                $nextId = null;
            }
            try {
                $prevId = (clone $queryBuilder)
                    ->select('o.id')
                    ->andWhere($orderCriteria . ' < :nav_id')->setParameter('nav_id', $criteriaValue)
                    ->setFirstResult($countBefore - 1)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getSingleScalarResult()
                ;
            } catch(\Exception $e) {
                $prevId = null;
            }
        }
        
        return array(
            'prev' => $prevId,
            'next' => $nextId,
            'count' => $countTotal,
            'position' => $countBefore + 1,
        );
    }
    
    public function getSelection($queryBuilder, $pagination, $property = 'DISTINCT o.id')
    {
        $queryBuilder->select($property);
        
        $this->sortQueryBuilder($queryBuilder, $pagination);
        
        $this->filterData($queryBuilder, $pagination['filters'] ?: []);
        
        return $queryBuilder->getQuery()->getResult('id_hydrator');
    }
}
