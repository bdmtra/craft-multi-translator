<?php

namespace digitalpulsebe\craftmultitranslator\migrations;

use craft\db\Migration;
use Craft;

/**
 * m250527_214400_add_prompt_field migration.
 */
class m250527_214400_add_prompt_field extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        echo "Adding 'addToPrompt' field to provider settings...\n";

        // This migration doesn't need to modify the database schema
        // It just adds a new field to the existing settings JSON
        
        // The field will be automatically available in the settings form
        // and will be saved when the settings are updated
        
        echo "Adding 'addToPrompt' field to multitranslator_provider_settings table...\n";
        
        // Add the addToPrompt column to the table
        $this->addColumn('{{%multitranslator_provider_settings}}', 'addToPrompt', $this->string()->after('settings'));
        
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "Removing 'addToPrompt' field from multitranslator_provider_settings table...\n";
        
        // Drop the addToPrompt column from the table
        $this->dropColumn('{{%multitranslator_provider_settings}}', 'addToPrompt');
        
        return true;
    }
}
