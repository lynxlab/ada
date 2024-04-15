<?php

use Lynxlab\ADA\Module\EventDispatcher\Subscribers\ADAScriptSubscriberInterface;

// Trigger: ClassWithNameSpace. The class ADAScriptSubscriberInterface was declared with namespace Lynxlab\ADA\Module\EventDispatcher\Subscribers. //

namespace Lynxlab\ADA\Module\EventDispatcher\Subscribers;

interface ADAScriptSubscriberInterface
{
    public static function getSubscribedScripts();
}
