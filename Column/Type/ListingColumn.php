<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 2015-01-16
 * Time: 20:02
 */

namespace PawelLen\DataTablesListing\Column\Type;


class ListingColumn extends ListingColumnType
{
    /**
     * @param string $name
     * @param array $options
     */
    public function __construct($name, array $options = array())
    {
        parent::__construct($name, $options);
    }

    /**
     * @return bool
     */
    public function isSortable()
    {
        if (isset($this->options['order_by']) && $this->options['order_by'] === false) {
            return false;
        }

        return true;
    }


    /**
     * @return string
     */
    public function getType()
    {
        return 'column';
    }

}