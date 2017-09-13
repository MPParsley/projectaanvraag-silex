<?php

namespace CultuurNet\ProjectAanvraag\Widget\WidgetType;

use CultuurNet\ProjectAanvraag\ContainerFactoryPluginInterface;
use CultuurNet\ProjectAanvraag\Widget\RendererInterface;
use CultuurNet\ProjectAanvraag\Widget\Twig\TwigPreprocessor;
use CultuurNet\ProjectAanvraag\Widget\WidgetTypeInterface;
use CultuurNet\ProjectAanvraag\Widget\WidgetPager;
use Pimple\Container;

class WidgetTypeBase implements WidgetTypeInterface, ContainerFactoryPluginInterface
{

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var TwigPreprocessor
     */
    protected $twigPreprocessor;

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
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $settings;

    /**
     * WidgetTypeBase constructor.
     * @param array $pluginDefinition
     * @param array $configuration
     * @param bool $cleanup
     * @param \Twig_Environment $twig
     * @param TwigPreprocessor $twigPreprocessor
     * @param RendererInterface $renderer
     */
    public function __construct(array $pluginDefinition, array $configuration, bool $cleanup, \Twig_Environment $twig, TwigPreprocessor $twigPreprocessor, RendererInterface $renderer)
    {
        $this->pluginDefinition = $pluginDefinition;
        $this->renderer = $renderer;
        $this->twigPreprocessor = $twigPreprocessor;
        $this->twig = $twig;

        if (isset($configuration['id'])) {
            $this->id = $configuration['id'];
        }

        if (isset($configuration['name'])) {
            $this->name = $configuration['name'];
        }

        $settings = $configuration['settings'] ?? [];
        if ($cleanup) {
            $settings = $this->cleanupConfiguration($settings, $this->pluginDefinition['annotation']->getAllowedSettings());
        }

        $defaultSettings = $this->pluginDefinition['annotation']->getDefaultSettings();
        if (is_array($defaultSettings)) {
            $settings = $this->mergeDefaults($settings, $defaultSettings);
        }

        $this->settings = $settings;
    }

    /**
     * @inheritDoc
     */
    public static function create(Container $container, array $pluginDefinition, array $configuration, bool $cleanup)
    {
        return new static(
            $pluginDefinition,
            $configuration,
            $cleanup,
            $container['twig'],
            $container['widget_twig_preprocessor'],
            $container['widget_renderer']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function renderPlaceholder()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->pluginDefinition['annotation']->getId(),
            'settings' => $this->settings,
        ];
    }

    /**
     * Trim the first
     * parameter.
     *
     * @param $params
     * @return array
     */
    protected function filterUrlQueryParams($params)
    {
        if (!empty($params)) {
            foreach ($params as $key => $param) {
                // Check key for question mark.
                if (substr($key, 0, 1) == '?') {
                    // Trim question mark.
                    $trimmedKey = ltrim($key, '?');
                    // Replace key.
                    $params[$trimmedKey] = $param;
                    unset($params[$key]);
                }
            }
        }
        return $params;
    }

    /**
     * Return a WidgetPager object for the given data.
     *
     * @param int $itemsPerPage
     * @param int $totalItems
     * @param int $pageIndex
     * @return WidgetPager
     */
    protected function retrievePagerData(int $itemsPerPage, int $totalItems, int $pageIndex)
    {
        // Determine number of pages.
        $pages = ceil($totalItems / $itemsPerPage);
        return new WidgetPager($pages, $pageIndex, $itemsPerPage);
    }

    /**
     * Merge all defaults into the $settings array.
     */
    protected function mergeDefaults($settings, $defaultSettings)
    {
        foreach ($defaultSettings as $id => $defaultSetting) {
            if (!isset($settings[$id])) {
                $settings[$id] = $defaultSetting;
            } elseif (is_array($settings[$id]) && is_array($defaultSetting)) {
                $settings[$id] = $this->mergeDefaults($settings[$id], $defaultSetting);
            }
        }

        return $settings;
    }

    /**
     * Cleanup the configuration.
     */
    protected function cleanupConfiguration($settings, $allowedSettings)
    {
        foreach ($settings as $id => $value) {
            // Unknown property? Remove from settings.
            if (!isset($allowedSettings[$id])) {
                unset($settings[$id]);
            } elseif (is_array($value)) {
                // If property is an array, and allowed setting also. Cleanup the array.
                if (is_array($allowedSettings[$id])) {
                    $settings[$id] = $this->cleanupConfiguration($value, $allowedSettings[$id]);
                } else {
                    // If a class exists for the setting. Clean it up using the class.
                    if (class_exists($allowedSettings[$id])) {
                        $class = $allowedSettings[$id];
                        $settingType = new $class();
                        $settings[$id] = $settingType->cleanup($settings[$id]);
                    } else {
                        // No class exists => invalid property.
                        unset($settings[$id]);
                    }
                }
            } else {
                // Normal value: Cast to the requested format.
                settype($settings[$id], $allowedSettings[$id]);
            }
        }

        return $settings;
    }
}
