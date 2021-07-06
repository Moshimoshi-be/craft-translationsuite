<?php

namespace moshimoshi\translationsuite\controllers;

use Craft;
use craft\helpers\Json;
use craft\models\Site;
use craft\web\Controller;
use moshimoshi\translationsuite\helpers\CpHelper;
use moshimoshi\translationsuite\records\MessageRecord;
use moshimoshi\translationsuite\records\SourceMessageRecord;
use moshimoshi\translationsuite\services\TranslationsService;
use moshimoshi\translationsuite\Translationsuite;
use yii\web\BadRequestHttpException;

/**
 * TranslationsController
 *
 * @author    Moshi Moshi
 * @package   Translationsuite
 * @since     1.0.0
 */
class TranslationsController extends Controller
{

    public $enableCsrfValidation = false;

    public function actionIndex(bool $showWelcome = false) {
        $segments = $this->request->segments;
        $variables = CpHelper::setCommonVariables($segments);
        $variables['title'] = Craft::t('translationsuite', "Translations");
        $variables['selectedSubnavItem'] = 'translations';
        $variables['showWelcome'] = $showWelcome;


        if ($showWelcome) {
            $variables['title'] = Craft::t('translationsuite', "Welcome!");
            unset($variables['selectedSubnavItem']);
        }

        return $this->renderTemplate('translationsuite/translations/index', $variables);
    }

    public function actionGetLanguages() {
        $sites = Craft::$app->sites->getAllSites();
        $languages = [];
        foreach ($sites as $site) {
            $locale = substr($site->language, 0, 2);
            $languages[] = [
                'name' => $site->getName(),
                'locale' => $locale,
            ];
        }

        return $this->asJson($languages);
    }

    public function actionGetCategories() {
        $translationService = Translationsuite::$plugin->translations;
        $categories = $translationService->getEnabledCategories();
        sort($categories);

        return $this->asJson($categories);
    }

    public function actionGetTranslations(string $category) {
        $translationService = Translationsuite::$plugin->translations;

        if ($category == 'missing') {
            $translations = $translationService->getMissingTranslations();
        } else {
            $translations = $translationService->getTranslations($category);
        }

        $translations = array_values($translations);

        return $this->asJson($translations);
    }

    /**
     * Add a source message to the database.
     * @return \yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionAddSource() {
        $this->requirePostRequest();
        Craft::$app->user->can('translationsuite:translations');
        $enabledCategories = Translationsuite::$plugin->translations->getEnabledCategories();

        $category = $this->request->getRequiredBodyParam('category');
        $message = $this->request->getRequiredBodyParam('message');

        if (!in_array($category, $enabledCategories) || empty($message)) {
            throw new BadRequestHttpException("Either the category was not correct or your message was empty");
        }

        $source = new SourceMessageRecord();
        $source->category = $category;
        $source->message = $message;
        $source->save();

        // We should clear the cache as well now.
        // But perhaps not the complete cache because we've only added empty stuff.
        // @todo: Optimizeable
        Translationsuite::$plugin->translations->invalidateTranslationsCaches();

        return $this->asJson([
            'message' => 'success'
        ]);
    }

    public function actionUpdateTranslations() {
        $this->requirePostRequest();
        $translations = $this->request->getRequiredBodyParam('translations');

        foreach ($translations as $translation) {
            foreach ($translation['languages'] as $locale => $translated) {
                //$translated['db']
                $dbTranslation = MessageRecord::find()
                    ->from(['m' => MessageRecord::tableName()])
                    ->leftJoin(['t' => SourceMessageRecord::tableName()], 't.id = m.id')
                    ->where([
                        't.category' => $translation['category'],
                        't.message' => $translation['message'],
                        'm.language' => $locale
                    ])
                    ->one();

                if (!$dbTranslation) {
                    $source = new SourceMessageRecord([
                        'category' => $translation['category'],
                        'message' => $translation['message'],
                    ]);
                    $source->save();

                    $dbTranslation = MessageRecord::findOne(['id' => $source->id]);
                }
                $dbTranslation->translation = $translated['db'] ?? '';
                $dbTranslation->save();
            }
        }

        // After adding/updating translations, wipe the cache
        Translationsuite::$plugin->translations->invalidateTranslationsCaches();

        return $this->asJson([]);
    }

    public function actionDeleteTranslations() {
        $this->requirePostRequest();

        $translations = $this->request->getRequiredBodyParam('translations');

        foreach ($translations as $translation) {
            SourceMessageRecord::deleteAll([
                'category' => $translation['category'],
                'message' => $translation['message']
            ]);
        }

        // After deleting translations, wipe the cache
        Translationsuite::$plugin->translations->invalidateTranslationsCaches();

        return $this->asJson([]);
    }

}