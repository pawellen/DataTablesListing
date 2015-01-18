<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 09:10
 */

namespace PawelLen\DataTablesListing\Type;

use PawelLen\DataTablesListing\Column\ColumnBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use PawelLen\DataTablesListing\Filter\FilterBuilderInterface;


abstract class AbstractType implements ListingTypeInterface
{

    /**
     * {@inheritdoc}
     */
    public function buildFilters(FilterBuilderInterface $builder, array $options) {

    }


    /**
     * {@inheritdoc}
     */
    public function buildColumns(ColumnBuilderInterface $builder, array $options) {

    }


    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array());
    }


    /**
     * {@inheritdoc}
     */
    public function getName() {

        return 'listing';
    }

} 