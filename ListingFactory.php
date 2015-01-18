<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 11:36
 */

namespace PawelLen\DataTablesListing;

use PawelLen\DataTablesListing\Renderer\ListingRendererInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use PawelLen\DataTablesListing\Column\ColumnBuilder;
use PawelLen\DataTablesListing\Type\ListingTypeInterface;
use PawelLen\DataTablesListing\Filter\FilterBuilder;


class ListingFactory
{
    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * \Twig_Environment  $environment
     */
    protected $eventDispatcher;

    /**
     * @var ListingRendererInterface
     */
    protected $renderer;


    /**
     * @param FormFactoryInterface $formFactory
     * @param RegistryInterface $registry
     * @param EventDispatcherInterface $eventDispatcher
     * @param ListingRendererInterface $renderer
     */
    public function __construct(FormFactoryInterface $formFactory, RegistryInterface $registry, EventDispatcherInterface $eventDispatcher, ListingRendererInterface $renderer)
    {
        $this->formFactory = $formFactory;
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
        $this->renderer = $renderer;
    }

    /**
     * @param ListingTypeInterface $type
     * @param array $options
     * @return Listing
     */
    public function createListing(ListingTypeInterface $type, array $options = array())
    {
        $router = $this->renderer->getRouter();
        $dataSourceResolver = function(Options $options) use ($router) {
            if (isset($options['route'])) {
                $data_source = $router->generate($options['route'], isset($options['route_parameters']) ? $options['route_parameters'] : array());
            } else {
                $data_source = $options['request']->getRequestUri();
            }

            return $data_source;
        };

        $columnBuilder = $this->createColumnBuilder($type, $options);
        $filterBuilder = $this->createFilterBuilder($type, $options);

        // Load default options to resolver:
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setRequired(array(
            'request'
        ));
        $optionsResolver->setDefined(array(
            'template',
            'class',
            'query_builder',
            'process_result_callback',
            'process_row_callback',
            'order_by',
            'order_direction'
        ));
        $optionsResolver->setDefaults(array(
            'data_source'   => $dataSourceResolver,
            'date_format'   => 'd-m-Y H:i:s',
            'page_length'   => 10,
            'page_length_options'  => array(2, 10, 25, 50, -1)
        ));

        // Modify default options by ListingType:
        $type->setDefaultOptions($optionsResolver);

        $listing = new Listing(
            $type->getName(),
            $columnBuilder->getColumns(),
            $filterBuilder->getFilters(),
            $this->registry,
            $this->eventDispatcher,
            $this->renderer,
            $optionsResolver->resolve($options)
        );

        return $listing;
    }


    /**
     * @param ListingTypeInterface $type
     * @param array $options
     * @return ColumnBuilder
     */
    protected function createColumnBuilder(ListingTypeInterface $type = null, array $options = array())
    {
        $columnBuilder = new ColumnBuilder();
        if ($type instanceof ListingTypeInterface) {
            $type->buildColumns($columnBuilder, $options);
        }

        return $columnBuilder;
    }


    /**
     * @param ListingTypeInterface $type
     * @param array $options
     * @return FilterBuilder
     */
    protected function createFilterBuilder(ListingTypeInterface $type = null, array $options = array())
    {
        if ($type instanceof ListingTypeInterface) {
            $filterBuilder = new FilterBuilder($this->formFactory, $type->getName());
            $type->buildFilters($filterBuilder, $options);
        } else {
            $filterBuilder = new FilterBuilder($this->formFactory);
        }

        return $filterBuilder;
    }

}