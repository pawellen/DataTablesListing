<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 2015-01-16
 * Time: 20:02
 */

namespace PawelLen\DataTablesListing\Filter\Type;

use Symfony\Component\Form\FormBuilderInterface;


class ListingFilter extends ListingFilterType
{
    /**
     * @param string $name
     * @param FormBuilderInterface $formBuilder
     * @param array $options
     */
    public function __construct($name, FormBuilderInterface $formBuilder, array $options = array())
    {
        parent::__construct($name, $formBuilder, $options);
    }


    /**
     * @return string
     */
    public function getType()
    {
        return 'default';
    }

}