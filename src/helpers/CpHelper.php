<?php

namespace moshimoshi\translationsuite\helpers;

use Craft;
use craft\helpers\UrlHelper;
use moshimoshi\translationsuite\Translationsuite;

/**
 * CpHelper
 *
 * @author    Moshi Moshi
 * @package   Translationsuite
 * @since     1.0.0
 */
class CpHelper
{
    const DOCUMENTATION_URL = 'https://github.com/moshimoshi-be/craft-translationsuite';

    public static function setCommonVariables(array $segments): array
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
        foreach ($segments as $k => $segment) {
            $label = Craft::t('translationsuite', ucfirst($segment));
            $url = '';
            for ($i = 0; $i <= $k; $i++) {
                $url .= $segments[$i];
                if ($i < (count($segments) -1)) {
                    $url .= "/";
                }
            }

            if ($k === 0) {
                $label = $pluginName;
            }
            $url = UrlHelper::cpUrl($url);

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