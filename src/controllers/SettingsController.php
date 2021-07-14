<?php

namespace moshimoshi\translationsuite\controllers;

use Box\Spout\Common\Entity\Style\Border;
use Box\Spout\Common\Entity\Style\Color;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\CSV\SheetIterator;
use Box\Spout\Writer\Common\Creator\Style\BorderBuilder;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Entity\Sheet;
use Craft;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\UploadedFile;
use moshimoshi\translationsuite\helpers\CpHelper;
use moshimoshi\translationsuite\records\MessageRecord;
use moshimoshi\translationsuite\records\SourceMessageRecord;
use moshimoshi\translationsuite\Translationsuite;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * SettingsController
 *
 * @author    Moshi Moshi
 * @package   Translationsuite
 * @since     1.0.0
 */
class SettingsController extends Controller
{
    // Constants
    // ================================================================================================================

    const DOCUMENTATION_URL = 'https://github.com/moshimoshi-be/craft-translationsuite';

    // Protected Properties
    // =================================================================================================================
    protected $allowAnonymous = [];

    public function actionDashboard(bool $showWelcome = false): Response
    {
        $this->requirePermission('translationsuite:dashboard');
        $segments = $this->request->segments;
        $variables = CpHelper::setCommonVariables($segments);
        //$variables = $this->setCommonVariables();
        $variables['showWelcome'] = $showWelcome;


        if ($showWelcome) {
            $variables['title'] = Craft::t('translationsuite', "Welcome!");
            unset($variables['selectedSubnavItem']);
        }

        return $this->renderTemplate('translationsuite/dashboard/index', $variables);
    }

    public function actionExport(): Response
    {
        $this->requirePermission('translationsuite:export');
        $segments = $this->request->segments;
        $variables = CpHelper::setCommonVariables($segments);
        $variables['exportOptions'] = [
            'all' => "All Translations",
            'db'  => "Database Translations",
        ];

        $categories = array_merge([
            'all'
        ], Translationsuite::$plugin->translations->getEnabledCategories());
        $categories = array_flip($categories);
        foreach ($categories as $key => $category) {
            $categories[$key] = ucfirst($key);
        }
        $variables['categories'] = $categories;

        $variables['exportableFiletypes'] = [
            'csv' => "CSV",
            'xlsx' => "Excel",
            'php' => "PHP",
        ];

        return $this->renderTemplate('translationsuite/export/index', $variables);
    }

    public function actionExportToFile() {
        $filetype = $this->request->getRequiredQueryParam('filetype');
        $category = $this->request->getRequiredQueryParam('category');

        if ($category == 'all') {
            $translations = Translationsuite::$plugin->translations->getAllTranslations(true);
            $translations = array_values($translations);
            $translations = array_merge(...$translations);
        } else {
            $translations = Translationsuite::$plugin->translations->getTranslations($category);
        }

        $today = new \DateTime();
        $tmpPath = Craft::$app->getPath()->getTempPath();


        switch ($filetype) {
            case 'php':
                $directory = 'translationsuite-export-' . $category . "-" . $today->format('YmdHis');
                $path = $tmpPath . "/" . $directory;
                $filepath = Translationsuite::$plugin->export->toPhp($translations, $path);
                break;
            case 'xlsx':
                $filename = 'translationsuite-export-' . $category . "-" . $today->format('YmdHis') . $filetype;
                $filepath = $tmpPath . "/" . $filename;
                $filepath = Translationsuite::$plugin->export->toExcel($translations, $filepath);
                break;
            case 'csv':
                $filename = 'translationsuite-export-' . $category . "-" . $today->format('YmdHis') . $filetype;
                $filepath = $tmpPath . "/" . $filename;
                $filepath = Translationsuite::$plugin->export->toCsv($translations, $filepath);
                break;
        }

        return $this->response->sendFile($filepath);
    }


    public function actionImport() {
        $this->requirePermission('translationsuite:import');
        $segments = $this->request->segments;
        $variables = CpHelper::setCommonVariables($segments);

        return $this->renderTemplate('translationsuite/import/index', $variables);
    }

    public function actionImportFromFile() {
        $columns = $this->request->getRequiredBodyParam('columns');
        $headers = $this->request->getBodyParam('headers');
        $columns = Json::decode($columns);

        // Validation of some required columns
        $types = array_column($columns, 'type');
        if (!in_array('language', $types, true)) {
            Craft::$app->getSession()->setError("Your selection of columns should contain at least 1 language");
            return $this->redirectToPostedUrl();
        }
        if (!in_array('message', $types, true)) {
            Craft::$app->getSession()->setError("Your selection of columns should contain the message column");
            return $this->redirectToPostedUrl();
        }
        if (!in_array('category', $types, true)) {
            Craft::$app->getSession()->setError("Your selection of columns should contain the category column");
            return $this->redirectToPostedUrl();
        }

        $columns = array_column($columns, 'name');
        $columns = array_map('strtolower', $columns);
        $translationsFile = UploadedFile::getInstanceByName('translations');

        if ($translationsFile) {
            $today = new \DateTime();
            $tempPath = Craft::$app->getPath()->getTempPath();
            $extension = $translationsFile->extension;
            $name = "translationsuite-import-" . $today->format('YmdHis') . "." . $extension;
            $filepath = $tempPath . "/" . $name;
            $translationsFile->saveAs($filepath);

            $reader = ReaderEntityFactory::createReaderFromFile($filepath);
            $reader->open($filepath);

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $key => $row) {
                    if ($headers && $key === 1) {
                        continue;
                    }
                    $items = $row->toArray();
                    $itemsAmount = count($items);
                    $columnsAmount = count($columns);

                    // Make sure our arrays have the same length to combine.
                    if ($itemsAmount > $columnsAmount) {
                        $items = array_slice($items, 0, $columnsAmount);
                    }
                    if ($columnsAmount > $itemsAmount) {
                        $items = array_pad($items, $columnsAmount, null);
                    }
                    $combined = array_combine($columns, $items);

                    $messageRecords = MessageRecord::find()
                        ->from(['m' => MessageRecord::tableName()])
                        ->leftJoin(['t' => SourceMessageRecord::tableName()], 't.id = m.id')
                        ->where([
                            't.category' => $combined['category'],
                            't.message' => $combined['message'],
                        ])
                        ->all();

                    if (!$messageRecords) {
                        $sourceRecord = new SourceMessageRecord([
                            'category' => $combined['category'],
                            'message' => $combined['message'],
                        ]);
                        $sourceRecord->save();
                        $messageRecords = MessageRecord::findAll(['id' => $sourceRecord->id]);
                    }

                    // Remove category and message so we only keep the translations
                    unset($combined['category'], $combined['message']);

                    // Loop over translations, set translations and save.
                    foreach ($combined as $language => $translation) {
                        $messageRecord = array_filter($messageRecords, function ($record) use ($language) {
                            return $record->language == $language;
                        });

                        // Apparently a language that wasn't added before.
                        // Let's add it now.
                        if (!$messageRecord) {
                            $messageRecord =  new MessageRecord([
                                'id' => reset($messageRecords)->id,
                                'language' => $language,
                                'translation' => $translation,
                            ]);
                            $messageRecord->save();
                        } else {
                            $messageRecord = reset($messageRecord);
                            $messageRecord->translation = $translation;
                            $messageRecord->save();
                        }
                    }
                }
            }
        }

        Craft::$app->session->setNotice(Craft::t('translationsuite', 'The translations were imported!'));
        return $this->redirectToPostedUrl();
    }

    public function actionSettings(): Response
    {
        $this->requirePermission('translationsuite:settings');
        $segments = $this->request->segments;
        $variables = CpHelper::setCommonVariables($segments);
        $variables['fullPageForm'] = true;

        // Update the possible categories
        Translationsuite::$plugin->categories->setTranslationsCategoriesSettings();
        $variables['settings'] = Translationsuite::$settings;

        return $this->renderTemplate('translationsuite/settings/index', $variables);
    }

    public function actionSaveSettings()
    {
        $this->requirePermission('translationsuite:settings');
        $this->requirePostRequest();

        // Save the settings
        $pluginHandle = Craft::$app->getRequest()->getRequiredBodyParam('pluginHandle');
        $settings = Craft::$app->getRequest()->getBodyParam('settings', []);
        $plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);

        if (!$plugin) {
            throw new NotFoundHttpException('Plugin not found');
        }

        if (!Craft::$app->getPlugins()->savePluginSettings($plugin, $settings)) {
            Craft::$app->getSession()->setError(Craft::t('app', "Couldn't save plugin settings."));

            // Send the plugin back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'plugin' => $plugin,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Plugin settings saved.'));

        return $this->redirectToPostedUrl();
    }


}