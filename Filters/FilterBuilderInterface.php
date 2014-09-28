<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 09:13
 */

namespace PawelLen\DataTablesListing\Listing\Filters;

use Symfony\Component\Form\FormFactoryInterface;


interface FilterBuilderInterface {


    /**
     * @param FormFactoryInterface $formFactory
     * @param string $name
     */
    public function __construct(FormFactoryInterface $formFactory, $name = '');


    /**
     * @return mixed
     */
    public function getListingFilters();

    /**
     * @return \Symfony\Component\Form\Form
     */
    public function getListingForm();


    /**
     * @param string $name
     * @param mixed $type
     * @param array $options
     * @return FilterBuilderInterface
     */
    public function add($name, $type, array $options = array());

    /**
     * @param $event_name
     * @param callable $callback
     * @return mixed
     */
    public function addEventListener($event_name, \Closure $callback);

} 