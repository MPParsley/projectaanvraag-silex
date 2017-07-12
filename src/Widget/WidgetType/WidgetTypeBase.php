<?php

namespace CultuurNet\ProjectAanvraag\Widget\WidgetType;

use CultuurNet\ProjectAanvraag\ContainerFactoryPluginInterface;
use CultuurNet\ProjectAanvraag\Widget\RendererInterface;
use CultuurNet\ProjectAanvraag\Widget\WidgetTypeInterface;
use Pimple\Container;

class WidgetTypeBase implements WidgetTypeInterface, ContainerFactoryPluginInterface
{

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var RendererInterface
     */
    protected $renderer;

    /**
     * @var bool
     */
    protected $cleanup;

    /**
     * @var array
     */
    protected $pluginDefinition;

    /**
     * @var array
     */
    protected $configuration;

    /**
     * LayoutBase constructor.
     *
     * @param array $plugin_definition
     * @param \Twig_Environment $twig
     * @param RendererInterface $renderer
     * @param array $configuration
     * @param bool $cleanup
     */
    public function __construct(array $pluginDefinition, \Twig_Environment $twig, RendererInterface $renderer, array $configuration, bool $cleanup)
    {
        $this->pluginDefinition = $pluginDefinition;
        $this->renderer = $renderer;
        $this->twig = $twig;

        if ($cleanup) {
            $this->configuration = $this->cleanupConfiguration($configuration, $this->pluginDefinition['annotation']->getAllowedSettings());
        }
        else {
            $this->configuration = $configuration;
        }
    }

    /**
     * @inheritDoc
     */
    public static function create(Container $container, array $pluginDefinition, array $configuration, bool $cleanup)
    {
        return new static(
            $pluginDefinition,
            $container['twig'],
            $container['widget_renderer'],
            $configuration,
            $cleanup
        );
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function renderPlaceholder()
    {
        return '';
    }

    /**
     * Cleanup the configuration.
     */
    protected function cleanupConfiguration($configuration, $allowedSettings)
    {

        foreach ($configuration as $id => $value) {

            // Unknown property? Remove from settings.
            if (!isset($allowedSettings[$id])) {
                unset($configuration[$id]);
            }
            elseif (is_array($value)) {

                // If property is an array, and allowed setting also. Cleanup the array.
                if (is_array($allowedSettings[$id])) {
                    $configuration[$id] = $this->cleanupConfiguration($value, $allowedSettings[$id]);
                }
                // If property is an array, but the allowed setting is a non-array property.
                else {

                    // If a class exists for the setting. Clean it up using the class.
                    if (class_exists($allowedSettings[$id])) {
                        $settingType = new $allowedSettings[$id]();
                        $configuration[$id] = $settingType->cleanup($configuration[$id]);
                    }
                    // No class exists => invalid property.
                    else {
                        unset($configuration[$id]);
                    }

                }
            }
            // Normal value: Cast to the requested format.
            else {
                settype($configuration[$id], $allowedSettings[$id]);
            }

        }

        return $configuration;
    }
}
