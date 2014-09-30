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

class ListingExtension extends \Twig_Extension {

    protected $environment;

    public function initRuntime(\Twig_Environment $environment) {
        $this->environment = $environment;
    }

    public function getFunctions() {
        return array(
            'listing' => new \Twig_Function_Method($this, 'listing', array('is_safe' => array('html'))),
            'listing_scripts' => new \Twig_Function_Method($this, 'listingScripts', array('is_safe' => array('html'))),
        );
    }

    public function listing(ListingView $listingView) {
        $template = $this->environment->loadTemplate('TdCoreBundle::listing_div_layout.html.twig');       
        
        return $template->renderBlock('listing', array(
            'list' => $listingView
        ));
    }

    public function listingScripts() {
        static $isRendered = false;
        if ($isRendered) {

            return '<!-- Listing scripts already rendered -->';
        } else {
            $isRendered = true;
            $template = $this->environment->loadTemplate('TdCoreBundle::listing_div_layout.html.twig');

            return $template->renderBlock('include_listing_header', array());
        }
    }

    public function getName() {
        return 'listing_extension';
    }

}
