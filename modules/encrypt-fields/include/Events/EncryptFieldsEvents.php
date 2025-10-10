<?php

/**
 * @package     event-dispatcher module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Encryptfields\Events;

use Lynxlab\ADA\Module\EventDispatcher\ADAEventTrait;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * EncryptFieldsEvents class
 */
final class EncryptFieldsEvents extends GenericEvent
{
    use ADAEventTrait;

    /**
     * event own namespace
     */
    public const NAMESPACE = 'encryptfields';

    /**
     * The POSTFIELDSCONFIG event occurs just before the FieldsConfig::getAllFields
     * returns the config array so that the event listener may add its own config.
     *
     * NOTE:
     * - Tables and fields from the core module cannot be modified and or
     *   removed.
     * - Event listener must set an argument named as the event subject that must
     *   be an array like the core one (e.g. with '<evnetsubjcet>' as first
     *   level keys, holding the 'fields' key, see
     *   modules/encrypt-fields/include/FieldsConfig.php for an example)
     *
     *
     * @EncryptFieldsEvents
     *
     * @var string
     */
    public const POSTFIELDSCONFIG = self::NAMESPACE . '.fieldsconfig.post';
}
