<?php

/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 11:29
 */

namespace PawelLen\DataTablesListing;

use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use PawelLen\DataTablesListing\Event\CreateRowEvent;
use PawelLen\DataTablesListing\Event\SearchCriteriaEvent;


class Listing
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $columns = array();

    /**
     * @var array
     */
    protected $buttons = array();

    /**
     * @var Form
     */
    protected $form;

    /**
     * @var array
     */
    protected $filters = array();

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * DataTables sEcho flag
     *
     * @var int
     */
    protected $sEcho = 0;

    /**
     * Record numeration
     *
     * @var bool
     */
    protected $useNumeration = false;

    /**
     * Offset of first result
     *
     * @var int
     */
    private $firstResultOffset = null;

    /**
     * Number of all results
     *
     * @var int
     */
    private $allResultsCount = null;

    /**
     * @var PropertyAccess
     */
    protected $accessor;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    const propertyAccessorErrorMessage = '';
    const arrayAccessErrorMessage = 'ARRAY-ACCESS-ERROR!';


    /**
     * @param $name
     * @param array $columns
     * @param array $buttons
     * @param array $filters
     * @param Form $form
     * @param array $options
     * @param RegistryInterface $registry
     * @param RouterInterface $router
     */
    public function __construct($name, array $columns, array $buttons, array $filters, Form $form, array $options, RegistryInterface $registry, RouterInterface $router, EventDispatcherInterface $eventDispatcher) {
        $this->name = trim($name);
        $this->columns = $columns;
        $this->buttons = $buttons;
        $this->filters = $filters;
        $this->form = $form;
        $this->options = $options;
        $this->registry = $registry;
        $this->accessor = PropertyAccess::createPropertyAccessor();
        $this->router = $router;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return ListingView
     * @throws \Exception
     */
    public function createView() {
        // Create route for ajax call:
        if (isset($this->options['ajaxsource'])) {
            $ajaxRoute = $this->options['ajaxsource'];
        } elseif (isset($this->options['route'])) {
            $ajaxRoute = $this->router->generate(
                    $this->options['route'], isset($this->options['route_parameters']) ? isset($this->options['route_parameters']) : array()
            );
        } elseif (isset($this->options['request'])) {
            if ($this->options['request'] instanceof Request) {
                $ajaxRoute = $this->options['request']->getRequestUri();
            } else {
                throw new \Exception('Option "request" must be instance of "Symfony\Component\HttpFoundation\Request", "' . get_class($this->options['request']) . '" given');
            }
        } else {
            throw new \Exception('One of options [ajaxsource, route, request] is required, none of them given');
        }


        // Create ListingView:
        $listingView = new ListingView($this->name, $this->columns, $this->buttons, $this->form->createView(), $ajaxRoute);

        return $listingView;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function createResponse(Request $request) {

        if (isset($this->options['data'])) {
            if (!is_array($this->options['data'])) {
                throw new \Exception('Parameter data must be an array');
            }
            $result = $this->options['data'];

        } else {
            // Initialize QueryBuilder:
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = null;
            if (isset($this->options['query_builder'])) {

                // Normalize QueryBuilder:
                if ($this->options['query_builder'] instanceof \Closure) {

                    // If has option class then pass EntityRepository of this class otherwise pass new instance of QueryBuilder:
                    if (isset($this->options['class'])) {
                        $repository = $this->registry->getRepository($this->options['class']);
                        $queryBuilder = $this->options['query_builder']($repository);
                    } else {
                        $queryBuilder = $this->registry->getManager()->createQueryBuilder();
                        $this->options['query_builder']($queryBuilder);
                    }
                } else {
                    $queryBuilder = $this->options['query_builder'];
                }
            } else {
                if (isset($this->options['class'])) {
                    $queryBuilder = $this->registry->getManager()->createQueryBuilder();
                    $queryBuilder->select('q')
                            ->from($this->options['class'], 'q');
                }
            }

            if (!$queryBuilder instanceof QueryBuilder) {
                throw new \Exception('Unable to create query builder, one of options [class, query_builder] is required');
            }

            // Load search parameters from request:
            $params = array_merge($request->query->all(), $request->request->all());
            $searchCriteria = array();
            if (isset($params['filter']) && is_array($params['filter'])) {
                $searchCriteria = $params['filter'];
            }

            // Dispatch event:
            $event = new SearchCriteriaEvent($searchCriteria);
            $this->eventDispatcher->dispatch(SearchCriteriaEvent::SEARCH_CRITERIA, $event);
            $searchCriteria = $event->getSearchCriteria();


            // Set additional params:
            $this->sEcho = isset($params['sEcho']) ? (int) $params['sEcho'] : 0;


            // Apply filters:
            $this->applyFilters($queryBuilder, $searchCriteria);

            // Execute query:
            $result = $this->buildAndExecute($queryBuilder, $params);
        }
        // Process result:
        $arrayResult = $this->processResult($result);

        return new JsonResponse($arrayResult);
    }

    /**
     * Count all records, then builds order by and limit and execute query
     *
     * @param QueryBuilder $queryBuilder
     * @param $params
     * @return array
     */
    protected function buildAndExecute(QueryBuilder $queryBuilder, $params) {
        $columns = array_keys($this->columns);

        // Count records:
        $countQueryBuilder = clone $queryBuilder;
        $countParameters = array();
        foreach ($countQueryBuilder->getParameters() as $param) {
            $countParameters[] = $param->getValue();
        }
        $countQuery = 'SELECT COUNT(*) AS count FROM (' . $countQueryBuilder->getQuery()->getSQL() . ') q';
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('count', 'count');
        $query = $countQueryBuilder->getEntityManager()->createNativeQuery($countQuery, $rsm);
        $query->setParameters($countParameters);
        $this->allResultsCount = (int) $query->getSingleScalarResult();

        // Prepare pagination:
        $limit = isset($params['iDisplayLength']) ? (int) $params['iDisplayLength'] : 0;
        $this->firstResultOffset = $limit && isset($params['iDisplayStart']) && (int) $params['iDisplayStart'] > 0 ? (int) $params['iDisplayStart'] : 0;
        if ($limit > 0) {
            $queryBuilder->setFirstResult($this->firstResultOffset);
            $queryBuilder->setMaxResults($limit);
        }

        // Handle column sorting:
        if (isset($params['iSortCol_0']) && !empty($params['iSortingCols']) && isset($columns[$params['iSortCol_0']])) {
            $sortField = $columns[$params['iSortCol_0']];
            if ($this->isSortable($sortField)) {
                $orderDirection = isset($params['sSortDir_0']) && $params['sSortDir_0'] === 'desc' ? 'desc' : 'asc';
                if (isset($this->columns[$sortField]['field'])) {
                    $queryBuilder->orderBy($this->columns[$sortField]['field'], $orderDirection);
                } else {
                    $queryBuilder->orderBy($this->getRootAliasFieldName($queryBuilder, $sortField), $orderDirection);
                }
            }
        }

        // Execute whole query and return result:
        return $queryBuilder->getQuery()->getResult();
    }

    protected function processResult($result, $parserClosure = null) {
        if (!is_array($result)) {
            throw new \Exception('DataTablesService: Unable to get proper results');
        }

        $data = array();
        $lp = $this->firstResultOffset;
        foreach ($result as $entity) {

            $table_row = array();
            if ($this->useNumeration)
                $table_row[] = ++$lp;

            $isEntity = is_object($entity);
            $index = 0;

            foreach ($this->columns as $name => $options) {

                // Load
                if ($isEntity) {
                    $propertyPath = isset($options['property']) ? $options['property'] : $name;
                    switch (substr_count($propertyPath, '[*]')) {

                        case 0:
                            try {
                                $value = $this->accessor->getValue($entity, $propertyPath);
                            } catch (\Exception $e) {
                                $value = self::propertyAccessorErrorMessage;
                            }
                            break;

                        case 1:
                            $iterator = 0;
                            $values = array();
                            while (1) {
                                try {
                                    $propertyPathIterator =   str_replace('[*]', '[' . $iterator . ']', $propertyPath);
                                    $values[] = $this->accessor->getValue($entity, $propertyPathIterator);
                                } catch (\Exception $e) {
                                    break;
                                }
                                ++$iterator;
                            }
                            $value = implode(', ', $values);
                            break;

                        default:
                            throw new \Exception('Only one wildcard for property is allowed');
                    }

                    // Process value using callback
                    if (isset($options['callback']) && is_callable($options['callback'])) {
                        $value = $options['callback']($value);

                        if ($value === null) {
                            throw new \Exception('Callback should return not null value that will be assigned to value');
                        }
                    }
                } else {

                    $value = isset($entity[$index]) ? $entity[$index] : self::arrayAccessErrorMessage;
                }

                // Normalize value:
                switch (true) {

                    case ($value instanceof \DateTime):
                        if (isset($options['format'])) {
                            $value = $value->format($options['format']);
                        } else {
                            $value = $value->format('d-m-Y H:i:s');
                        }
                        break;

                    case (is_object($value) && method_exists($value, '__toString')):
                        $value = (string) $value;
                        break;
                }

                // Evaluate value:
                if (isset($options['format'])) {
                    switch ($options['format']) {

                        case 'price':
                            if ($value !== '') {
                                $value = number_format($value, 2, ',', ' ');
                            }
                            break;

                        case 'nl2br':
                            $value = nl2br($value);
                            break;

                        // Exclusive :):
                        case 'recordInfo':
                            if (is_array($value) && count($value) > 0) {
                                $tip = '';
                                foreach ($value as $_name => $_val)
                                    $tip .= htmlspecialchars($_name) . ': ' . htmlspecialchars($_val) . "<br/>\n";
                                $value = '<i class="icon-info-sign timeago has-tooltip" title="' . $tip . '" data-html="true" rel="tooltip" data-placement="left"></i>';
                            } else {
                                $value = '';
                            }
                            break;

                        case 'boolean':
                            $value = $value ? 'Tak' : 'Nie';
                            break;
                    }
                }

                // Create link:
                if (isset($options['link'])) {
                    $params = array();
                    if (is_array($options['link']['params'])) {
                        foreach ($options['link']['params'] as $_name => $_propertyAccessor) {
                            if ($this->accessor->isReadable($entity, $_propertyAccessor)) {
                                $params[$_name] = $this->accessor->getValue($entity, $_propertyAccessor);
                            }
                        }
                    }
                    $label = (isset($options['link']['label'])) ? htmlspecialchars($options['link']['label']) : '';
                    $class = (isset($options['link']['class'])) ? $options['link']['class'] : 'btn-inverse';
                    $value = '<a href="' . $this->router->generate($options['link']['route'], $params) . '" class="' . $class . '" title="' . $label . '">' . $value . '</a>';
                }

                $table_row[$propertyPath] = $value;
                $index++;
            }

            if (!empty($this->buttons)) {
                $table_row[] = implode(' ', $this->renderButtons($entity));
            }

            // Run data transformer closure:
            if (is_callable($parserClosure)) {
                $table_row = $parserClosure($table_row, $entity);
                if (!is_array($table_row)) {
                    throw new \Exception('Closure must return array');
                }
            }

            $row = isset($this->dataTableConfig) && $this->dataTableConfig->isAssocResult() ? $table_row : array_values($table_row);

            // Dispatch event:
            $event = new CreateRowEvent($row);
            $this->eventDispatcher->dispatch(CreateRowEvent::CREATE_ROW, $event);
            //var_dump($this->eventDispatcher->getListeners(CreateRowEvent::CREATE_ROW));
            $row = $event->getRow();

            $data[] = $row;
        }


        $result = array(
            'sEcho' => $this->sEcho,
            'iTotalRecords' => $this->allResultsCount,
            'iTotalDisplayRecords' => $this->allResultsCount,
            'aaData' => $data
        );

        return $result;
    }

    private function isSortable($column) {
        if (isset($this->columns[$column])) {

            $options = $this->columns[$column];
            if (!isset($options['sortable']) || $options['sortable'] === true)
                return true;
        }

        return false;
    }

    /**
     * Apply filters array to query builder
     *
     * @param QueryBuilder $queryBuilder
     * @param array $searchCriteria
     * @throws \Exception
     */
    private function applyFilters(QueryBuilder $queryBuilder, array $searchCriteria) {
        foreach ($searchCriteria as $name => $value) {
            if (isset($this->filters[$name])) {
                $cfg = $this->filters[$name];

                // Evaluate value:
                if (isset($cfg['eval'])) {

                    switch ($cfg['eval']) {
                        case '%like%':
                            $value = '%' . $value . '%';
                            break;

                        case 'like%':
                            $value = $value . '%';
                            break;

                        default:
                            throw new \Exception('Unsupported eval parameter "' . $cfg['eval'] . '" for filter "' . $name . '"');
                    }
                }

                // Pass QueryBuilder to modify query for this filter
                if (isset($cfg['query_builder'])) {

                    if ($cfg['query_builder'] instanceof \Closure) {
                        $cfg['query_builder']($queryBuilder);
                    } else {
                        throw new \Exception('Exception in filter "' . $name . ', "query_builder" must be instance of \Closure.');
                    }
                }

                // Add join to query (by Paweł Kołoszko):
                if (isset($cfg['join'])) {
                    // Zakładamy, że można dorzucić więcej niż jeden join do zapytania - sprawdzamy czy przekazano nam jednego czy wiele joinów:
                    $joins = $cfg['join'];
                    if (!isset($joins[0])) {
                        $this->appendJoin($queryBuilder, $joins);
                    } else {
                        foreach ($joins as $join) {
                            $this->appendJoin($queryBuilder, $join);
                        }
                    }
                }


                // Add filter to query:
                if (isset($cfg['expression'])) {
                    // TODO: ensure this solution is safe
                    $where = $cfg['expression'];
                    $paramsCount = substr_count($where, ':' . $name);
                    for ($i = 0; $i < $paramsCount; $i++) {
                        $where = preg_replace("/:$name/", ":ch$i$name", $where, 1);
                        $queryBuilder->setParameter(":ch$i$name", $value);
                    }
                    $queryBuilder->andWhere($where);
                } elseif (isset($cfg['callback'])) {
                    if (is_callable($cfg['callback'])) {
                        $cfg['callback']($queryBuilder, $value);
                    }
                } else {
                    $field = $this->getRootAliasFieldName($queryBuilder, $name);
                    if (isset($cfg['entity'])) {
                        $queryBuilder->andWhere($field . ' = ' . ':' . $name);
                        $queryBuilder->setParameter(':' . $name, $value);
                    } else {
                        $queryBuilder->andWhere($field . ' LIKE ' . ':' . $name);
                        $queryBuilder->setParameter(':' . $name, '%' . $value . '%');
                    }

                }
            }
        }
    }

    protected function renderButtons($entity) {
        $data = array();
        foreach ($this->buttons as $btnName => $options) {

            $params = array();
            if (is_array($options['params'])) {
                foreach ($options['params'] as $name => $propertyAccessor) {

                    $value = $this->accessor->getValue($entity, $propertyAccessor);
                    if ($value !== null) {
                        $params[$name] = $value;
                    }
                }
            }
            
            $label = $options['label'] ? htmlspecialchars($options['label']) : '...';
            $labelOrIcon = \array_key_exists('icon', $options) ? '<i class="' . $options['icon'] . '"></i>' : $label;
            $class = (isset($options['class'])) ? $options['class'] : 'btn-default';

            $data[] = '<a href="' . $this->router->generate($options['route'], $params) . '" class="btn btn-xs ' . $class . '" title="' . $label . '">' . $labelOrIcon . '</a>';
        }

        return $data;
    }


    private function getRootAliasFieldName(QueryBuilder $queryBuilder, $name) {
        $rootAliases = $queryBuilder->getRootAliases();
        if (isset($rootAliases[0])) {
            return $rootAliases[0] . '.' . $name;
        }

        throw new \Exception('Unable to get root alias field name for field "' . $name . '", maybe you should add "field" option to this column');
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param $joinCfg
     */
    private function appendJoin(QueryBuilder $queryBuilder, $joinCfg) {
        if (!$this->checkJoinExists($queryBuilder, $joinCfg)) {
            $queryBuilder->join($joinCfg['field'], $joinCfg['alias']);
        }
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param $joinCfg
     * @return bool
     */
    private function checkJoinExists(QueryBuilder $queryBuilder, $joinCfg) {
        $queryJoins = current($queryBuilder->getDQLParts()['join']);
        foreach ($queryJoins as $queryJoin) {
            if ($queryJoin->getAlias() === $joinCfg['alias'] && $queryJoin->getJoin() === $joinCfg['field']) {
                return true;
            }
        }
        return false;
    }

}
