<?php

namespace CultuurNet\ProjectAanvraag\Widget\EventListener;

use CultuurNet\ProjectAanvraag\Widget\AlterSearchResultsQueryInterface;
use CultuurNet\ProjectAanvraag\Widget\Event\SearchResultsQueryAlter;
use CultuurNet\ProjectAanvraag\Widget\LayoutInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Listener on the search result query alter event.
 */
class SearchResultsQueryAlterListener
{

    /**
     * @var null|\Symfony\Component\HttpFoundation\Request
     */
    private $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * Handle the event
     * @param SearchResultsQueryAlter $searchResultsQueryAlter
     */
    public function handle(SearchResultsQueryAlter $searchResultsQueryAlter)
    {

        // No page in this request, nothing to alter.
        if (!$this->request->attributes->has('widgetPage')) {
            return;
        }

        $widgetPage = $this->request->attributes->get('widgetPage');

        $rows = $widgetPage->getRows();
        /** @var LayoutInterface $row */
        foreach ($rows as $row) {
            $widgets = $row->getWidgets();
            foreach ($widgets as $widget) {
                if ($widget instanceof AlterSearchResultsQueryInterface) {
                    $widget->alterSearchResultsQuery($searchResultsQueryAlter);
                }
            }
        }
    }
}
