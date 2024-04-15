<?php

use Lynxlab\ADA\Module\EventDispatcher\Subscribers\ADAMethodSubscriberInterface;

// Trigger: ClassWithNameSpace. The class ADAMethodSubscriberInterface was declared with namespace Lynxlab\ADA\Module\EventDispatcher\Subscribers. //

namespace Lynxlab\ADA\Module\EventDispatcher\Subscribers;

interface ADAMethodSubscriberInterface
{
    public static function getSubscribedMethods();
}
