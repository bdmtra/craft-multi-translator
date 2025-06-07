<?php

namespace digitalpulsebe\craftmultitranslator\console\controllers;

use Craft;
use craft\console\Controller;
use yii\console\ExitCode;
use craft\elements\Entry;
use craft\models\Site;
use digitalpulsebe\craftmultitranslator\MultiTranslator;

class TranslateController extends Controller
{
    /**
     * Translates one or more entries from one site to another.
     *
     * Usage:
     *   php craft multi-translator/translate/entry <entryIds> <sourceSiteHandle> <targetSiteHandle>
     * 
     * Example:
     *   php craft multi-translator/translate/entry 44,45,46 de en
     *
     * @param string $entryIds Comma-separated list of entry IDs
     * @param string $sourceSiteHandle
     * @param string $targetSiteHandle
     * @return int
     */
    public function actionEntry($entryIds, $sourceSiteHandle, $targetSiteHandle): int
    {
        $sourceSite = Craft::$app->sites->getSiteByHandle($sourceSiteHandle);
        $targetSite = Craft::$app->sites->getSiteByHandle($targetSiteHandle);

        if (!$sourceSite) {
            $this->stderr("Source site handle not found: $sourceSiteHandle\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
        if (!$targetSite) {
            $this->stderr("Target site handle not found: $targetSiteHandle\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
        
        // Parse comma-separated entry IDs
        $entryIdList = array_map('trim', explode(',', $entryIds));
        $successCount = 0;
        $failCount = 0;
        
        foreach ($entryIdList as $entryId) {
            $this->stdout("Processing entry ID: $entryId\n");
            
            $entry = Entry::find()->id($entryId)->siteId($sourceSite->id)->one();
            if (!$entry) {
                $this->stderr("  - Entry not found for ID $entryId on site $sourceSiteHandle\n");
                $failCount++;
                continue;
            }
            
            try {
                $translated = MultiTranslator::getInstance()->translate->translateElement($entry, $sourceSite, $targetSite);
                $this->stdout("  - Successfully translated entry ID $entryId from $sourceSiteHandle to $targetSiteHandle\n");
                $successCount++;
            } catch (\Throwable $e) {
                $this->stderr("  - Error translating entry ID $entryId: " . $e->getMessage() . "\n");
                $failCount++;
            }
        }
        
        // Final summary
        $total = count($entryIdList);
        $this->stdout("\nTranslation complete: $successCount successful, $failCount failed, $total total\n");
        
        return ($failCount === 0) ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
    }
}
