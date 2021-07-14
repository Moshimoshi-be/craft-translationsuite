<?php

namespace moshimoshi\translationsuite\variables;

use craft\helpers\Json;
use craft\helpers\Template;
use nystudio107\pluginvite\variables\ViteVariableInterface;
use nystudio107\pluginvite\variables\ViteVariableTrait;
use Twig\Markup;
use yii\di\ServiceLocator;
use yii\helpers\Html;

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

    /**
     * Injects the languages of the site in the window element in our namespace "Translationsuite".
     * @return Markup
     */
    public function injectActiveLanguages(): Markup
    {
        // Fetch languages
        $sites = \Craft::$app->sites->allSites;
        $languages = [];
        foreach ($sites as $site) {
            $languages[] = substr($site->language, 0, 2);
        }

        // Encode
        $languages = Json::encode($languages);

        // Create script
        $script = "
            window.Translationsuite = {};
            Translationsuite.activeLanguages = ${languages};
            Translationsuite.getActiveLanguages = function(val) {
                return this.activeLanguages;
            };
        ";

        return Template::raw(Html::tag('script', $script));
    }
}
