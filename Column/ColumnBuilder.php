<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 09:13
 */

namespace PawelLen\DataTablesListing\Column;


use PawelLen\DataTablesListing\Column\Type\ListingColumnTypeInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class ColumnBuilder implements ColumnBuilderInterface
{
    /**
     * @var array
     */
    protected $knownTypes = array(
        'column'    => 'PawelLen\DataTablesListing\Column\Type\ListingColumn',
        'button'    => 'PawelLen\DataTablesListing\Column\Type\ListingButton',
        'checkbox'  => 'PawelLen\DataTablesListing\Column\Type\ListingCheckbox',
        'radio'     => 'PawelLen\DataTablesListing\Column\Type\ListingRadio',
    );

    /**
     * @var array
     */
    protected $children;



    public function __construct()
    {
        $this->children = array();
    }


    /**
     * @param string $name
     * @param string $type
     * @param array $options
     * @return $this
     */
    public function add($name, $type, array $options = array())
    {
        $column = $this->create($name, $type, $options);

        $this->children[$name] = $column;

        return $this;
    }

    /**
     * @param string $name
     * @param string $type
     * @param array $options
     * @return ListingColumnTypeInterface
     * @throws \Exception
     */
    public function create($name, $type, array $options = array())
    {
        if (!is_string($name)) {
            throw new UnexpectedTypeException($name, 'string');
        }

        $columnType = $this->resolveType($type);
        $column = new $columnType($name, $options);

        if (!$column instanceof ListingColumnTypeInterface) {
            throw new UnexpectedTypeException($column, 'PawelLen\DataTablesListing\Column\Type\ListingColumnTypeInterface');
        }

        return $column;
    }


    /**
     * @param string $name
     * @param string $class
     */
    public function addType($name, $class)
    {
        if (!is_string($name)) {
            throw new UnexpectedTypeException($name, 'string');
        }
        if (!is_string($class)) {
            throw new UnexpectedTypeException($class, 'string');
        }

        $this->knownTypes[ $name ] = $class;
    }


    /**
     * @return Columns
     */
    public function getColumns()
    {
        $columns = new Columns($this->children);

        return $columns;
    }


    /**
     * @param string $type
     * @return string
     * @throws \Exception
     */
    protected function resolveType($type)
    {
        if (!isset($this->knownTypes[ $type ])) {
            throw new \Exception('Unknown column type "' . $type .'", known types are: "' . implode(', ', array_keys($this->knownTypes)) . '"');
        }

        return $this->knownTypes[ $type ];
    }

}