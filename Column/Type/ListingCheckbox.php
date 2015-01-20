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
        $value = $this->getPropertyValue($row, isset($this->options['property']) ? $this->options['property'] : $this->name);

        return array(
            'value' => $value,
            'options' => $this->options,
            'name' => $this->name
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