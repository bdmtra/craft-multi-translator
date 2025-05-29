<?php

namespace digitalpulsebe\craftmultitranslator\helpers;

use craft\base\Element;
use craft\commerce\elements\Product;
use craft\elements\Asset;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use yii\db\Expression;

class ElementHelper
{

    /**
     * @param string $elementType
     * @param int|array $elementIds
     * @param int $siteId
     * @return ElementQuery
     */
    public static function query(string $elementType, int|array $elementIds, int $siteId): ElementQuery
    {
        $query = null;
        
        if ($elementType == 'craft\commerce\elements\Product') {
            $query = Product::find()->status(null)->id($elementIds)->siteId($siteId);
        } elseif ($elementType == Asset::class) {
            $query = Asset::find()->status(null)->id($elementIds)->siteId($siteId);
        } else {
            $query = Entry::find()->drafts(null)->status(null)->id($elementIds)->siteId($siteId);
        }
        
        // Manually set the order if we have an array of IDs
        if (is_array($elementIds) && count($elementIds) > 1) {
            // Use orderBy with FIELD function instead of fixedOrder
            $ids = implode(',', array_map('intval', $elementIds));
            if (!empty($ids)) {
                $query->orderBy(new \yii\db\Expression("FIELD([[elements.id]], {$ids})"));
            }
        }
        
        return $query;
    }

    /**
     * @param string $elementType
     * @param int $elementId
     * @param int $siteId
     * @return ?Element
     */
    public static function one(string $elementType, int $elementId, int $siteId): ?Element
    {
        return self::query($elementType, $elementId, $siteId)->one();
    }

    /**
     * @param string $elementType
     * @param array $elementIds the element ids to select
     * @param int $siteId the siteId
     * @return Element[]
     */
    public static function all(string $elementType, array $elementIds, int $siteId): array
    {
        return self::query($elementType, $elementIds, $siteId)->all();
    }
}
