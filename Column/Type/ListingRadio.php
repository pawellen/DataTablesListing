<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 2015-01-16
 * Time: 20:02
 */

namespace PawelLen\DataTablesListing\Column\Type;


class ListingRadio extends ListingColumnType
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

        // Build parameters:
        $parameters = array();
        if (isset($this->options['parameters'])) {
            foreach ($this->options['parameters'] as $name => $propertyPath) {
                $parameters[$name] = $this->getPropertyValue($row, $propertyPath);
            }
        }

        return array(
            'name' => $this->name,
            'value' => $value,
            'parameters' => $parameters
        );
    }


    /**
     * @return string
     */
    public function getType()
    {
        return 'radio';
    }

}