<?php
namespace moshimoshi\translationsuite\services;

use Craft;
use craft\console\Application;
use craft\db\Connection;
use craft\db\Query;
use craft\i18n\PhpMessageSource;
use moshimoshi\translationsuite\errors\CategoryNotEnabledException;
use moshimoshi\translationsuite\events\InvalidateCachesEvent;
use moshimoshi\translationsuite\Translationsuite;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;
use yii\db\Expression;
use yii\di\Instance;
use yii\helpers\ArrayHelper;

/**
 * TranslationsuiteMessageSource
 *
 * @author    Moshi Moshi
 * @package   Translationsuite
 * @since     1.0.0
 *
 */
class TranslationsService extends PhpMessageSource
{
    // Constants
    // =========================================================================

    const TRANSLATIONSUITE_CATEGORY_LANGUAGE_CACHE_TAG = 'translationsuite_translations_category_language_cache';
    const TRANSLATIONSUITE_MISSING_TRANSLATIONS_CACHE_TAG = 'translationsuite_missing_translations_cache';
    const TRANSLATIONSUITE_TRANSLATIONS_CATEGORY_CACHE_TAG = 'translationsuite_translations_category_tag';
    const EVENT_INVALIDATE_TRANSLATION_CACHE = 'invalidateTranslationCaches';

    // Public variables
    // =========================================================================

    /**
     * @var Connection $db
     */
    public $db = 'db';

    /**
     * @var CacheInterface
     */
    public $cache = 'cache';

    /**
     * @var int Duration in seconds defining how long the cache can remain valid
     */
    public $cacheDuration = 0;

    /**
     * @var string Name of the source message table.
     */
    public static $sourceMessageTable = '{{%translationsuite_source_message}}';

    /** @var string Name of the message table */
    public static $messageTable = '{{%translationsuite_message}}';

    /** @var bool Save results in cache and attempt to fetch from cache first */
    public $enableCaching = true;

    /** @var bool Should the translation files be used for gathering translations */
    public $useTranslationFiles = true;

    /**
     * Initializes the TranslationsService component.
     * This method will initialize the [[db]] property to make sure it refers to a valid DB connection.
     * Configured [[cache]] component would also be initialized.
     */
    public function init()
    {
        parent::init();

        $this->db = Craft::$app->getDb();
        if ($this->enableCaching) {
            $this->cache = Instance::ensure($this->cache, CacheInterface::class);
        }
    }

    // Protected Methods
    // =========================================================================
    protected function loadMessages($category, $language, $useDb = true): array
    {
        // Load messages from file
        $messages = [];
        $dbMessages = [];
        if ($this->useTranslationFiles) {
            $messages = parent::loadMessages($category, $language);
        }

        if ($useDb) {
            $dbMessages = $this->checkSavedMessages($category, $language);
            $dbMessages = array_filter($dbMessages, function($translation) {
               return !empty($translation);
            });
        }

        // Translations saved in the DB will overwrite translations found in the files.
        $messages = array_merge($messages, $dbMessages);

        return $messages;
    }

    protected function checkSavedMessages($category, $language): array
    {
        if($this->enableCaching) {
            $key = [
                self::TRANSLATIONSUITE_CATEGORY_LANGUAGE_CACHE_TAG,
                $category,
                $language
            ];
            $messages = $this->cache->get($key);

            if (!$messages) {
                $messages = $this->loadMessagesFromDb($category, $language);

                if ($messages) {
                    $this->cache->set(
                        $key,
                        $messages,
                        $this->cacheDuration,
                        new TagDependency(['tags' => self::TRANSLATIONSUITE_CATEGORY_LANGUAGE_CACHE_TAG])
                    );
                }
            }

            return $messages;
        }

        return $this->loadMessagesFromDb($category, $language);
    }

    protected function loadMessagesFromDb($category, $language): array
    {
        $mainQuery = (new Query())->select(['message' => 't1.message', 'translation' => 't2.translation'])
            ->from(['t1' => self::$sourceMessageTable, 't2' => self::$messageTable])
            ->where([
                't1.id' => new Expression('[[t2.id]]'),
                't1.category' => $category,
                't2.language' => $language,
            ]);

        $fallbackLanguage = substr($language, 0, 2);
        $fallbackSourceLanguage = substr($this->sourceLanguage, 0, 2);

        if ($fallbackLanguage !== $language) {
            $mainQuery->union($this->createFallbackQuery($category, $language, $fallbackLanguage), true);
        } elseif ($language === $fallbackSourceLanguage) {
            $mainQuery->union($this->createFallbackQuery($category, $language, $fallbackSourceLanguage), true);
        }

        $messages = $mainQuery->createCommand($this->db)->queryAll();

        return ArrayHelper::map($messages, 'message', 'translation');
    }

    protected function createFallbackQuery($category, $language, $fallbackLanguage)
    {
        return (new Query())->select(['message' => 't1.message', 'translation' => 't2.translation'])
            ->from(['t1' => self::$sourceMessageTable, 't2' => self::$messageTable])
            ->where([
                't1.id' => new Expression('[[t2.id]]'),
                't1.category' => $category,
                't2.language' => $fallbackLanguage,
            ])->andWhere([
                'NOT IN', 't2.id', (new Query())->select('[[id]]')->from(self::$messageTable)->where(['language' => $language]),
            ]);
    }


    protected function loadMessagesFromDbByCategory(string $category, array $languages, array $filters = []): array
    {
        $where = [
            't1.id' => new Expression('[[t2.id]]'),
            't1.category' => $category,
            't2.language' => $languages,
        ];

        $where = array_merge($where, $filters);

        $mainQuery = (new Query())
            ->select(['id' => 't1.id', 'message' => 't1.message', 'category' => 't1.category', 'translation' => 't2.translation', 'language' => 't2.language'])
            ->from(['t1' => self::$sourceMessageTable, 't2' => self::$messageTable])
            ->where($where);

        $fallbackSourceLanguage = substr($this->sourceLanguage, 0, 2);
        foreach ($languages as $language) {
            $fallbackLanguage = substr($language, 0, 2);

            if ($fallbackLanguage !== $language) {
                $mainQuery->union($this->createFallbackQueryForCategory($category, $language, $fallbackLanguage), true);
            } elseif ($language === $fallbackSourceLanguage) {
                $mainQuery->union($this->createFallbackQueryForCategory($category, $language, $fallbackSourceLanguage), true);
            }
        }

        return $mainQuery->createCommand($this->db)->queryAll();
    }

    protected function createFallbackQueryForCategory(string $category, string $language, string $fallbackLanguage, array $filters = [])
    {
        $where = [
            't1.id' => new Expression('[[t2.id]]'),
            't1.category' => $category,
            't2.language' => $fallbackLanguage,
        ];
        $where = array_merge($where, $filters);

        return (new Query())
            ->select(['id' => 't1.id', 'message' => 't1.message', 'category' => 't1.category', 'translation' => 't2.translation', 'language' => 't2.language'])
            ->from(['t1' => self::$sourceMessageTable, 't2' => self::$messageTable])
            ->where($where)
            ->andWhere([
                'NOT IN', 't2.id', (new Query())->select('[[id]]')->from(self::$messageTable)->where(['language' => $language]),
            ]);
    }


    protected function loadMissingMessages(): array
    {
        $query = (new Query())
            ->select('id')
            ->from(self::$messageTable)
            ->where([
                'translation' => ""
            ])
            ->orWhere(['translation' => null]);
        $emptyTranslations = $query->createCommand($this->db)->queryColumn();


        $mainQuery = (new Query())
            ->select(['id'=> 't2.id', 'message' => 't1.message', 'category' => 't1.category', 'translation' => 't2.translation', 'language' => 't2.language'])
            ->from(['t2' => self::$messageTable])
            ->leftJoin(['t1' => self::$sourceMessageTable], 't1.id = t2.id')
            ->where(['t2.id' => $emptyTranslations]);

        return $mainQuery->createCommand($this->db)->queryAll();
    }

    public function getTranslations(string $category, bool $useFiles = true): array
    {
        $translations = [];

        if (!$translations) {
            $categories = $this->getEnabledCategories();
            if (!in_array($category, $categories)) {
                throw new CategoryNotEnabledException(Craft::t('translationsuite', 'This category "{category}" is not managed by {handle}.', [
                    'category' => $category,
                    'handle' => Translationsuite::$plugin->handle
                ]));
            }
            $locales = Craft::$app->i18n->getSiteLocaleIds();

            // This is later on used for the language generation.
            $availableLocales = [];
            foreach ($locales as $locale) {
                $short = substr($locale, 0, 2);
                $availableLocales[$short] = [
                    'locale' => $short,
                ];
            }

            if ($useFiles && $this->useTranslationFiles) {
                foreach ($locales as $locale) {
                    // Mapping happens by broad locale
                    $generalizedLocale = substr($locale, 0, 2);
                    $messages = $this->loadMessages($category, $locale, false);

                    // Let's map it to the right format.
                    foreach ($messages as $message => $translation) {

                        // Hey! Something is already there!
                        // Let's add some more stuff to it!
                        if (isset($translations[$message])) {
                            // Set both the locale and the translation
                            // We set the locale here as well since it might be another language that was there for the message
                            // In this case the locale wouldn't be set for the language we're adding.
                            $translations[$message]['languages'][$generalizedLocale]['locale'] = $generalizedLocale;
                            $translations[$message]['languages'][$generalizedLocale]['file'] = $translation;

                            continue; // Our work for this cycle is done, move ahead!
                        }

                        // Setup basic structure
                        $translations[$message] = [
                            'message' => $message,
                            'category' => $category,
                            'languages' => $availableLocales
                        ];

                        $translations[$message]['languages'][$generalizedLocale]['file'] = $translation;
                    }
                }
            }

            // Query translations from the DB
            $translationsFromDb = $this->loadMessagesFromDbByCategory($category, $locales);

            foreach ($translationsFromDb as $dbTranslation) {
                $message = $dbTranslation['message'];
                $category = $dbTranslation['category'];
                $translation = $dbTranslation['translation'];
                $locale = $dbTranslation['language'];

                // Hey! Something is already there!
                if (isset($translations[$message])) {
                    $translations[$message]['languages'][$locale]['locale'] = $locale;
                    $translations[$message]['languages'][$locale]['db'] = $translation;

                    continue; // Our work for this cycle is done, move ahead!
                }

                $translations[$message] = [
                    'selected' => false,
                    'message' => $message,
                    'category' => $category,
                    'languages' => $availableLocales
                ];

                $translations[$message]['languages'][$locale]['db'] = $translation;
            }

            if ($this->enableCaching) {
                $this->cache->set([
                   self::TRANSLATIONSUITE_CATEGORY_LANGUAGE_CACHE_TAG,
                   $category
                ], $translations, 0, new TagDependency(['tags' => self::TRANSLATIONSUITE_CATEGORY_LANGUAGE_CACHE_TAG]));
            }
        }

        return $translations;
    }

    public function getAllTranslations(bool $useFiles): array
    {
        $translations = [];
        $categories = $this->getEnabledCategories();
        foreach ($categories as $category) {
            $translations[$category] = $this->getTranslations($category, $useFiles);
        }

        return $translations;
    }

    public function getMissingTranslations(bool $useFiles = true): array {
        $translations = [];
        $dbTranslations = [];

        // $categories = $this->getEnabledCategories();
        $locales = Craft::$app->i18n->getSiteLocaleIds();

        $availableLocales = [];
        foreach ($locales as $locale) {
            $short = substr($locale, 0, 2);
            $availableLocales[$short] = [
                'locale' => $short,
            ];
        }

        /*if ($useFiles && $this->useTranslationFiles) {
            foreach ($categories as $category) {
                foreach ($locales as $locale) {
                    // Mapping happens by broad locale
                    $generalizedLocale = substr($locale, 0, 2);
                    $messages = $this->loadMessages($category, $locale, false);

                    // Let's map it to the right format.
                    foreach ($messages as $message => $translation) {

                        // Hey! Something is already there!
                        if (isset($translations[$message])) {
                            $translations[$message]['languages'][$generalizedLocale]['locale'] = $generalizedLocale;
                            $translations[$message]['languages'][$generalizedLocale]['file'] = $translation;

                            continue; // Our work for this cycle is done, move ahead!
                        }

                        // Setup basic structure
                        $translations[$message] = [
                            'message' => $message,
                            'category' => $category,
                            'languages' => $availableLocales
                        ];

                        $translations[$message]['languages'][$generalizedLocale]['locale'] = $generalizedLocale;
                        $translations[$message]['languages'][$generalizedLocale]['file'] = $translation;
                    }
                }
            }
        }*/

        // -------------------- DB -------------------------
        if($this->enableCaching) {
            $dbTranslations = $this->cache->get(self::TRANSLATIONSUITE_MISSING_TRANSLATIONS_CACHE_TAG);
        }

        if(!$dbTranslations) {
            $dbTranslations =  $this->loadMissingMessages();

            foreach ($dbTranslations as $missingTranslation) {
                $message = $missingTranslation['message'];
                $category = $missingTranslation['category'];
                $locale = $missingTranslation['language'];
                $translation = $missingTranslation['translation'];

                if (isset($translations[$message])) {
                    $translations[$message]['languages'][$locale]['locale'] = $locale;
                    $translations[$message]['languages'][$locale]['db'] = $translation;

                    continue;
                }

                // Setup basic structure
                $translations[$message] = [
                    'message' => $message,
                    'category' => $category,
                    'languages' => $availableLocales
                ];

                $translations[$message]['languages'][$locale]['locale'] = $locale;
                $translations[$message]['languages'][$locale]['db'] = $translation;
            }

            if ($this->enableCaching) {
                $this->cache->set(
                    self::TRANSLATIONSUITE_MISSING_TRANSLATIONS_CACHE_TAG,
                    $translations,
                    $this->cacheDuration,
                    new TagDependency(['tags' => self::TRANSLATIONSUITE_MISSING_TRANSLATIONS_CACHE_TAG]));
            }
        }

        // Clear out the translations that have a file translation or a db translation for both languages.
        /*if ($prune) {
            foreach ($translations as $key => $translation) {
                $languages = $translation['languages'];
                $hasEmpty = false;
                foreach ($languages as $language) {
                    if (empty($language['file']) && empty($language['db'])) {
                        $hasEmpty = true;
                        break;
                    }

                }

                if (!$hasEmpty) {
                    unset($translations[$key]);
                }
            }
        }*/

        return $translations;
    }

    public function getEnabledCategories(): array
    {
        $categories = Translationsuite::$settings->translationCategories;
        $categories = array_filter($categories, function($enabled) {
            return $enabled;
        });
        return array_keys($categories);
    }

    public function invalidateTranslationsCaches()
    {
        if (!$this->cache instanceof CacheInterface) {
            $this->cache = Craft::$app->getCache();
        }

        TagDependency::invalidate($this->cache, [
            self::TRANSLATIONSUITE_CATEGORY_LANGUAGE_CACHE_TAG,
            self::TRANSLATIONSUITE_MISSING_TRANSLATIONS_CACHE_TAG
        ]);

        Craft::info(
            'Cleared translation caches',
            __METHOD__
        );

        $event = new InvalidateCachesEvent([
            'component' => self::class
        ]);

        if (!Craft::$app instanceof Application) {
            $this->trigger(self::EVENT_INVALIDATE_TRANSLATION_CACHE, $event);
        }
    }
}