<?php
/**
 * Translationsuite plugin for Craft CMS 3.x
 *
 * The one and only translation plugin you'll ever need.
 *
 * @link      moshimoshi.be
 * @copyright Copyright (c) 2021 Moshi Moshi
 */

namespace moshimoshi\translationsuite\services;

use craft\i18n\I18N;
use moshimoshi\translationsuite\Translationsuite;

use Craft;
use craft\base\Component;

/**
 * TranslationsuiteService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Moshi Moshi
 * @package   Translationsuite
 * @since     1.0.0
 */
class TranslationsuiteService extends Component
{
    // Public Methods
    // =========================================================================

    public function getAvailableCategories(): array
    {
        /** @var I18N $i18n */
        $i18n = Craft::$app->getComponents(false)['i18n'];
        $categories = $i18n->translations;
        dd($categories);
        return [];
    }
}
