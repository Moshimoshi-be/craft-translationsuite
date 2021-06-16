<?php
/**
 * SettingsController.php
 */

namespace moshimoshi\translationsuite\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use moshimoshi\translationsuite\Translationsuite;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * SettingsController
 *
 * @copyright   2021 UniWeb bvba
 * @since       2021-06-15 16:50
 * @author      pieterjangeeroms
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
        $variables = $this->setCommonVariables();
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
        $variables = $this->setCommonVariables();

        return $this->renderTemplate('translationsuite/export/index', $variables);
    }

    public function actionSettings(): Response
    {
        $this->requirePermission('translationsuite:settings');
        $variables = $this->setCommonVariables();
        $variables['fullPageForm'] = true;
        $variables['settings'] = Translationsuite::$settings;

        return $this->renderTemplate('translationsuite/settings/index', $variables);
    }

    public function actionSaveSettings()
    {
        $this->requirePermission('translationsuite:settings');
        $this->requirePostRequest();

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

    // Protected Methods
    // =================================================================================================================

    protected function setCommonVariables(): array
    {
        // Find out the method that's calling the function
        $caller = debug_backtrace()[1]['function'];
        $caller = explode('action', $caller);
        $caller = end($caller);

        $pluginName = Translationsuite::$plugin->name;
        $title = Craft::t('translationsuite', $caller);

        $variables = [];
        $variables['pluginName'] = $pluginName;
        $variables['docTitle'] = "{$pluginName} - {$title}";
        $variables['fullPageForm'] = false;
        $variables['docsUrl'] = self::DOCUMENTATION_URL;
        $variables['title'] = $title;
        $variables['selectedSubnavItem'] = strtolower($caller);
        $variables['crumbs'] = [];
        $variables['pluginUrl'] = UrlHelper::cpUrl('translationsuite');

        // Generate the crumbs
        $segments = $this->request->segments;
        foreach ($segments as $k => $segment) {
            $label = Craft::t('translationsuite', ucfirst($segment));
            $url = '';
            for ($i = 1; $i <= $k; $i++) {
                $url .= $segments[$i] . "/";
            }

            if ($k == 0) {
                $label = $pluginName;
                $url = $segment;
            }

            $variables['crumbs'][] = [
                'label' => $label,
                'url'   => $url,
            ];
        }

        $variables['baseAssetsUrl'] = Craft::$app->assetManager->getPublishedUrl(
            '@moshimoshi/translationsuite/web/assets/dist',
            true
        );

        return $variables;
    }

}