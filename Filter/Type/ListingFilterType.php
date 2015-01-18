<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 2015-01-16
 * Time: 20:02
 */

namespace PawelLen\DataTablesListing\Filter\Type;

use Symfony\Component\Form\FormBuilder;


abstract class ListingFilterType implements ListingFilterTypeInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var FormBuilder
     */
    protected $formBuilder;

    /**
     * @var array
     */
    protected $options;



    /**
     * @param string $name
     * @param FormBuilder $formBuilder
     * @param array $options
     */
    public function __construct($name, FormBuilder $formBuilder, array $options = array())
    {
        $this->name = $name;
        $this->formBuilder = $formBuilder;
        $this->options = $options;
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * @return FormBuilder
     */
    public function getFormBuilder()
    {
        return $this->formBuilder;
    }


    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }


    /**
     * @throws \Exception
     */
    public function getType()
    {
        throw new \Exception('Filter type must implement getType() method.');
    }

}