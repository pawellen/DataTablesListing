<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 2015-01-16
 * Time: 20:02
 */

namespace PawelLen\DataTablesListing\Column\Type;


class ListingCheckbox extends ListingColumnType
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
     * @inheritdoc
     */
    public function getValues($row)
    {
        $name = $this->getPropertyValue($row, $this->options['property']);

        return array(
            'name' => $name
        );
    }


    /**
     * @return string
     */
    public function getType()
    {
        return 'checkbox';
    }

}