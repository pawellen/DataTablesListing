<?php

/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 22.07.14
 * Time: 09:25
 */

namespace PawelLen\DataTablesListing\Twig;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use PawelLen\DataTablesListing\ListingView;

class ListingExtension extends \Twig_Extension
{

    /**
     * @var \Twig_Environment
     */
    protected $environment;

    /**
     * @var string
     */
    protected $defaultTemplate;



    /**
     * @param string $defaultTemplate
     */
    public function __construct($defaultTemplate)
    {
        $this->defaultTemplate = (string)$defaultTemplate;
    }


    /**
     * @param \Twig_Environment $environment
     */
    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }


    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            'render_listing' => new \Twig_Function_Method($this, 'renderListing', array('is_safe' => array('html'))),
            'render_listing_scripts' => new \Twig_Function_Method($this, 'renderListingScripts', array('is_safe' => array('html'))),
        );
    }


    /**
     * @param ListingView $listingView
     * @return string
     */
    public function renderListing(ListingView $listingView)
    {
        /** @var \Twig_Template $template */
        $template = $this->environment->loadTemplate($this->defaultTemplate);

        // Override template blocks:
        $blocks = array();
        if ($listingView->getTemplateReference()) {
            /** @var \Twig_Template $childTemplate */
            $childTemplate = $this->environment->loadTemplate($listingView->getTemplateReference());
            $blocks = $childTemplate->getBlocks();
        }

        return $template->renderBlock('listing', array(
            'listing' => $listingView
        ), $blocks);
    }


    /**
     * @return string
     */
    public function renderListingScripts()
    {
        static $isRendered = false;

        if (!$isRendered) {
            /** @var \Twig_Template $template */
            $template = $this->environment->loadTemplate($this->defaultTemplate);
            $isRendered = true;

            return $template->renderBlock('listing_assets', array());
        }

        return '<!-- Listing scripts already rendered -->';
    }


    /**
     * @return string
     */
    public function getName()
    {
        return 'listing_extension';
    }

}
