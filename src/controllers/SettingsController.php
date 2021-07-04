<?php

namespace moshimoshi\translationsuite\controllers;

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Craft;
use craft\web\Controller;
use moshimoshi\translationsuite\helpers\CpHelper;
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

    const DOCUMENTATION_URL = 'https://github.com/moshimoshi/craft-translationsuite';

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
            'xlsx' => "Excel"
        ];

        return $this->renderTemplate('translationsuite/export/index', $variables);
    }

    public function actionExportExcel() {
        $exportMode = $this->request->getRequiredQueryParam('export-mode');
        $filetype = $this->request->getRequiredQueryParam('filetype');
        $category = $this->request->getRequiredQueryParam('category');

        switch ($filetype) {
            case 'xlsx':
                $writer = WriterEntityFactory::createXLSXWriter();
                break;
            case 'csv':
            default:
                $writer = WriterEntityFactory::createCSVWriter();
                $filetype = 'csv';
                break;
        }

        // Category export?

        switch ($exportMode) {
            case 'db':
                $translations = Translationsuite::$plugin->translations->getAllTranslations(false);
                break;
            case 'all':
            default:
                $translations = Translationsuite::$plugin->translations->getAllTranslations(true);
                break;
        }

        dd($translations);

        $writer->close();
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