<?php
/**
 * Translationsuite plugin for Craft CMS 3.x
 *
 * The one and only translation plugin you'll ever need.
 *
 * @link      moshimoshi.be
 * @copyright Copyright (c) 2021 Moshi Moshi
 */

namespace moshimoshi\translationsuite\migrations;

use moshimoshi\translationsuite\services\TranslationsService;

use Craft;
use craft\db\Migration;

/**
 * Translationsuite Install Migration
 *
 * If your plugin needs to create any custom database tables when it gets installed,
 * create a migrations/ folder within your plugin folder, and save an Install.php file
 * within it using the following template:
 *
 * If you need to perform any additional actions on install/uninstall, override the
 * safeUp() and safeDown() methods.
 *
 * @author    Moshi Moshi
 * @package   Translationsuite
 * @since     1.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * This method contains the logic to be executed when applying this migration.
     * This method differs from [[up()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[up()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();

            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
        }

        return true;
    }

    /**
     * This method contains the logic to be executed when removing this migration.
     * This method differs from [[down()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[down()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables needed for the Records used by the plugin
     *
     * @return bool
     */
    protected function createTables(): bool
    {
        $tablesCreated = false;

        // Source table
        $tableSchema = Craft::$app->db->schema->getTableSchema(TranslationsService::$sourceMessageTable);
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                TranslationsService::$sourceMessageTable,
                [
                    // Craft required columns
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),

                    // Grabbed this from the Yii Migrations (@yii/i18n/migrations/)
                    'category' => $this->string(),
                    'message' => $this->text(),
                ]
            );
        }

        $tableSchema = Craft::$app->db->schema->getTableSchema(TranslationsService::$messageTable);
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                TranslationsService::$messageTable,
                [
                    // Craft required columns
                    'id' => $this->integer()->notNull(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),

                    // Grabbed this from the Yii Migrations (@yii/i18n/migrations/)
                    'language' => $this->string(16)->notNull(),
                    'translation' => $this->text()
                ]
            );

            // Grabbed this from the Yii Migrations (@yii/i18n/migrations/)
            $this->addPrimaryKey('pk_message_id_language', TranslationsService::$messageTable, ['id', 'language']);
        }

        return $tablesCreated;
    }

    /**
     * Creates the indexes needed for the Records used by the plugin
     *
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex('idx_message_language', TranslationsService::$messageTable, 'language');
        $this->createIndex('idx_source_message_category', TranslationsService::$sourceMessageTable, 'category');
    }

    /**
     * Creates the foreign keys needed for the Records used by the plugin
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(
            'fk_message_source_message',
            TranslationsService::$messageTable,
            'id',
            TranslationsService::$sourceMessageTable,
            'id',
            'CASCADE',
            'RESTRICT'
        );
    }

    /**
     * Removes the tables needed for the Records used by the plugin
     *
     * @return void
     */
    protected function removeTables()
    {
        // Let's not delete the tables on an uninstall, for safety purposes.
        // Wouldn't want to lose our translations on an accidental uninstall.
    }
}
