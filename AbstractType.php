<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 09:10
 */

namespace PawelLen\DataTablesListing\Listing;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use PawelLen\DataTablesListing\Listing\Filters\FilterBuilderInterface;


abstract class AbstractType implements ListingTypeInterface {

    /**
     * {@inheritdoc}
     */
    public function buildFilters(FilterBuilderInterface $builder, array $options) {

    }

    /**
     * {@inheritdoc}
     */
    public function buildListing(ListingBuilderInterface $builder, array $options) {

    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(

        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {

        return 'listing';
    }


} 