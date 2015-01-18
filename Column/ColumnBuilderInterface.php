<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 09:13
 */

namespace PawelLen\DataTablesListing\Column;


use PawelLen\DataTablesListing\Column\Type\ListingColumnTypeInterface;

interface ColumnBuilderInterface
{
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
    public function getColumns();

}