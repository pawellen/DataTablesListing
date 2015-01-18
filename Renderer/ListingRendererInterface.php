<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 2015-01-18
 * Time: 19:31
 */

namespace PawelLen\DataTablesListing\Renderer;

use PawelLen\DataTablesListing\Column\Type\ListingColumnTypeInterface;
use PawelLen\DataTablesListing\ListingView;
use Symfony\Component\Routing\RouterInterface;

interface ListingRendererInterface
{
    /**
     * @param ListingColumnTypeInterface $column
     * @param mixed $row
     * @return string
     */
    public function renderCell(ListingColumnTypeInterface $column, $row);


    /**
     * @param ListingView $listingView
     * @return string
     */
    public function renderListing(ListingView $listingView);


    /**
     * @return string
     */
    public function renderListingAssets();


    /**
     * @param \Twig_Environment $environment
     */
    public function initRuntime(\Twig_Environment $environment);


    /**
     * @param null $template
     * @return mixed
     */
    public function load($template = null);


    /**
     * @return RouterInterface
     */
    public function getRouter();


    /**
     * @return \Twig_Environment
     */
    public function getEnvironment();


    /**
     * @return string
     */
    public function getDefaultTemplate();

}