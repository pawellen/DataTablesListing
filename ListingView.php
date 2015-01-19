<?php

/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 11:29
 */

namespace PawelLen\DataTablesListing;

use PawelLen\DataTablesListing\Column\Columns;
use PawelLen\DataTablesListing\Column\Type\ListingColumn;
use PawelLen\DataTablesListing\Filter\Filters;


class ListingView
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var Columns
     */
    protected $columns;

    /**
     * @var Filters
     */
    protected $filters;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var string
     */
    protected $templateReference;



    /**
     * @param string $name
     * @param Columns $columns
     * @param Filters $filters
     * @param array $options
     * @param array $data
     */
    public function __construct($name, Columns $columns, Filters $filters, array $options = array(), array $data = array())
    {
        $this->name = $name;
        $this->columns = $columns;
        $this->filters = $filters;
        $this->options = $options;
        $this->data = $data;
        $this->templateReference = isset($options['template']) ? $options['template'] : null;
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns->getColumns();
    }


    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters->getFilters();
    }


    /**
     * @return \Symfony\Component\Form\FormView
     */
    public function getFiltersFormView()
    {
        return $this->filters->getForm()->createView();
    }


    /**
     * @return string
     */
    public function getSource()
    {
        return $this->options['data_source'];
    }


    /**
     * @return bool
     */
    public function hasFilters()
    {
        return $this->filters->count() > 0;
    }


    /**
     * @return string
     */
    public function getTemplateReference()
    {
        return $this->templateReference;
    }


    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }


    /**
     * @return array
     */
    public function getSettings()
    {
        // Columns:
        $columns = array();
        /** @var ListingColumn $column */
        foreach ($this->columns as $column) {
            $columns[] = array(
                'searchable'    => $column->isSearchable(),
                'orderable'     => $column->isSortable(),
            );
        }

        $settings = array(
            'pageLength'    => $this->options['page_length'],
            'columns'       => $columns,
            'deferLoading'  => $this->options['page_length'] ?: null,
            'lengthMenu'    => $this->options['page_length_menu'],
        );

        return $settings;
    }


    /**
     * @return string
     */
    public function getSettingsJson()
    {
        $settings = $this->getSettings();

        return json_encode($settings);
    }

}
