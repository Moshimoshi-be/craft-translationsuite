<?php
/**
 * Translation Suite plugin for Craft CMS 3.x
 *
 * The one and only translation plugin you'll ever need.
 *
 * @link      moshimoshi.be
 * @copyright Copyright (c) 2021 Moshi Moshi
 */

namespace moshimoshi\translationsuite;

use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;
use craft\i18n\I18N;
use craft\services\UserPermissions;
use moshimoshi\translationsuite\services\MessageSource;
use moshimoshi\translationsuite\services\TranslationsuiteService;
use moshimoshi\translationsuite\variables\TranslationsuiteVariable;
use moshimoshi\translationsuite\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\console\Application as ConsoleApplication;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;

use nystudio107\pluginvite\services\VitePluginService;
use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
 * @author    Moshi Moshi
 * @package   Translationsuite
 * @since     1.0.0
 *
 * @property  TranslationsuiteService $translationsuiteService
 * @property  VitePluginService $vite
 * @method    Settings getSettings()
 */
class Translationsuite extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Translationsuite::$plugin
     *
     * @var Translationsuite
     */
    public static $plugin;

    /**
     * @var Settings
     */
    public static $settings;

    /**
     * @var bool
     */
    public static $devMode;


    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public $hasCpSettings = true;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public $hasCpSection = true;

    // Static Methods
    // =========================================================================

    public function __construct($id, $parent = null, array $config = [])
    {
        $config['components'] = [
            'translationsuite' => __CLASS__,
            'messageSource' => MessageSource::class,
            // Register the vite service
            'vite' => [
                'class' => VitePluginService::class,
                'assetClass' => TranscoderAsset::class,
                'useDevServer' => true,
                'devServerPublic' => 'http://localhost:3001',
                'serverPublic' => 'http://localhost:8000',
                'errorEntry' => '/src/js/app.ts',
                'devServerInternal' => 'http://craft-translationsuite-buildchain:3001',
                'checkDevServer' => true,
            ],
        ];

        parent::__construct($id, $parent, $config);
    }


    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Translationsuite::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Handle console commands
        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'moshimoshi\translationsuite\console\controllers';
        }

        // Initialize properties
        self::$settings = self::$plugin->getSettings();
        self::$devMode = Craft::$app->getConfig()->getGeneral()->devMode;

        $this->name = self::$settings->pluginName;
        $this->attachEventListeners();

        // Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('translationsuite', [
                    'class' => TranslationsuiteVariable::class,
                    'viteService' => $this->vite
                ]);
            }
        );

        // We're loaded
        Craft::info(
            Craft::t(
                'translationsuite',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    public function getCpNavItem()
    {
        $currentUser = Craft::$app->getUser()->getIdentity();
        $subNavs = [];
        $navItem = parent::getCpNavItem();

        if ($currentUser->can('translationsuite:dashboard')) {
            $subNavs['dashboard'] = [
                'label' => Craft::t('translationsuite', 'Dashboard'),
                'url' => 'translationsuite/dashboard'
            ];
        }

        if ($currentUser->can('translationsuite:export')) {
            $subNavs['export'] = [
                'label' => Craft::t('translationsuite', 'Export'),
                'url' => 'translationsuite/export'
            ];
        }

        if ($currentUser->can('translationsuite:settings')) {
            $subNavs['settings'] = [
                'label' => Craft::t('translationsuite', 'Settings'),
                'url' => 'translationsuite/settings'
            ];
        }

        if (empty($subNavs)) {
            $navItem = null;
            return $navItem;
        }

        $navItem = array_merge($navItem, [
            'subnav' => $subNavs
        ]);

        return $navItem;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    public function getSettingsResponse()
    {
        Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('translationsuite/settings'));
    }

    protected function attachEventListeners() {
        $request = Craft::$app->getRequest();

        if ($request->getIsCpRequest() && !$request->getIsConsoleRequest()) {
            $this->attachCpEventListeners();
        }

        // Do something after we're installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // We were just installed
                    $request = Craft::$app->getRequest();
                    if ($request->isCpRequest) {
                        Craft::$app->getResponse()->redirect(UrlHelper::cpUrl(
                            'translationsuite',
                            [
                                'showWelcome' => true,
                            ]
                        ))->send();
                    }
                }
            }
        );
    }

    protected function attachCpEventListeners() {
        // Register CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                Craft::debug('UrlManager::EVENT_REGISTER_CP_URL_RULES', __METHOD__);

                $event->rules = array_merge(
                    $event->rules,
                    $this->adminCPRoutes()
                );
            }
        );

        // Register user permissions
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                Craft::debug(
                    'UserPermissions::EVENT_REGISTER_PERMISSIONS',
                    __METHOD__
                );
                $event->permissions[Craft::t('translationsuite', 'Translation Suite')] = $this->registerCpPermissions();
            }
        );
    }

    protected function adminCPRoutes(): array
    {
        return [
            'translationsuite' => 'translationsuite/settings/dashboard',
            'translationsuite/dashboard' => 'translationsuite/settings/dashboard',
            'translationsuite/export' => 'translationsuite/settings/export',
            'translationsuite/settings' => 'translationsuite/settings/settings',
            'translationsuite/settings/save-settings' => 'translationsuite/settings/save-settings',
        ];
    }

    protected function registerCpPermissions(): array
    {
        return [
            'translationsuite:dashboard' => [
                'label' => Craft::t('translationsuite', 'Dashboard'),
            ],
            'translationsuite:export' => [
                'label' => Craft::t('translationsuite', 'Export translations'),
            ],
            'translationsuite:settings' => [
                'label' => Craft::t('translationsuite', 'Access settings'),
            ]
        ];
    }
}
