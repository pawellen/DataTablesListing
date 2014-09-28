<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 09:13
 */

namespace PawelLen\DataTablesListing\Listing\Filters;


use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;

class FilterBuilder implements FilterBuilderInterface {

    /**
     * @var array
     */
    protected $filters;

    /**
     * @var FormBuilderInterface
     */
    protected $formBuilder;

    /**
     * @param FormFactoryInterface $formFactory
     * @param string $name
     */
    public function __construct(FormFactoryInterface $formFactory, $name = '')
    {
        $this->filters = array();
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
        if (!isset($options['filter']) || !is_array($options['filter'])) {

            throw new \Exception('Missing required option "filter" or it is not an array');
        }

        // Set entity flag in filter configuration when type is entity:
        if ($type === 'entity') {
            $options['filter']['entity'] = isset($options['multiple']) ? 'multiple' : 'single';
        }
        // Copy filter config and cleanup:
        $this->addFilter($name, $options['filter']);
        unset($options['filter']);

        // Pass clean config to form builder:
        $this->addFormChild($name, $type, $options);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getListingFilters()
    {
        return $this->filters;
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    public function getListingForm()
    {
        return $this->formBuilder->getForm();
    }

    /**
     * @param string $name
     * @param array $options
     * @return FilterBuilder
     */
    private function addFilter($name, $options)
    {
        $this->filters[ $name ] =  $options;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $type
     * @param array $options
     * @return FilterBuilder
     */
    private function addFormChild($name, $type, $options)
    {
        $this->formBuilder->add($name, $type, $options);

        return $this;
    }

    /**
     * @param $event_name
     * @param callable $callback
     * @return mixed
     */
    public function addEventListener($event_name, \Closure $callback)
    {

    }

}