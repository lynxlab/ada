<?php

/**
 * index.php
 *
 * @package        API
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2024, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           API
 * @version        0.1
 */

use DI\ContainerBuilder;
use Fig\Http\Message\StatusCodeInterface;
use Lynxlab\ADA\API\Controllers\Common\AdaApiInterface;
use Lynxlab\ADA\API\Controllers\Common\APIException;
use Lynxlab\ADA\API\Controllers\TokenController;
use Lynxlab\ADA\API\Middleware\CleanQueryString;
use Lynxlab\ADA\API\Middleware\FormatSupported;
use Lynxlab\ADA\API\Middleware\OAuth2Auth;
use Lynxlab\ADA\API\Routes\Routes;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Exception\HttpNotFoundException;
use Slim\Routing\RouteCollectorProxy;

require_once __DIR__ . '/bootstrap.php';

/**
 * Build DI container instance
 */
$container = (new ContainerBuilder())
    ->addDefinitions(__DIR__ . '/config/config.php')
    ->addDefinitions(__DIR__ . '/config/container.php')
    ->build();

/**
 * Create App instance
 */
$app =  $container->get(App::class);

/**
 * OAuth2 token route.
 */
$app->map(['GET', 'POST'], '/token', TokenController::class . ':getAccessToken');

/**
 * All other routes, as defined in Routes\Routes.php
 */
$routes = Routes::getEndpoints();
$publicRoutes = Routes::getPublicEndpoints();

foreach ($routes as $version => $versionRoutes) {
    $version = strtolower($version);

    /**
     * Add default empty group route to return 404.
     */
    $app->group('/' . $version, function (RouteCollectorProxy $group) {
        $group->any('/', function (Request $request, Response $response) {
            throw new HttpNotFoundException($request);
        });
    });

    foreach ($versionRoutes as $endpoint => $routeData) {
        $endpoint = strtolower($endpoint);

        /**
         * Allow preflight requests.
         * Due to the behaviour of browsers when sending a request,
         * you must add the OPTIONS method.
         */
        $app->options(
            '/' . $version . '/' . $endpoint . '[.{format}]',
            fn (Request $request, Response $response) => $response
        );

        foreach ($routeData['methods'] as $method) {
            $method = strtolower($method);
            /**
             * TODO: VERIFICARE COSA SUCCEDE SE SI PASSA BODY IN JSON O CSV,
             * CON L'HEADER application/json etc...
             * vecchio middleware ContentTypes
             */
            // group by version number: v1, v2, etc...
            $app->group('/' . $version, function (RouteCollectorProxy $group) use ($app, $method, $endpoint, $routeData, $version, $publicRoutes) {
                /**
                 * @var \Slim\Interfaces\RouteInterface $appRoute
                 */
                $appRoute = $group->$method('/' . $endpoint . '[.{format}]', function (Request $request, Response $response, array $parameters) use ($app, $method, $endpoint, $routeData, $version) {
                    try {
                        $controllerClassName = $app->getContainer()->get('basenamespace') . 'Controllers\\' . $version . '\\' . $routeData['controllerclass'];
                        $controller = new $controllerClassName($app->getContainer(), $request->getAttribute('authUserID'));
                        if ($controller instanceof AdaApiInterface) {
                            $args = array_merge(
                                $request->getQueryParams() ?? [],
                                $request->getParsedBody() ?? []
                            );
                            // Call controller, with namespace depending on called API version.
                            $data = $controller->{$method}($request, $response, $args);

                            $format = $parameters['format'] ?? $app->getContainer()->get('defaultFormat');
                            $renderClassName = $app->getContainer()->get('basenamespace') . 'Views\\' . ucfirst($format);
                            $renderClass = new $renderClassName($app->getContainer());

                            // Return response build by renderClass render method.
                            return call_user_func([$renderClass, 'render'], $response, $data, $endpoint);
                        } else {
                            throw (new APIException('', StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR))->setParams([
                                'error_description' => $controllerClassName . ' must implement AdaApiInterface',
                            ]);
                        }
                    } catch (APIException $th) {
                        $response = $response->withStatus($th->getCode(), $th->getMessage());
                        $response = $response->withHeader('Content-Type', 'application/json');
                        $response->getBody()->write(json_encode(
                            array_merge([
                                'error' => empty($th->getMessage()) ? $response->getReasonPhrase() : $th->getMessage(),
                            ], $th->getParams())
                        ));
                        return $response;
                    } catch (Throwable $th) {
                        $response = $response->withStatus($th->getCode(), $th->getMessage());
                        $response->getBody()->write($th->getMessage());
                        return $response;
                        // TODO: DECIDERE COSA FARE SE LA CLASSE VIEWER NON C'È
                        // return JsonView::render($response, $data);
                        //throw $th;
                    }
                })->setName($version . '.' . $endpoint . '.' . $method);

                // do not mess up middlewares order!

                if (!in_array($endpoint, array_keys($publicRoutes[$version]))) {
                    $appRoute->add(OAuth2Auth::class);
                }

                $appRoute
                    ->addMiddleware(new CleanQueryString())
                    ->addMiddleware(
                        new FormatSupported(
                            $app->getContainer(),
                            $app->getResponseFactory(),
                            $routeData['extraformats'] ?? []
                        )
                    );
            });
        }
    }
}

$app->run();
