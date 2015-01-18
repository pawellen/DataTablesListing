<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 09:13
 */

namespace PawelLen\DataTablesListing\Filter;

use PawelLen\DataTablesListing\Filter\Type\ListingFilter;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;


class FilterBuilder implements FilterBuilderInterface
{
    /**
     * @var FormBuilderInterface
     */
    protected $formBuilder;

    /**
     * @var array
     */
    protected $children;



    /**
     * @param FormFactoryInterface $formFactory
     * @param string $name
     */
    public function __construct(FormFactoryInterface $formFactory, $name = '')
    {
        $this->children = array();
        $this->formBuilder = $formFactory->createNamedBuilder($name);
    }


    /**
     * @param string $name
     * @param mixed $type
     * @param array $options
     * @return FilterBuilder
     * @throws \Exception
     */
    public function add($name, $type, array $options = array())
    {
        $filter = $this->create($name, $type, $options);

        $this->formBuilder->add($filter->getFormBuilder());
        $this->children[$name] = $filter;

        return $this;
    }


    /**
     * @param string $name
     * @param string $type
     * @param array $options
     * @return ListingFilter
     */
    public function create($name, $type, array $options = array())
    {
        $filter_options = isset($options['filter']) ? $options['filter'] : array();
        if (!is_array($filter_options)) {
            throw new UnexpectedTypeException($filter_options, 'array');
        }
        unset($options['filter']);

        $formBuilder = $this->formBuilder->create($name, $type, $options);
        $filter = new ListingFilter($name, $formBuilder, $filter_options);

        return $filter;
    }


    /**
     * @return Filters
     */
    public function getFilters()
    {
        $filters = new Filters($this->getForm(), $this->children);

        return $filters;
    }


    /**
     * @return \Symfony\Component\Form\Form
     */
    public function getForm()
    {
        return $this->formBuilder->getForm();
    }

}