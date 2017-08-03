<?php

namespace CultuurNet\ProjectAanvraag\Widget;

use CultuurNet\ProjectAanvraag\IntegrationType\Controller\IntegrationTypeController;
use CultuurNet\ProjectAanvraag\Widget\Controller\WidgetApiController;
use CultuurNet\ProjectAanvraag\Widget\Controller\WidgetController;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Silex\ControllerCollection;

class WidgetControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['widget_renderer'] = function (Application $app) {
            return new Renderer();
        };

        $app['widget_controller'] = function (Application $app) {
            return new WidgetController($app['widget_renderer'], $app['widget_repository'], $app['mongodb'], $app['search_api']);
        };

        $app['widget_builder_api_controller'] = function (Application $app) {
            return new WidgetApiController($app['command_bus'], $app['widget_repository'], $app['widget_type_discovery'], $app['widget_page_deserializer'], $app['security.authorization_checker']);
        };

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];
        $controllers->get('api/widget-types', 'widget_builder_api_controller:getWidgetTypes');
          $controllers->get('api/project/{project}/widget-page', 'widget_builder_api_controller:updateWidgetPage')
            ->convert('project', 'project_convertor:convert');
        $controllers->put('api/test', 'widget_builder_api_controller:test');
        $controllers->post('api/test', 'widget_builder_api_controller:test');
        $controllers->get('api/test', 'widget_builder_api_controller:test');

        $controllers->get('/', 'widget_controller:renderPage');
        $controllers->get('/search', 'widget_controller:searchExample');

        return $controllers;
    }
}
