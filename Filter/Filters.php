<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 09:13
 */

namespace PawelLen\DataTablesListing\Filter;

use PawelLen\DataTablesListing\Filter\Type\ListingFilter;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;


class Filters implements \Iterator, \ArrayAccess
{
    /**
     * @var Form
     */
    protected $form;

    /**
     * @var array
     */
    protected $filters;




    /**
     * @param FormInterface $form
     * @param array $filters
     */
    public function __construct(FormInterface $form, array $filters)
    {
        $this->form = $form;
        $this->filters = $filters;
    }


    /**
     * @return Form
     */
    public function getForm()
    {
        return $this->form;
    }


    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }


    /**
     * @return int
     */
    public function count()
    {
        return count($this->filters);
    }


    /**
     * @param $index
     * @return null|ListingFilter
     */
    public function getByIndex($index)
    {
        $keys = array_keys($this->filters);
        if (isset($keys[$index]) && isset($this->filters[ $keys[$index] ])) {

            return $this->filters[ $keys[$index] ];
        }

        return null;
    }


    /**
     * @inheritdoc
     */
    public function rewind()
    {
        return reset($this->filters);
    }


    /**
     * @inheritdoc
     */
    public function current()
    {
        return current($this->filters);
    }


    /**
     * @inheritdoc
     */
    public function key()
    {
        return key($this->filters);
    }


    /**
     * @inheritdoc
     */
    public function next()
    {
        return next($this->filters);
    }


    /**
     * @inheritdoc
     */
    public function valid()
    {
        return key($this->filters) !== null;
    }


    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return isset($this->filters[$offset]);
    }


    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return isset($this->filters[$offset]) ? $this->filters[$offset] : null;
    }


    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->filters[] = $value;
        } else {
            $this->filters[$offset] = $value;
        }
    }


    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        unset($this->filters[$offset]);
    }

}