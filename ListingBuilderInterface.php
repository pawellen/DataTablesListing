<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 09:13
 */

namespace PawelLen\DataTablesListing\Listing;


interface ListingBuilderInterface {

    /**
     * @param string $name
     * @param string $type
     * @param array $options
     * @return ListingBuilderInterface
     */
    public function add($name, $type, array $options = array());

    /**
     * @param $event_name
     * @param callable $callback
     * @return mixed
     */
    public function addEventListener($event_name, \Closure $callback);
} 