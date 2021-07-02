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

use craft\console\Application;
use craft\helpers\FileHelper;
use craft\i18n\I18N;
use moshimoshi\translationsuite\events\InvalidateCachesEvent;
use moshimoshi\translationsuite\Translationsuite;

use Craft;
use craft\base\Component;
use yii\caching\TagDependency;

/**
 * CategoriesService Service
 *
 * Service for general functions like managing settings
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Moshi Moshi
 * @package   Translationsuite
 * @since     1.0.0
 */
class CategoriesService extends Component
{
    // Constants
    // =========================================================================

    const TRANSLATIONSUITE_TRANSLATION_CATEGORIES_CACHE_TAG = 'translationsuite_translation_categories';
    const EVENT_INVALIDATE_GENERAL_CACHES = 'invalidateGeneralCaches';


    // Public Methods
    // =========================================================================

    /**
     * Set the available translation categories for the settings.
     * @return bool
     */
    public function setTranslationsCategoriesSettings(): bool
    {
        /** @var I18N $i18n */
        $i18n = Craft::$app->getComponents(false)['i18n'];
        $categories = array_keys($i18n->translations);
        $categoriesFromFiles = $this->getCategoriesFromFiles();
        $categories = array_merge($categories, $categoriesFromFiles);
        $categories = array_fill_keys($categories, 0);

        $settings = Translationsuite::$settings;
        $existingCategories = $settings->translationCategories;

        // We don't want to override already existing categories and their values.
        $differences = array_diff_key($categories, $existingCategories);
        $newCategories = [];
        foreach ($differences as $k => $difference) {
            if (array_key_exists($k, $existingCategories)) {
                unset($existingCategories[$k]);
            } else {
                $newCategories[$k] = 0;
            }
        }

        $categories = array_merge($existingCategories, $newCategories);
        $settings->translationCategories = $categories;
        Craft::$app->getPlugins()->savePluginSettings(Translationsuite::$plugin, $settings->toArray());

        return true;
    }

    public function getEnabledCategories(): array
    {
        $settings = Translationsuite::$settings;
        $categories = $settings->translationCategories;

        $enabledCategories = array_filter($categories, function($enabled) {
            return $enabled;
        });

        return array_keys($enabledCategories);
    }

    public function invalidateCategoryCaches()
    {
        // Fetch translation categories based on files and add them to the possible categories.
        $this->setTranslationsCategoriesSettings();
        $cache = Craft::$app->getCache();
        TagDependency::invalidate($cache, self::TRANSLATIONSUITE_TRANSLATION_CATEGORIES_CACHE_TAG);
        Craft::info(
            'Cleared general caches',
            __METHOD__
        );

        $event = new InvalidateCachesEvent([
           'component' => self::class,
        ]);

        if (!Craft::$app instanceof Application) {
            $this->trigger(self::EVENT_INVALIDATE_GENERAL_CACHES, $event);
        }
    }

    /**
     * Scans the project translation directories for other files
     *
     * @return array An array containing category names based on the file names.
     */
    private function getCategoriesFromFiles(): array
    {
        $categories = [];

        // Fetched this from the Craft PhpMessageSource.
        $path = Craft::getAlias('@translations', false);
        $translationFiles = FileHelper::findFiles($path, [
            'only' => ['*.php'],
            'recursive' => true,
        ]);
        foreach ($translationFiles as $translationFile) {
            $pathArray = explode('/', $translationFile);
            $file = end($pathArray);
            $fileNameArray = explode('.php', $file);
            $fileName = reset($fileNameArray);
            if (!in_array($fileName, $categories, true)) {
                $categories[] = $fileName;
            }
        }

        return $categories;
    }
}
