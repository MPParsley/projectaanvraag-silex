<?php

namespace CultuurNet\ProjectAanvraag\Widget\WidgetType;

use CultuurNet\ProjectAanvraag\Widget\AlterSearchResultsQueryInterface;
use CultuurNet\ProjectAanvraag\Widget\Event\SearchResultsQueryAlter;
use CultuurNet\ProjectAanvraag\Widget\RegionService;
use CultuurNet\ProjectAanvraag\Widget\RendererInterface;
use CultuurNet\ProjectAanvraag\Widget\Twig\TwigPreprocessor;
use CultuurNet\ProjectAanvraag\Widget\WidgetTypeInterface;

use CultuurNet\ProjectAanvraag\Widget\Annotation\WidgetType;
use CultuurNet\SearchV3\Parameter\Query;
use CultuurNet\SearchV3\SearchQueryInterface;
use Pimple\Container;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the search form widget type.
 *
 * @WidgetType(
 *      id = "search-form",
 *      defaultSettings = {
 *          "general": {
 *              "new_window": false,
 *              "button_label": "Zoeken"
 *          },
 *          "header": {
 *              "body": "<p>Uit in ...</p>",
 *          },
 *          "fields": {
 *              "type": {
 *                  "keyword_search": {
 *                      "enabled" : true,
 *                      "label": "Wat",
 *                      "placeholder": "Bv. concert, Bart Peeters,...",
 *                  },
 *                  "group_filters": {
 *                      "enabled": false,
 *                  }
 *              },
 *              "location": {
 *                  "keyword_search": {
 *                      "enabled" : true,
 *                      "label": "Waar",
 *                      "placeholder": "",
 *                  },
 *                  "group_filters": {
 *                      "enabled": false
 *                  }
 *              },
 *              "time": {
 *                  "date_search": {
 *                      "enabled" : true,
 *                      "label": "Waar",
 *                      "placeholder": "Kies een periode",
 *                      "options": {
 *                          "today": true,
 *                          "tomorrow": true,
 *                          "weekend": true,
 *                          "days_7": true,
 *                          "days_14": true,
 *                          "days_30": true,
 *                          "custom_date": true
 *                      },
 *                      "default_option": "placeholder"
 *                  },
 *                  "group_filters": {
 *                      "enabled": false
 *                  }
 *              },
 *              "extra": {
 *                  "group_filters": {
 *                      "enabled": false
 *                  }
 *              },
 *          },
 *          "footer": {
 *              "body": "<a href='http://www.uitinvlaanderen.be' target='_blank'><img border='0' class='cultuurnet-logo-uiv' src='http://tools.uitdatabank.be/sites/all/modules/cul_widgets_server/images/uiv-btn.jpg' alt='Meer tips op UiTinVlaanderen.be' /></a>"
 *          }
 *      },
 *      allowedSettings = {
 *          "general": {
 *              "new_window": "boolean",
 *              "button_label": "string"
 *          },
 *          "header": {
 *              "body": "string",
 *          },
 *          "fields": {
 *              "type": {
 *                  "keyword_search": {
 *                      "enabled" : "boolean",
 *                      "label": "string",
 *                      "placeholder": "string",
 *                  },
 *                  "group_filters": "CultuurNet\ProjectAanvraag\Widget\Settings\GroupFilter"
 *              },
 *              "location": {
 *                  "keyword_search": {
 *                      "enabled" : "boolean",
 *                      "label": "string",
 *                      "placeholder": "string",
 *                  },
 *                  "group_filters": "CultuurNet\ProjectAanvraag\Widget\Settings\GroupFilter"
 *              },
 *              "time": {
 *                  "date_search": {
 *                      "enabled" : "boolean",
 *                      "label": "string",
 *                      "placeholder": "string",
 *                      "options": {
 *                          "today": "boolean",
 *                          "tomorrow": "boolean",
 *                          "weekend": "boolean",
 *                          "days_7": "boolean",
 *                          "days_14": "boolean",
 *                          "days_30": "boolean",
 *                          "custom_date": "boolean"
 *                      },
 *                      "default_option": "string"
 *                  },
 *                  "group_filters": "CultuurNet\ProjectAanvraag\Widget\Settings\GroupFilter"
 *              },
 *              "extra": {
 *                  "group_filters": "CultuurNet\ProjectAanvraag\Widget\Settings\GroupFilter"
 *              },
 *          },
 *          "footer": {
 *              "body": "string"
 *          }
 *      }
 * )
 */
class SearchForm extends WidgetTypeBase implements AlterSearchResultsQueryInterface
{

    /**
     * @var null|\Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var RegionService
     */
    protected $regionService;

    /**
     * @var array
     */
    private $groupFilterTypes = [
        'type',
        'location',
        'time',
        'extra',
    ];

    /**
     * WidgetTypeBase constructor.
     * @param array $pluginDefinition
     * @param array $configuration
     * @param bool $cleanup
     * @param \Twig_Environment $twig
     * @param TwigPreprocessor $twigPreprocessor
     * @param RendererInterface $renderer
     * @param RequestStack $requestStack
     */
    public function __construct(array $pluginDefinition, array $configuration, bool $cleanup, \Twig_Environment $twig, TwigPreprocessor $twigPreprocessor, RendererInterface $renderer, RequestStack $requestStack, RegionService $regionService)
    {
        parent::__construct($pluginDefinition, $configuration, $cleanup, $twig, $twigPreprocessor, $renderer);
        $this->request = $requestStack->getCurrentRequest();
        $this->regionService = $regionService;
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
            $container['widget_renderer'],
            $container['request_stack'],
            $container['widget_region_service']
        );
    }
    /**
     * {@inheritdoc}
     */
    public function render()
    {
        return $this->twig->render(
            'widgets/search-form-widget/search-form-widget.html.twig',
            [
                'id' => $this->index, // Use the index as identifier for smaller querystrings.
                'settings_general' => $this->settings['general'],
                'settings_header' => $this->settings['header'],
                'settings_footer' => $this->settings['footer'],
                'settings_fields' => $this->settings['fields'],
                'defaults' => $this->getDefaults(),
                'when_autocomplete_path' => $this->request->getScheme() . '://' . $this->request->getHost() . $this->request->getBaseUrl() . '/widgets/autocomplete/regions',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderPlaceholder()
    {
        $this->renderer->attachJavascript(WWW_ROOT . '/assets/js/widgets/search-form/search-form.js');
        $this->renderer->attachJavascript(WWW_ROOT . '/assets/js/widgets/search-form/autocomplete.js');

        if ($this->settings['fields']['time']['date_search']['enabled']) {
            $this->renderer->attachJavascript(WWW_ROOT . '/assets/vendor/pickaday/pickaday.js');
            $this->renderer->attachCss(WWW_ROOT . '/assets/vendor/pickaday/pickaday.css');
        }

        return $this->render();
    }

    /**
     * Get the default values based on current request.
     */
    protected function getDefaults()
    {
        $defaults = [];
        if ($this->settings['fields']['time']['date_search']['enabled']) {
            $defaults['when'] = $this->settings['fields']['time']['date_search']['default_option'];
        }

        foreach ($this->groupFilterTypes as $typeKey => $type) {
            if ($this->settings['fields'][$type]['group_filters']['enabled']) {
                foreach ($this->settings['fields'][$type]['group_filters']['filters'] as $key => $groupFilter) {
                    $defaults[$typeKey]['group_filters'][$key] = [-1];
                    if (isset($groupFilter['default_option'])) {
                        foreach ($groupFilter['options'] as $optionKey => $option) {
                            if ($option['label'] === $groupFilter['default_option']) {
                                $defaults[$typeKey]['group_filters'][$key] = [$optionKey];
                            }
                        }
                    }
                }
            }
        }

        return $defaults;
    }

    /**
     * {@inheritdoc}
     */
    public function alterSearchResultsQuery(SearchResultsQueryAlter $searchResultsQueryAlter)
    {

        // Check what filters should be placed active.
        $activeFilters = $this->getDefaults();
        if ($this->request->query->has('search-form')) {
            $searchFormFilters = $this->request->query->get('search-form');
            if (isset($searchFormFilters[$this->index])) {
                foreach ($searchFormFilters[$this->index] as $key => $activeFilter) {
                    // Loop through every custom group filter that is found in query string.
                    if ($key === 'custom' && is_array($activeFilter)) {
                        foreach ($activeFilter as $groupFilterKey => $groupFilterGroups) {
                            if (is_array($groupFilterGroups)) {
                                foreach ($groupFilterGroups as $groupKey => $groupFilterSubmittedValue) {
                                    if (!is_numeric($groupFilterSubmittedValue)) {
                                        $activeFilters[$groupFilterKey]['group_filters'][$groupKey] = explode('|', $groupFilterSubmittedValue);
                                    } else {
                                        $activeFilters[$groupFilterKey]['group_filters'][$groupKey] = [$groupFilterSubmittedValue];
                                    }
                                }
                            }
                        }
                    } elseif (!empty($activeFilter)) {
                        $activeFilters[$key] = $activeFilter;
                    }
                }
            }
        }

        // Add every active filter to the query.
        $advancedQuery = [];
        $searchResultsActiveFilters = $searchResultsQueryAlter->getActiveFilters();
        foreach ($activeFilters as $key => $activeValue) {
            // Group filters => Search the options related with the default option.
            if (is_numeric($key) && isset($activeFilters[$key]['group_filters'])) {
                if (isset($this->groupFilterTypes[$key])) {
                    $type = $this->groupFilterTypes[$key];
                    foreach ($activeFilters[$key]['group_filters'] as $groupFilterKey => $selectedOptions) {
                        if (isset($this->settings['fields'][$type]['group_filters']['filters'][$groupFilterKey])) {
                            $groupFilter = $this->settings['fields'][$type]['group_filters']['filters'][$groupFilterKey];
                            foreach ($selectedOptions as $selectedOption) {
                                if (isset($groupFilter['options'][$selectedOption])) {
                                    $advancedQuery[] = $groupFilter['options'][$selectedOption]['query'];
                                    $searchResultsActiveFilters[] = [
                                        'label' => $groupFilter['options'][$selectedOption]['label'],
                                        'name' => 'search-form[' . $this->index . '][custom][' . $key . '][' . $groupFilterKey . ']',
                                        'is_default' => $groupFilter['default_option'] === $groupFilter['options'][$selectedOption]['label'],
                                    ];
                                }
                            }
                        }
                    }
                }
            } elseif ($key === 'when') {
                // Custom date requested? Construct the date range.
                if ($activeValue === 'custom_date') {
                    $cetTimezone = new \DateTimeZone('CET');
                    $query = '';
                    $labelParts = ['activiteiten'];
                    if (isset($activeFilters['date-start'])) {
                        $dateTime = \DateTime::createFromFormat('d/m/Y', $activeFilters['date-start'], $cetTimezone);
                        if ($dateTime) {
                            $dateTime->setTime(0, 0, 0);
                            $query .= $dateTime->format('c');
                        }
                        $labelParts[] = 'van ' . $activeFilters['date-start'];
                    } else {
                        $query .= '*';
                    }
                    if (isset($activeFilters['date-end'])) {
                        $dateTime = \DateTime::createFromFormat('d/m/Y', $activeFilters['date-end'], $cetTimezone);
                        if ($dateTime) {
                            $dateTime->setTime(23, 59, 59);
                            $query .= (' TO ' . $dateTime->format('c'));
                            $labelParts[] = 'tot ' .  $activeFilters['date-end'];
                        }
                    } else {
                        $query .= ' TO *';
                    }

                    $advancedQuery[] = 'dateRange:[' . $query . ']';

                    $searchResultsActiveFilters[] = [
                        'label' => implode(' ', $labelParts),
                        'name' => 'search-form[' . $this->index . '][when]',
                        'is_default' => false,
                    ];
                } else {
                    // Create ISO-8601 daterange from datetype.
                    $dateRange = $this->convertDateTypeToDateRange($activeValue);
                    if (!empty($dateRange)) {
                        $advancedQuery[] = 'dateRange:' . $dateRange['query'];

                        $searchResultsActiveFilters[] = [
                            'label' => $dateRange['label'],
                            'name' => 'search-form[' . $this->index . '][when]',
                            'is_default' => false,
                        ];
                    }
                }
            } elseif ($key === 'what') {
                $advancedQuery[] = $activeValue;
                $searchResultsActiveFilters[] = [
                    'label' => $activeValue,
                    'name' => 'search-form[' . $this->index . '][what]',
                    'is_default' => false,
                ];
            } elseif ($key === 'where') {
                $region = $this->regionService->getItemByName($activeValue);
                if ($region) {
                    $searchResultsActiveFilters[] = [
                        'label' => $region->name,
                        'name' => 'search-form[' . $this->index . '][where]',
                        'is_default' => false,
                    ];
                    $advancedQuery[] = 'regions:' . $region->key;
                }
            }
        }


        if (!empty($advancedQuery)) {
            $searchResultsQueryAlter->setActiveFilters($searchResultsActiveFilters);
            $searchResultsQueryAlter->getSearchQuery()->addParameter(
                new Query(implode($advancedQuery, ' AND '))
            );
        }
    }
}
