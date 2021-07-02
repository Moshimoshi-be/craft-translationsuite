<?php

namespace moshimoshi\translationsuite\variables;

use craft\helpers\Template;
use nystudio107\pluginvite\variables\ViteVariableInterface;
use nystudio107\pluginvite\variables\ViteVariableTrait;
use Twig\Markup;
use yii\di\ServiceLocator;

/**
 * Translationsuite Variable
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    Moshi Moshi
 * @package   Translationsuite
 * @since     1.0.0
 */
class TranslationsuiteVariable implements ViteVariableInterface
{
    use ViteVariableTrait;


    /**
     * Injects translations in a namespace in the Window object.
     * @param string $category
     * @param string|null $locale
     * @return Markup
     */
    public function injectTranslations(string $category, string $locale = null): Markup
    {
        if (!$locale) {
            $locale = \Craft::$app->locale;
        }

        return Template::raw('<script>alert("Hello there")</script>');
    }
}
