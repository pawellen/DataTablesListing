<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 11:36
 */

namespace PawelLen\DataTablesListing\Listing;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use PawelLen\DataTablesListing\Listing\Filters\FilterBuilder;


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
     * @var Router
     */
    protected $router;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;


    /**
     * @param FormFactoryInterface $formFactory
     * @param RegistryInterface $registry
     * @param Router $router
     */
    function __construct(FormFactoryInterface $formFactory, RegistryInterface $registry, Router $router, EventDispatcherInterface $eventDispatcher)
    {
        $this->formFactory = $formFactory;
        $this->registry = $registry;
        $this->router = $router;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ListingTypeInterface $type
     * @param array $options
     * @return Listing
     */
    public function createListing(ListingTypeInterface $type, array $options = array())
    {
        $listingBuilder = $this->createListingBuilder($type, $options);
        $filterBuilder = $this->createFilterBuilder($type, $options);

        // Load default options to resolver:
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setOptional(array(
            'class',
            'query_builder',
            'data'
        ));
        $optionsResolver->setRequired(array(
            'request'
        ));
        $type->setDefaultOptions($optionsResolver);

        $listing = new Listing(
            $type->getName(),
            $listingBuilder->getListingColumns(),
            $listingBuilder->getListingButtons(),
            $filterBuilder->getListingFilters(),
            $filterBuilder->getListingForm(),
            $optionsResolver->resolve($options),
            $this->registry,
            $this->router,
            $this->eventDispatcher
        );

        return $listing;
    }


    /**
     * @param ListingTypeInterface $type
     * @param array $options
     * @return ListingBuilder
     */
    protected function createListingBuilder(ListingTypeInterface $type = null, array $options = array())
    {
        $listingBuilder = new ListingBuilder();
        if ($type instanceof ListingTypeInterface) {
            $type->buildListing($listingBuilder, $options);
        }

        return $listingBuilder;
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