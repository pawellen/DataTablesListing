<?php

/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 11:29
 */

namespace PawelLen\DataTablesListing;

use PawelLen\DataTablesListing\Column\Type\ListingColumnTypeInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\RouterInterface;
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
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var \Twig_Environment
     */
    protected $environment;

    /**
     * @var string
     */
    protected $defaultTemplate;

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
     * Value when property accessor throws exception
     */
    const valueOnPropertyAccessorException = '!';


    /**
     * @param string $name
     * @param Columns $columns
     * @param Filters $filters
     * @param RegistryInterface $registry
     * @param RouterInterface $router
     * @param EventDispatcherInterface $eventDispatcher
     * @param \Twig_Environment $environment
     * @param array $options
     */
    public function __construct($name, Columns $columns, Filters $filters, RegistryInterface $registry, RouterInterface $router, EventDispatcherInterface $eventDispatcher, \Twig_Environment  $environment, $defaultTemplate, array $options = array())
    {
        $this->name = $name;
        $this->columns = $columns;
        $this->filters = $filters;
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
        $this->router = $router;
        $this->environment = $environment;
        $this->defaultTemplate = $defaultTemplate;
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
     * @return array
     */
    protected function loadTemplateAndBlocks()
    {
        /** @var \Twig_Template $template */
        $template = $this->environment->loadTemplate($this->defaultTemplate);

        // Override template blocks:
        $blocks = array();
        if (isset($this->options['template'])) {
            /** @var \Twig_Template $childTemplate */
            $childTemplate = $this->environment->loadTemplate($this->options['template']);
            $blocks = $childTemplate->getBlocks();
        }

        return array(
            'template' => $template,
            'blocks' => $blocks
        );
    }


    /**
     * @param $data
     * @return array
     * @throws \Exception
     */
    protected function processData($data)
    {
        if (!is_array($data) && !$data instanceof \Traversable) {
            throw new \Exception('convertToDataTablesFormat: Unable to get convert result, result is not traversable.');
        }
        $templateAndBlocks = $this->loadTemplateAndBlocks();

        $processed = array();
        foreach ($data as $result) {
            $row = array();
            /** @var ListingColumnTypeInterface $column */
            foreach ($this->columns as $column) {
                $options = $column->getOptions();
                $property = isset($options['property']) ? $options['property'] : $column->getName();
                $value = $this->getPropertyValue($result, $property);

                // Process value using callback:
                if (isset($options['callback']) && is_callable($options['callback'])) {
                    $value = $options['callback']($value, $result, $column);
                }
                $value = $this->normalizeValue($value, $options);
                $value = $this->transformValue($value, $result, $options);

                // Render cell value:
                $cellValue = $templateAndBlocks['template']->renderBlock('listing_column', array(
                    'column' => $column,
                    'value' => $value
                ), $templateAndBlocks['blocks']);

                $row[] = trim($cellValue);
            }
            $processed[] = $row;
        }

        return $processed;
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
     * @param $value
     * @param array $options
     * @return string
     */
    protected function normalizeValue($value, array $options = array())
    {
        switch (true) {
            case ($value instanceof \DateTime):
                $value = $value->format($options['date_format']);
                break;

            case (is_object($value) && method_exists($value, '__toString')):
                $value = (string)$value;
                break;

        }

        return $value;
    }


    /**
     * @param $value
     * @param array $options
     * @return string
     */
    protected function transformValue($value, $row, array $options = array())
    {
        if (isset($options['route'])) {
            $parameters = array();
            if (isset($options['route_parameters'])) {
                $propertyAccessor = PropertyAccess::createPropertyAccessor();
                foreach ($options['route_parameters'] as $_name => $_propertyPath) {
                    $parameters[$_name] = $propertyAccessor->getValue($row, $_propertyPath);
                }
            }
            $url = $this->router->generate($options['route'], $parameters);
            $value = '<a href="' . $url . '">' . htmlspecialchars($value) . '</a>';
        }

        return $value;
    }


    /**
     * @param $data
     * @param $propertyPath
     * @return string
     * @throws \Exception
     */
    protected function getPropertyValue($data, $propertyPath)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        switch (substr_count($propertyPath, '[*]')) {
            case 0:
                try {
                    $value = $propertyAccessor->getValue($data, $propertyPath);
                } catch (\Exception $e) {
                    $value = self::valueOnPropertyAccessorException;
                }
                break;

            case 1:
                $iterator = 0;
                $values = array();
                while (1) {
                    try {
                        $propertyPathIterator = str_replace('[*]', '[' . $iterator . ']', $propertyPath);
                        $values[] = $propertyAccessor->getValue($data, $propertyPathIterator);
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

        return $value;
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
        $orderColumnDefs     = isset($parameters['order']) && is_array($parameters['order']) ? $parameters['order'] : null;
        $limit              = isset($parameters['length']) && $parameters['length']>0 ? (int)$parameters['length'] : 0;
        $offset             = isset($parameters['start'])  && $parameters['start']>0  ? (int)$parameters['start']  : 0;

        // Create QueryBuilder:
        $queryBuilder = $this->createQueryBuilder();

        // Filters:
        $this->applyFilters($queryBuilder, $filters);

        // Sorting:
        if ($orderColumnDefs) {
            foreach ($orderColumnDefs as $orderColumnDef) {
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

        // Pagination:
        if ($limit > 0) {
            $queryBuilder->setFirstResult($offset);
            $queryBuilder->setMaxResults($limit);
        }

        // Execute query using paginator:
        $data = array();
        $paginator = new Paginator($queryBuilder->getQuery(), $fetchJoin = true);
        $this->firstResultsOffset = $limit;
        $this->allResultsCount = count($paginator);
        foreach ($paginator as $row) {
            if (isset($this->options['process_row_callback']) && is_callable($this->options['process_row_callback'])) {
                $row = $this->options['process_row_callback']($row);
            }
            $data[] = $row;
        }

        // Process result event:
        if (isset($this->options['process_result_callback']) && is_callable($this->options['process_result_callback'])) {
            $data = $this->options['process_result_callback']($data);
        }

        return $data;
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
