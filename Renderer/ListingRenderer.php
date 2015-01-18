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
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\RouterInterface;


class ListingRenderer implements ListingRendererInterface
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var \Twig_Environment
     */
    protected $environment = null;

    /**
     * @var string
     */
    protected $defaultTemplate;

    /**
     * @var array
     */
    protected $assetsConfiguration;

    /**
     * @var array
     */
    protected $blocks;

    /**
     * @var \Twig_Template
     */
    protected $template;

    /**
     * @var bool
     */
    protected $loaded;


    /**
     * @param RouterInterface $router
     * @param $defaultTemplate
     * @param array $assetsConfiguration
     * @param \Twig_Environment $environment
     */
    function __construct(RouterInterface $router, $defaultTemplate, array $assetsConfiguration, \Twig_Environment $environment = null)
    {
        $this->router = $router;
        $this->defaultTemplate = $defaultTemplate;
        $this->environment = $environment;
        $this->assetsConfiguration = $assetsConfiguration;
        $this->loaded = false;
        $this->blocks = array();
    }


    /**
     * @inheritdoc
     */
    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }


    /**
     * @param null $template
     * @return $this
     * @throws \Exception
     */
    public function load($template = null)
    {
        if (!$this->environment instanceof \Twig_Environment) {
            throw new \Exception('Unable to load ListingRenderer, \Twig_Environment is not loaded. Maybe you forget to call "initRuntime".');
        }

        if (!$this->template) {
            $this->template = $this->environment->loadTemplate($this->defaultTemplate);
        }

        if ($template !== null) {
            /** @var \Twig_Template $childTemplate */
            $childTemplate = $this->environment->loadTemplate($template);
            $this->blocks = array_merge($this->blocks, $childTemplate->getBlocks());
        }

        return $this;
    }


    /**
     * @param ListingView $listingView
     * @return string
     */
    public function renderListing(ListingView $listingView)
    {
        if (!$this->loaded)
            $this->load();

        return trim($this->template->renderBlock('listing', array(
            'listing' => $listingView
        ), $this->blocks));
    }


    /**
     * @return string
     * @throws \Exception
     */
    public function renderListingAssets()
    {
        if (!$this->loaded)
            $this->load();

        return trim($this->template->renderBlock('listing_assets', $this->assetsConfiguration, $this->blocks));
    }


    /**
     * @inheritdoc
     */
    public function renderCell(ListingColumnTypeInterface $column, $row)
    {
        if (!$this->loaded)
            $this->load();

        // Load and process value:
        $values = $column->getValues($row);
        //$value = $this->normalizeValue($value, $column->getOptions());

        // Create template block name and parameters:
        $blockName = 'listing_' . $column->getType();
        $parameters = array_merge(array(
            'column' => $column
        ), $values);

        // Render block:
        return trim($this->template->renderBlock($blockName, $parameters, $this->blocks));
    }




    /**
     * @param $value
     * @param array $options
     * @return string
     *
    protected function normalizeValue($value, array $options = array())
    {
        switch (true) {
            case ($value instanceof \DateTime):
                $value = $value->format($options['date_format']);
                break;

            case (is_object($value) && method_exists($value, '__toString')):
                $value = (string)$value;
                break;

        }

        return $value;
    }
     *


    /**
     * @param $value
     * @param $row
     * @param array $options
     * @return string
     */
    protected function transformValue($value, $row, array $options = array())
    {
        if (isset($options['route'])) {
            $parameters = array();
            if (isset($options['route_parameters'])) {
                $propertyAccessor = PropertyAccess::createPropertyAccessor();
                foreach ($options['route_parameters'] as $_name => $_propertyPath) {
                    $parameters[$_name] = $propertyAccessor->getValue($row, $_propertyPath);
                }
            }
            $url = $this->router->generate($options['route'], $parameters);
            $value = '<a href="' . $url . '">' . htmlspecialchars($value) . '</a>';
        }

        return $value;
    }


    /**
     * @inheritdoc
     */
    public function getRouter()
    {
        return $this->router;
    }


    /**
     * @inheritdoc
     */
    public function getEnvironment()
    {
        return $this->environment;
    }


    /**
     * @inheritdoc
     */
    public function getDefaultTemplate()
    {
        return $this->defaultTemplate;
    }

}