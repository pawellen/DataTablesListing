<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 2015-01-16
 * Time: 20:02
 */
namespace PawelLen\DataTablesListing\Filter\Type;

use Symfony\Component\Form\FormBuilder;


interface ListingFilterTypeInterface
{
    /**
     * @return string
     */
    public function getType();


    /**
     * @return string
     */
    public function getName();


    /**
     * @return FormBuilder
     */
    public function getFormBuilder();


    /**
     * @return array
     */
    public function getOptions();

}