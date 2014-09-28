<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 01.09.14
 * Time: 16:58
 */

namespace PawelLen\DataTablesListing\Listing\Event;

use Symfony\Component\EventDispatcher\Event;


class CreateRowEvent extends Event
{

    const CREATE_ROW = 'listing.create_row';

    /**
     * @var array
     */
    protected $row;

    /**
     * @var int
     */
    protected $row_count;


    function __construct(array $row)
    {
        $this->row = $row;
        $this->row_count = count($row);
    }

    /**
     * @param array $row
     * @throws \Exception
     */
    public function setRow(array $row)
    {
        if (!is_array($row) || count($row) !== $this->row_count) {
            throw new \Exception('Unable to set new row. New row must be an array with unchanged count of elements: "' . $this->row_count .'"');
        }
        $this->row = $row;
    }

    /**
     * @return array
     */
    public function getRow()
    {
        return $this->row;
    }



} 