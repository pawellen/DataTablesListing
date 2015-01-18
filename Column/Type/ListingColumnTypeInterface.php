<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 2015-01-16
 * Time: 20:02
 */
namespace PawelLen\DataTablesListing\Column\Type;


interface ListingColumnTypeInterface
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
     * @param $row
     * @return mixed
     */
    public function getValues($row);


    /**
     * @return array
     */
    public function getOptions();


    /**
     * @return bool
     */
    public function isSortable();

    /**
     * @return bool
     */
    public function isSearchable();

}