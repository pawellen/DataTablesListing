<?php

/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.07.14
 * Time: 11:29
 */

namespace PawelLen\DataTablesListing\Listing;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;

class ListingView {

    /**
     * @var string
     */
    protected $name;

    /**
     * @var FormView
     */
    protected $formView;

    /**
     * @var string
     */
    protected $ajaxSource;

    /**
     * @var array
     */
    protected $columns;
    
    /**
     * @var array
     */
    protected $buttons;

    /**
     * @param $name
     * @param $columns
     * @param FormView $formView
     * @param $ajaxSource
     */
    public function __construct($name, $columns, $buttons, FormView $formView, $ajaxSource) {
        $this->name = trim($name);
        $this->formView = $formView;
        $this->ajaxSource = $ajaxSource;
        $this->columns = $columns;
        $this->buttons = $buttons;
    }
 
    public function getTableHeader() {
        $header = array();
        foreach ($this->columns as $name => $column) {
            $header[$name] = $column['label'];
        }
        
        if($this->buttons){
            $header['buttons'] = '';
        }

        return $header;
    }

    public function getAjaxSource() {
        return $this->ajaxSource;
    }

    /**
     * @return \Symfony\Component\Form\FormView
     */
    public function getFilters() {
        return $this->formView;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    public function hasFilters() {
        if ($this->formView->count() > 0) {

            return true;
        }

        return false;
    }

}
