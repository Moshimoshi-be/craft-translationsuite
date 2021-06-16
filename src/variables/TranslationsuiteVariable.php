<?php
/**
 * Translationsuite plugin for Craft CMS 3.x
 *
 * The one and only translation plugin you'll ever need.
 *
 * @link      moshimoshi.be
 * @copyright Copyright (c) 2021 Moshi Moshi
 */

namespace moshimoshi\translationsuite\variables;

use nystudio107\pluginvite\variables\ViteVariableInterface;
use nystudio107\pluginvite\variables\ViteVariableTrait;

/**
 * Translationsuite Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.translationsuite }}).
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
}
