<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 09:13
 */

namespace PawelLen\DataTablesListing;


use PawelLen\DataTablesListing\Listing\ListingBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ListingBuilder implements ListingBuilderInterface
{

    /**
     * @var array
     */
    protected $columns = array();

    /**
     * @var array
     */
    protected $buttons = array();

    /**
     * @var OptionsResolver
     */
    protected $buttonOptionsResolver;


    public function __construct()
    {
        $this->buttonOptionsResolver = new OptionsResolver();
        $this->buttonOptionsResolver->setRequired(array(
           'route'
        ));
        $this->buttonOptionsResolver->setOptional(array(
            'label', 'params', 'class', 'icon'
        ));
    }

    public function add($name, $type, array $options = array())
    {
        if ($type === 'column') {
            $this->addColumn($name, $options);
        } else if ($type === 'button') {
            $this->addButton($name, $options);
        } else {
            throw new \Exception('Unknown ListingBuilder type: "' . $type .'", allowed are: "column, button"');
        }

        return $this;
    }

    protected function addButton($name, $options)
    {
        $this->buttons[ $name ] = $this->buttonOptionsResolver->resolve($options);
    }

    protected function addColumn($name, $options)
    {
        $this->columns[ $name ] = $options;
    }

    public function getListingColumns()
    {
        return $this->columns;
    }

    public function getListingButtons()
    {
        return $this->buttons;
    }

    /**
     * @param $event_name
     * @param callable $callback
     * @return mixed
     */
    public function addEventListener($event_name, \Closure $callback)
    {

    }


}