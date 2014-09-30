<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 09:13
 */

namespace PawelLen\DataTablesListing\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use PawelLen\DataTablesListing\Listing\ListingBuilderInterface;
use PawelLen\DataTablesListing\Filters\FilterBuilderInterface;


interface ListingTypeInterface {

    /**
     * @param FilterBuilderInterface $builder
     * @param array $options
     * @return mixed
     */
    public function buildFilters(FilterBuilderInterface $builder, array $options);

    /**
     * @param ListingBuilderInterface $builder
     * @param array $options
     * @return mixed
     */
    public function buildListing(ListingBuilderInterface $builder, array $options);


    /**
     * @param OptionsResolverInterface $resolver
     * @return mixed
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver);

    /**
     * @return string
     */
    public function getName();

} 