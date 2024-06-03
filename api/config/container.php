<?php

use DI\NotFoundException;
use Lynxlab\ADA\API\Middleware\ADAHeaders;
use Lynxlab\ADA\API\Middleware\EnableCORS;
use Lynxlab\ADA\API\Middleware\MaintenanceMode;
use Lynxlab\ADA\API\Middleware\ResolveLatestVersion;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteParserInterface;

return [
    App::class => function (ContainerInterface $container) {
        $app = AppFactory::createFromContainer($container);

        $app->addBodyParsingMiddleware();
        $app->add(ADAHeaders::class);
        $app->add(EnableCORS::class);
        $app->addRoutingMiddleware();
        $app->add(ResolveLatestVersion::class);
        $app->add(MaintenanceMode::class);

        try {
            if ($app->getContainer()->get('error')) {
                $cError = $app->getContainer()->get('error');
                $app->addErrorMiddleware(
                    $cError['displayErrorDetails'] ?? false,
                    $cError['logErrors'] ?? false,
                    $cError['logErrorDetails'] ?? false
                );
                unset($cError);
            }
        } catch (NotFoundException) {
            // resume execution without the error middleware.
        }

        $app->setBasePath($app->getContainer()->get('basepath'));
        return $app;
    },

    // HTTP factories
    ResponseFactoryInterface::class => fn (ContainerInterface $container) => $container->get(Psr17Factory::class),

    ServerRequestFactoryInterface::class => fn (ContainerInterface $container) => $container->get(Psr17Factory::class),

    StreamFactoryInterface::class => fn (ContainerInterface $container) => $container->get(Psr17Factory::class),

    UploadedFileFactoryInterface::class => fn (ContainerInterface $container) => $container->get(Psr17Factory::class),

    UriFactoryInterface::class => fn (ContainerInterface $container) => $container->get(Psr17Factory::class),

    // The Slim RouterParser
    RouteParserInterface::class => fn (ContainerInterface $container) => $container->get(App::class)->getRouteCollector()->getRouteParser(),

];
