<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 01.09.14
 * Time: 16:58
 */

namespace PawelLen\DataTablesListing\Listing\Event;

use Symfony\Component\EventDispatcher\Event;


class SearchCriteriaEvent extends Event
{

    const SEARCH_CRITERIA = 'listing.search_criteria';

    protected $searchCriteria;


    function __construct($searchCriteria)
    {
        $this->searchCriteria = $searchCriteria;
    }

    /**
     * @param mixed $searchCriteria
     */
    public function setSearchCriteria($searchCriteria)
    {
        $this->searchCriteria = $searchCriteria;
    }

    /**
     * @return mixed
     */
    public function getSearchCriteria()
    {
        return $this->searchCriteria;
    }



} 