<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 2015-01-16
 * Time: 20:02
 */

namespace PawelLen\DataTablesListing\Column\Type;


abstract class ListingColumnType implements ListingColumnTypeInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $options;




    /**
     * @param string $name
     * @param array $options
     */
    public function __construct($name, array $options = array())
    {
        $this->name = $name;
        $this->options = $options;
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * @return string
     */
    public function getLabel()
    {
        return isset($this->options['label']) ? $this->options['label'] : $this->name;
    }


    /**
     * @return array
     */
    public function getAttributes()
    {
        return isset($this->options['attr']) ? $this->options['attr'] : array();
    }


    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }


    /**
     * @return bool
     */
    public function isSortable()
    {
        return false;
    }


    /**
     * @return bool
     */
    public function isSearchable()
    {
        return false;
    }


    /**
     * @throws \Exception
     */
    public function getType()
    {
        throw new \Exception('Column type must implement getType() method.');
    }

}