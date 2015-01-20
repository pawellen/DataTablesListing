<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 2015-01-16
 * Time: 20:02
 */

namespace PawelLen\DataTablesListing\Column\Type;

use Symfony\Component\PropertyAccess\PropertyAccess;


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


    /**
     * @param $row
     * @param $propertyPath
     * @param null $emptyValue
     * @return string
     * @throws \Exception
     */
    protected function getPropertyValue($row, $propertyPath, $emptyValue = null)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        switch (substr_count($propertyPath, '[*]')) {
            case 0:
                try {
                    $value = $propertyAccessor->getValue($row, $propertyPath);
                } catch (\Exception $e) {
                    $value = $emptyValue ?: '';
                }
                break;

            case 1:
                $iterator = 0;
                $values = array();
                while (1) {
                    try {
                        $propertyPathIterator = str_replace('[*]', '[' . $iterator . ']', $propertyPath);
                        $values[] = $propertyAccessor->getValue($row, $propertyPathIterator);
                    } catch (\Exception $e) {
                        break;
                    }
                    ++$iterator;
                }
                $value = implode(', ', $values);
                break;

            default:
                throw new \Exception('Only one wildcard for property is allowed');
        }

        return $value;
    }


}