<?php

namespace moshimoshi\translationsuite\events;

use yii\base\Event;


/**
 * Throw an event after Translation Suite has cleared its caches.
 *
 * @author    moshimoshi
 * @package   Translationsuite
 * @since     1.0.0
 */
class InvalidateCachesEvent extends Event
{
    /**
     * @var string|null The classname of the component clearing caches.
     */
    public $component;

}