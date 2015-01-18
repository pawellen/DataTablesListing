<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 09:13
 */

namespace PawelLen\DataTablesListing\Filter;

use PawelLen\DataTablesListing\Column\ColumnBuilderInterface;
use PawelLen\DataTablesListing\Column\Type\ListingColumnTypeInterface;
use Symfony\Component\Form\FormFactoryInterface;


interface FilterBuilderInterface
{
    /**
     * @param FormFactoryInterface $formFactory
     * @param string $name
     */
    public function __construct(FormFactoryInterface $formFactory, $name = '');


    /**
     * @param string $name
     * @param string $type
     * @param array $options
     * @return ColumnBuilderInterface
     */
    public function add($name, $type, array $options = array());


    /**
     * @param string $name
     * @param string $type
     * @param array $options
     * @return ListingColumnTypeInterface
     */
    public function create($name, $type, array $options = array());


    /**
     * @return array
     */
    public function getFilters();


    /**
     * @return \Symfony\Component\Form\Form
     */
    public function getForm();

} 