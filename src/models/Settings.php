<?php
/**
 * Translationsuite plugin for Craft CMS 3.x
 *
 * The one and only translation plugin you'll ever need.
 *
 * @link      moshimoshi.be
 * @copyright Copyright (c) 2021 Moshi Moshi
 */

namespace moshimoshi\translationsuite\models;

use moshimoshi\translationsuite\Translationsuite;

use Craft;
use craft\base\Model;

/**
 * Translationsuite Settings Model
 *
 * @author    Moshi Moshi
 * @package   Translationsuite
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $pluginName = 'Translation Suite';

    /**
     * @var bool
     */
    public $useTranslationFiles = true;

    /**
     * @var array
     */
    public $translationCategories = [];

    /**
     * @var bool
     */
    public $forceTranslations = true;

    /**
     * @var bool
     */
    public $enableCaching = true;

    /**
     * @var bool
     */
    public $saveMissingTranslations = true;

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * @return array
     */
    public function rules()
    {
        return [
            ['pluginName', 'string'],
            ['pluginName', 'required'],
            ['useTranslationFiles', 'boolean'],
            ['forceTranslations', 'boolean'],
            ['enableCaching', 'boolean'],
            ['saveMissingTranslations', 'boolean'],
        ];
    }
}
