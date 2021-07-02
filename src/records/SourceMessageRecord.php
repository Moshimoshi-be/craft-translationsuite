<?php

namespace moshimoshi\translationsuite\records;

use Craft;
use craft\db\ActiveRecord;
use moshimoshi\translationsuite\services\TranslationsService;

/**
 * SourceMessageRecord
 *
 * @author    Moshi Moshi
 * @package   Translationsuite
 * @since     1.0.0
 */
class SourceMessageRecord extends ActiveRecord
{
    public $locales;

    /**
     * @return string the table name
     */
    public static function tableName(): string
    {
        return TranslationsService::$sourceMessageTable;
    }

    public function __construct($config = [])
    {
        $sites = Craft::$app->i18n->getSiteLocaleIds();
        foreach ($sites as $site) {
            $short = substr($site, 0, 2);
            $this->locales[] = $short;
        }
        parent::__construct($config);
    }

    // After adding a source message, also add the messages related to the source message for each locale.
    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            foreach ($this->locales as $locale) {
                $message = new MessageRecord();
                $message->id = $this->id;
                $message->language = $locale;
                $message->translation = '';
                $message->save();
            }
        }

        parent::afterSave($insert, $changedAttributes);
    }

    public function rules()
    {
        return [
            ['message', 'string'],
            ['category', 'string', 'max' => 255],
        ];
    }
}