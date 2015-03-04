<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 09:13
 */

namespace PawelLen\DataTablesListing\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use PawelLen\DataTablesListing\Filter\FilterBuilderInterface;
use PawelLen\DataTablesListing\Column\ColumnBuilderInterface;


interface ListingTypeInterface
{
    /**
     * @param FilterBuilderInterface $builder
     * @param array $options
     * @return mixed
     */
    public function buildFilters(FilterBuilderInterface $builder, array $options);


    /**
     * @param ColumnBuilderInterface $builder
     * @param array $options
     */
    public function buildColumns(ColumnBuilderInterface $builder, array $options);


    /**
     * @param OptionsResolver $resolver
     */
    public function setDefaultOptions(OptionsResolver $resolver);


    /**
     * @return string
     */
    public function getName();

} 