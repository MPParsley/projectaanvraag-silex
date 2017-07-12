<?php

namespace CultuurNet\ProjectAanvraag\Widget;

use CultuurNet\ProjectAanvraag\Widget\Entities\WidgetPageEntity;

/**
 * Provides a deserializer for widget pages.
 */
class WidgetPageEntityDeserializer
{

    /**
     * @var WidgetPluginManager
     */
    protected $widgetLayoutManager;

    /**
     * @var WidgetPluginManager
     */
    protected $widgetTypeManager;

    /**
     * WidgetPageEntityDeserializer constructor.
     * @param WidgetPluginManager $widgetLayoutManager
     * @param WidgetPluginManager $widgetTypeManager
     */
    public function __construct(WidgetPluginManager $widgetLayoutManager, WidgetPluginManager $widgetTypeManager)
    {
        $this->widgetLayoutManager = $widgetLayoutManager;
        $this->widgetTypeManager = $widgetTypeManager;
    }

    /**
     * Deserialize a given JSON to a valid widget page entity.
     * @param $json
     */
    public function deserialize($json)
    {

        $jsonObject = json_decode($json, TRUE);

        $widgetPageEntity = new WidgetPageEntity();
        $widgetPageEntity->setTitle($jsonObject['title']);

        $rows = [];
        if (isset($jsonObject['rows']) && is_array($jsonObject['rows'])) {
            foreach ($jsonObject['rows'] as $row) {
                $rows[] = $this->widgetLayoutManager->createInstance($row['type'], $row, true);
            }
        }

        $widgetPageEntity->setRows($rows);

        return $widgetPageEntity;
    }

}