<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 11:36
 */

namespace PawelLen\DataTablesListing;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use PawelLen\DataTablesListing\Column\ColumnBuilder;
use PawelLen\DataTablesListing\Type\ListingTypeInterface;
use PawelLen\DataTablesListing\Filter\FilterBuilder;
use PawelLen\DataTablesListing\Renderer\ListingRendererInterface;


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
     * @var string|null
     */
    protected $defaultIdProperty;


    /**
     * @param FormFactoryInterface $formFactory
     * @param RegistryInterface $registry
     * @param EventDispatcherInterface $eventDispatcher
     * @param ListingRendererInterface $renderer
     * @param string|null $defaultIdProperty
     */
    public function __construct(FormFactoryInterface $formFactory, RegistryInterface $registry, EventDispatcherInterface $eventDispatcher, ListingRendererInterface $renderer, $defaultIdProperty = null)
    {
        $this->formFactory = $formFactory;
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
        $this->renderer = $renderer;
        $this->defaultIdProperty = $defaultIdProperty;
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

        $pageLengthMenuOptionsNormalizer = function(Options $options, $value) {
            $lengthMenu = array();
            foreach ($value as $length) {
                $lengthMenu[0][] = $length > 0 ? (int)$length : -1;
                $lengthMenu[1][] = $length > 0 ? (int)$length : '-';
            }

            /*
            // Ensure that "page_length" option is in "page_length_menu" array:
            if (!isset($lengthMenu[0][0]) || !in_array((int)$options['page_length'], $lengthMenu[0])) {
                $lengthMenu[0][] = $options['page_length'] > 0 ? (int)$options['page_length'] : -1;
                $lengthMenu[1][] = $options['page_length'] > 0 ? (int)$options['page_length'] : '-';
            }
            */

            return $lengthMenu;
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
            'order_direction',
            'order_column'
        ));

        $optionsResolver->setDefaults(array(
            'data_source'       => $dataSourceResolver,
            //'date_format'       => 'd-m-Y H:i:s',
            'page_length'       => 10,
            'page_length_menu'  => array(10, 25, 50, 100, -1),
            'auto_width'        => true,
            'row_attr'          => array(
                'id'    => $this->defaultIdProperty ?: null,
                'class' => null
            ),
            'order_column'      => array(),
            'save_state'        => false
        ));
        $optionsResolver->setNormalizers(array(
            'page_length_menu' => $pageLengthMenuOptionsNormalizer
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