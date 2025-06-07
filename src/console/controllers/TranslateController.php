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
     * Translates an entry from one site to another.
     *
     * Usage:
     *   php craft multi-translator/translate/entry <entryId> <sourceSiteHandle> <targetSiteHandle>
     *
     * @param int $entryId
     * @param string $sourceSiteHandle
     * @param string $targetSiteHandle
     * @return int
     */
    public function actionEntry($entryId, $sourceSiteHandle, $targetSiteHandle): int
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

        $entry = Entry::find()->id($entryId)->siteId($sourceSite->id)->one();
        if (!$entry) {
            $this->stderr("Entry not found for ID $entryId on site $sourceSiteHandle\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        try {
            $translated = MultiTranslator::getInstance()->translateService->translateElement($entry, $sourceSite, $targetSite);
            $this->stdout("Successfully translated entry ID $entryId from $sourceSiteHandle to $targetSiteHandle.\n");
            return ExitCode::OK;
        } catch (\Throwable $e) {
            $this->stderr("Error during translation: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
