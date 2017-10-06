<?php

namespace CultuurNet\ProjectAanvraag\Widget\Controller;

use CultuurNet\ProjectAanvraag\Guzzle\Cache\FixedTtlCacheStorage;
use CultuurNet\ProjectAanvraag\Widget\Entities\WidgetPageEntity;
use CultuurNet\ProjectAanvraag\Widget\Entities\WidgetRowEntity;
use CultuurNet\ProjectAanvraag\Widget\JavascriptResponse;
use CultuurNet\ProjectAanvraag\Widget\LayoutDiscovery;
use CultuurNet\ProjectAanvraag\Widget\LayoutManager;
use CultuurNet\ProjectAanvraag\Widget\Renderer;
use CultuurNet\ProjectAanvraag\Widget\RendererInterface;
use CultuurNet\ProjectAanvraag\Widget\WidgetPageEntityDeserializer;
use CultuurNet\ProjectAanvraag\Widget\WidgetPageInterface;
use CultuurNet\ProjectAanvraag\Widget\WidgetPluginManager;
use CultuurNet\ProjectAanvraag\Widget\WidgetTypeDiscovery;
use CultuurNet\SearchV3\PagedCollection;
use CultuurNet\SearchV3\Parameter\Facet;
use CultuurNet\SearchV3\Parameter\Labels;
use CultuurNet\SearchV3\Parameter\Query;
use CultuurNet\SearchV3\SearchClient;
use CultuurNet\SearchV3\SearchQuery;
use CultuurNet\SearchV3\SearchQueryInterface;
use CultuurNet\SearchV3\Serializer\Serializer;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Plugin\Cache\CachePlugin;
use Guzzle\Plugin\Cache\DefaultCacheStorage;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializerBuilder;
use ML\JsonLD\JsonLD;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Model\BSONDocument;
use SimpleBus\JMSSerializerBridge\JMSSerializerObjectSerializer;
use SimpleBus\JMSSerializerBridge\SerializerMetadata;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RequestStack;


/**
 * Provides a controller to render widget pages and widgets.
 */
class WidgetController
{

    /**
     * @var RendererInterface
     */
    protected $renderer;

    /**
     * @var DocumentRepository
     */
    protected $widgetRepository;

    /**
     * @var SearchClient
     */
    protected $searchClient;

    /**
     * @var WidgetPageEntityDeserializer
     */
    protected $widgetPageEntityDeserializer;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var RequestStack
     */
    protected $request;

    /**
     * @var bool
     */
    protected $debugMode;

    /**
     * WidgetController constructor.
     *
     * @param RendererInterface $renderer
     * @param DocumentRepository $widgetRepository
     * @param Connection $db
     */
    public function __construct(RendererInterface $renderer, DocumentRepository $widgetRepository, Connection $db, SearchClient $searchClient, WidgetPageEntityDeserializer $widgetPageEntityDeserializer, \Twig_Environment $twig, RequestStack $requestStack, bool $debugMode)
    {
        $this->renderer = $renderer;
        $this->widgetRepository = $widgetRepository;
        $this->searchClient = $searchClient;
        $this->widgetPageEntityDeserializer = $widgetPageEntityDeserializer;
        $this->twig = $twig;
        $this->request = $requestStack->getCurrentRequest();
        $this->debugMode = $debugMode;

/*        $json = file_get_contents(__DIR__ . '/../../../test/Widget/data/page.json');
        $doc = json_decode($json, true);
        $collection->insert($doc);
        die();*/

/*        $layoutDiscovery = new LayoutDiscovery();
        $layoutDiscovery->register(__DIR__ . '/../WidgetLayout', 'CultuurNet\ProjectAanvraag\Widget\WidgetLayout');

        $typeDiscovery = new WidgetTypeDiscovery();
        $typeDiscovery->register(__DIR__ . '/../WidgetType', 'CultuurNet\ProjectAanvraag\Widget\WidgetType');

        $layoutManager = new WidgetPluginManager($layoutDiscovery);
        $test = $layoutManager->createInstance('one-col');

        $widgetTypeManager = new WidgetPluginManager($typeDiscovery);
        $test2 = $widgetTypeManager->createInstance('search-form');
print_r($test);
print_r($test2);
        die();*/

        /*$results = $collection->find();
        while ($results->hasNext()) {
            $document = $results->getNext();
            //print '<pre>' . print_r($document, true) . '</pre>';
        }*/
    }

    /**
     * Hardcoded example of a render page.
     */
    public function renderPage(Request $request, WidgetPageInterface $widgetPage)
    {
        $javascriptResponse = new JavascriptResponse($this->renderer, $this->renderer->renderPage($widgetPage));

        // Only write the javascript files, when we are not in debug mode.
        if (!$this->debugMode) {
            $directory = dirname(WWW_ROOT . $request->getPathInfo());
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }
            file_put_contents(WWW_ROOT . $request->getPathInfo(), $javascriptResponse->getContent());
        }

        return $javascriptResponse->getContent();
    }

    /**
     * Render the given widget and return it as a json response.
     *
     * @param Request $request
     * @param WidgetPageInterface $widgetPage
     * @param $widgetId
     * @return JsonResponse
     */
    public function renderWidget(Request $request, WidgetPageInterface $widgetPage, $widgetId)
    {

        $widget = null;
        $rows = $widgetPage->getRows();

        // Search for the requested widget.
        foreach ($rows as $row) {
            if ($row->hasWidget($widgetId)) {
                $widget = $row->getWidget($widgetId);
            }
        }

        if (empty($widget)) {
            throw new NotFoundHttpException();
        }

        $data = [
            'data' => $this->renderer->renderWidget($widget),
        ];
        $response = new JsonResponse($data);

        // If this is a jsonp request, set the requested callback.
        if ($request->query->has('callback')) {
            $response->setCallback($request->query->get('callback'));
        }

        return $response;
    }

    /**
     * Example of a search request.
     */
    public function searchExample()
    {
        $query = new SearchQuery(true);
        $query->addParameter(new Facet('regions'));
        $query->addParameter(new Facet('types'));
        //$query->addParameter(new Labels('bouwen'));
        //$query->addParameter(new Labels('Kiditech'));
        $query->addParameter(new Query('regions:gem-leuven OR regions:gem-gent'));

        $query->addSort('availableTo', SearchQueryInterface::SORT_DIRECTION_ASC);

        $result = $this->searchClient->searchEvents($query);
        print_r($result);
        die();
    }

    /**
     * Social share proxy page.
     */
    public function socialShareProxy($cdbid) {
        // Get origin url.
        $originUrl = ($this->request->query->get('origin') ? $this->request->query->get('origin') : '');

        // Retrieve event corresponding to ID.
        $query = new SearchQuery(true);
        $query->addParameter(
            new Query($cdbid)
        );
        // Retrieve results from Search API.
        $result = $this->searchClient->searchEvents($query);
        $items = $result->getMember()->getItems();

        if (!empty($items)) {
            $event = $items[0];
            return $this->twig->render(
                'widgets/widget-share-proxy.html.twig',
                [
                    'name' => $event->getName()['nl'],
                    'description' => $event->getDescription()['nl'],
                    'image' => $event->getImage(),
                    'url' => $originUrl
                ]
            );
        }
        else {
            // TODO: redirect back to origin URL?
            return 'foo';
        }
    }
}
