<?php
/**
 * MessageRecord.php
 */

namespace moshimoshi\translationsuite\records;

use craft\db\ActiveRecord;
use moshimoshi\translationsuite\services\TranslationsService;

/**
 * MessageRecord
 *
 * @author    Moshi Moshi
 * @package   Translationsuite
 * @since     1.0.0
 */
class MessageRecord extends ActiveRecord
{

    /**
     * @return string the table name
     */
    public static function tableName(): string
    {
        return TranslationsService::$messageTable;
    }
    public function rules()
    {
        return [
            ['language', 'string'],
            ['translation', 'string'],
        ];
    }
}