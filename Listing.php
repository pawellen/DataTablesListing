<?php

/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 11:29
 */

namespace PawelLen\DataTablesListing;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PawelLen\DataTablesListing\Column\Type\ListingColumnTypeInterface;
use PawelLen\DataTablesListing\Renderer\ListingRendererInterface;
use PawelLen\DataTablesListing\Filter\Type\ListingFilter;
use PawelLen\DataTablesListing\Column\Columns;
use PawelLen\DataTablesListing\Filter\Filters;


class Listing
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var Columns
     */
    protected $columns;

    /**
     * @var Filters
     */
    protected $filters;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ListingRendererInterface
     */
    protected $renderer;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var int
     */
    protected $allResultsCount;

    /**
     * @var int
     */
    protected $firstResultsOffset;



    /**
     * @param string $name
     * @param Columns $columns
     * @param Filters $filters
     * @param RegistryInterface $registry
     * @param EventDispatcherInterface $eventDispatcher
     * @param ListingRendererInterface $renderer
     * @param array $options
     */
    public function __construct($name, Columns $columns, Filters $filters, RegistryInterface $registry, EventDispatcherInterface $eventDispatcher, ListingRendererInterface $renderer, array $options = array())
    {
        $this->name = $name;
        $this->columns = $columns;
        $this->filters = $filters;
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
        $this->renderer = $renderer;
        $this->options = $options;
    }


    /**
     * @return ListingView
     */
    public function createView()
    {
        $listingView = new ListingView(
            $this->name,
            $this->columns,
            $this->filters,
            $this->options,
            $this->getInitialData()
        );

        return $listingView;
    }


    /**
     * @param Request $overrideRequest
     * @return JsonResponse
     * @throws \Exception
     */
    public function createResponse(Request $overrideRequest = null)
    {
        if (isset($this->options['data'])) {
            if (!is_array($this->options['data'])) {
                throw new \Exception('Parameter data must be an array');
            }
            $data = $this->options['data'];
        } else {
            $request = $overrideRequest ?: $this->options['request'];

            $parameters = array_merge($request->query->all(), $request->request->all());
            $filters = array();
            if (isset($parameters['_filter']) && is_array($parameters['_filter'])) {
                $filters = $parameters['_filter'];
                unset($parameters['_filter']);
            }
            $data = $this->loadData($parameters, $filters);
            $data = $this->processData($data);
        }

        $result = $this->createDataTablesResult($data);
        $response = new JsonResponse($result);

        return $response;
    }


    /**
     * @return array
     * @throws \Exception
     */
    protected function getInitialData()
    {
        $parameters = array_merge($this->options['request']->query->all(), $this->options['request']->request->all());

        $filters = array();
        if (isset($parameters['_filter']) && is_array($parameters['_filter'])) {
            $filters = $parameters['_filter'];
            unset($parameters['_filter']);
        }

        if (!isset($parameters['length']) || $parameters['length'] < 1) {
            $parameters['length'] = $this->options['page_length'];
        }

        $data = $this->loadData($parameters, $filters);
        $data = $this->processData($data);

        return $data;
    }


    /**
     * @param array $parameters
     * @param array $filters
     * @return array
     * @throws \Exception
     */
    protected function loadData(array $parameters, array $filters = array())
    {
        // Load DataTables parameters:
        $limit                  = isset($parameters['length']) && $parameters['length']>0 ? (int)$parameters['length'] : 0;
        $offset                 = isset($parameters['start'])  && $parameters['start']>0  ? (int)$parameters['start']  : 0;
        $orderColumnDefinitions = isset($parameters['order']) && is_array($parameters['order']) ? $parameters['order'] : null;

        // Create QueryBuilder:
        $queryBuilder = $this->createQueryBuilder();

        // Filters:
        $this->applyFilters($queryBuilder, $filters);

        // Sorting:
        $this->applySorting($queryBuilder, $orderColumnDefinitions);

        // Pagination:
        if ($limit > 0) {
            $queryBuilder->setFirstResult($offset);
            $queryBuilder->setMaxResults($limit);
        }

        // Execute query using paginator:
        $paginator = new Paginator($queryBuilder->getQuery(), true);
        $this->firstResultsOffset = $limit;
        $this->allResultsCount = count($paginator);
        $processRowCallback = isset($this->options['process_row_callback']) && is_callable($this->options['process_row_callback']);

        // Fill data array:
        $data = array();
        foreach ($paginator as $row) {
            $data[] = $processRowCallback ? $this->options['process_row_callback']($row) : $row;
        }

        // Process result event:
        if (isset($this->options['process_result_callback']) && is_callable($this->options['process_result_callback'])) {
            $data = $this->options['process_result_callback']($data);
        }

        return $data;
    }


    /**
     * @param $data
     * @return array
     * @throws \Exception
     */
    protected function processData($data)
    {
        if (!is_array($data) && !$data instanceof \Traversable) {
            throw new \Exception('Unable to process data, query result is not traversable.');
        }

        // Load renderer template:
        $this->renderer->load(isset($this->options['template']) ? $this->options['template'] : null);

        $table = array();
        foreach ($data as $row) {
            $tr = array();
            /** @var ListingColumnTypeInterface $column */
            foreach ($this->columns as $column) {
                $tr[] = $this->renderer->renderCell($column, $row);
            }
            $table[] = $tr;
        }

        return $table;
    }


    /**
     * @param $data
     * @return array
     */
    protected function createDataTablesResult($data)
    {
        $result = array(
            'sEcho' => 0,
            'iTotalRecords' => $this->allResultsCount,
            'iTotalDisplayRecords' => $this->allResultsCount,
            'data' => $data,
        );

        return $result;
    }


    /**
     * @param QueryBuilder $queryBuilder
     * @param array $filters
     * @throws \Exception
     */
    protected function applyFilters(QueryBuilder $queryBuilder, array $filters)
    {
        foreach ($filters as $name => $value) {
            if (!isset($this->filters[$name])) {
                continue;
            }

            /** @var ListingFilter $filter */
            $filter = $this->filters[$name];
            $options = $filter->getOptions();
            $value = $this->transformFilterValue($filter, $value);

            // Pass QueryBuilder to modify query for this filter
            if (isset($options['query_builder'])) {
                if ($options['query_builder'] instanceof \Closure) {
                    $options['query_builder']($queryBuilder, $value);
                } else {
                    throw new \Exception('Exception in filter "' . $filter->getName() . ', "query_builder" must be instance of \Closure.');
                }

            } elseif (isset($options['expression'])) {
                $expression = $options['expression'];
                $parameters_count = substr_count($expression, '?');
                if ($parameters_count > 0) {
                    for ($i = 0; $i < $parameters_count; $i++) {
                        $expression = preg_replace('/\?/', ':arg_'.$i, $expression, 1);
                        $queryBuilder->setParameter(':arg_'.$i, $value);
                    }
                    $queryBuilder->andWhere($expression);
                }
            } else {
                $field = $this->getRootAliasFieldName($queryBuilder, $filter->getName());
                if ($filter->getFormBuilder()->getType() == 'entity') {
                    $queryBuilder->andWhere($field . ' = :arg_id');
                } else {
                    $queryBuilder->andWhere($field . ' LIKE :arg_id');
                }
                $queryBuilder->setParameter(':arg_id', '%' . $value . '%');
            }
        }
    }


    /**
     * @param QueryBuilder $queryBuilder
     * @param null $orderColumnDefinitions
     * @throws \Exception
     */
    protected function applySorting(QueryBuilder $queryBuilder, $orderColumnDefinitions = null)
    {
        if ($orderColumnDefinitions) {
            foreach ($orderColumnDefinitions as $orderColumnDef) {
                $orderColumn = $this->columns->getByIndex($orderColumnDef['column']);
                if ($orderColumn instanceof ListingColumnTypeInterface && $orderColumn->isSortable()) {
                    $options = $orderColumn->getOptions();
                    if (isset($options['order_by'])) {
                        $orderProperty = $options['order_by'];
                    } else {
                        $orderProperty = $this->getRootAliasFieldName($queryBuilder, $orderColumn->getName());
                    }
                    $orderDirection = $orderColumnDef['dir'] == 'desc' ? 'DESC' : 'ASC';
                    $queryBuilder->addOrderBy($orderProperty, $orderDirection);
                }

            }
        } elseif (isset($this->options['order_by'])) {
            $orderDirection = isset($this->options['order_direction']) ? $this->options['order_direction'] : 'ASC';
            $queryBuilder->orderBy($this->options['order_by'], $orderDirection);
        }
    }


    /**
     * @param ListingFilter $filter
     * @param $value
     * @return string
     * @throws \Exception
     */
    protected function transformFilterValue(ListingFilter $filter, $value)
    {
        $options = $filter->getOptions();

        // To delete (ensure is compatible with previous version:
        if (isset($options['eval']) && !isset($options['transform']))
            $options['transform'] = $options['eval'];

        if (!isset($options['transform'])) {

            return $value;
        }

        switch ($options['transform']) {
            case '%like%':
                return '%' . $value . '%';

            case 'like%':
                return $value . '%';

            default:
                throw new \Exception('Unsupported transform option "' . $options['eval'] . '" for filter "' . $filter->getName() . '"');
        }
    }


    /**
     * @return QueryBuilder
     * @throws \Exception
     */
    protected function createQueryBuilder()
    {
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
                    $queryBuilder = $this->registry->getEntityManager()->createQueryBuilder();
                    $this->options['query_builder']($queryBuilder);
                }
            } else {
                $queryBuilder = $this->options['query_builder'];
            }
        } else {
            if (isset($this->options['class'])) {
                $queryBuilder = $this->registry->getEntityManager()->createQueryBuilder();
                $queryBuilder->select('q')
                    ->from($this->options['class'], 'q');
            }
        }

        if (!$queryBuilder instanceof QueryBuilder) {
            throw new \Exception('Unable to create query builder, one of options [class, query_builder] is required');
        }

        return $queryBuilder;
    }


    /**
     * @param QueryBuilder $queryBuilder
     * @param $name
     * @return string
     * @throws \Exception
     */
    private function getRootAliasFieldName(QueryBuilder $queryBuilder, $name)
    {
        $rootAliases = $queryBuilder->getRootAliases();
        if (isset($rootAliases[0])) {
            return $rootAliases[0] . '.' . $name;
        }

        throw new \Exception('Unable to get root alias field name for field "' . $name . '", maybe you should add "field" option to this column');
    }

}
