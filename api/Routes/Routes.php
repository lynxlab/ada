<?php

namespace Lynxlab\ADA\API\Routes;

class Routes
{
    /**
     * Define an array of public endpoints,
     * that will bypass the OAuth2 authentication.
     *
     * @return array
     */
    public static function getPublicEndpoints(): array
    {
        $retArr = [];
        foreach (self::getEndpoints() as $version => $versionRoutes) {
            $retArr[$version] = array_filter(
                $versionRoutes,
                fn ($el) => $el['public'] ?? false,
            );
        }
        return $retArr;
    }

    /**
     * Define here all the endpoints (in the array keys) with the
     * associated controller classes and methods to respond to.
     */
    public static function getEndpoints(): array
    {
        return [
            'v1' => [
                'users' => [
                    'controllerclass' => 'UserController',
                    'methods' => [
                        'GET', 'POST',
                    ],
                ],
                'testers' => [
                    'public' => MULTIPROVIDER,
                    'controllerclass' => 'TesterController',
                    'methods' => [
                        'GET',
                    ],
                ],
                'subscriptions' => [
                    'controllerclass' => 'SubscriptionController',
                    'methods' => [
                        'POST',
                    ],
                ],
                'info' => [
                    'public' => true,
                    'controllerclass' => 'InfoController',
                    'methods' => [
                        'GET',
                    ],
                    'extraformats' => [
                        'rss1', 'rss2', 'atom',
                    ],
                ],
            ],
        ];
    }
}
