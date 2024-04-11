<?php

namespace CommonsBooking\Repository;

class TimeframeRelations
{

	public static string $tableName = 'cb_tfrelations';

	public static function initTable(): void {
		global $wpdb;
		//TODO: Add DB versioning

		$tableName = $wpdb->prefix . self::$tableName;
		$charsetCollate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $tableName (
			timeframe bigint(20) unsigned NOT NULL,
			location bigint(20) unsigned NOT NULL,
			item bigint(20) unsigned NOT NULL,
			startTS bigint(20) unsigned NOT NULL,
			endTS bigint(20) unsigned NOT NULL,
			type tinyint(1) unsigned NOT NULL,
			PRIMARY KEY (timeframe, location, item, type)
		) $charsetCollate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

	public static function insertTimeframe ( \CommonsBooking\Model\Timeframe $timeframe ) {
		global $wpdb;
		if (self::hasTimeframe($timeframe)) {
			return self::updateTimeframe($timeframe);
		}
		$tableName = $wpdb->prefix . self::$tableName;
		$locationIDs = $timeframe->getLocationIDs();
		$itemIDs = $timeframe->getItemIDs();
		$startTS = $timeframe->getStartDate();
		$endTS = $timeframe->getEndDate();
		$type = $timeframe->getType();

		foreach ($locationIDs as $locationID) {
			foreach ($itemIDs as $itemID) {
				$sql = $wpdb->prepare("INSERT INTO $tableName (timeframe, location, item, startTS, endTS, type) VALUES (%d, %d, %d, %d, %d, %d)", $timeframe->ID, $locationID, $itemID, $startTS, $endTS, $type);
				$wpdb->query($sql);
			}
		}
	}

	public static function updateTimeframe( \CommonsBooking\Model\Timeframe $timeframe ) {
		global $wpdb;
		$tableName = $wpdb->prefix . self::$tableName;
		//This is incredibly dirty and fast, TODO FIX!
		$sql = $wpdb->prepare("DELETE FROM $tableName WHERE timeframe = %d", $timeframe->ID);
		$wpdb->query($sql);
		self::insertTimeframe($timeframe);
	}

	/**
	 * To replace the
	 *
	 * @see Timeframe::getPostIdsByType()
	 * @param int[] $locations
	 * @param int[] $items
	 * @param int $dateTS
	 * @param int[] $types
	 *
	 * @return int[] Timeframe IDS
	 */
	public static function getRelevantPosts( array $locations, array $items, int $dateTS, array $types ): array {
		global $wpdb;
		$tableName = $wpdb->prefix . self::$tableName;
		$locationString = implode(',', $locations);
		$itemString = implode(',', $items);
		$typeString = implode(',', $types);
		$sql = $wpdb->prepare("SELECT DISTINCT timeframe FROM $tableName WHERE location IN (%s) AND item IN (%s) AND startTS <= %d AND endTS >= %d AND type IN (%s)", $locationString, $itemString, $dateTS, $dateTS, $typeString);
		$result = $wpdb->get_results($sql);
		$ids = [];
		foreach ($result as $row) {
			$ids[] = $row->timeframe;
		}
		return $ids;
	}

	public static function hasTimeframe ( \CommonsBooking\Model\Timeframe $timeframe ): bool {
		global $wpdb;
		$tableName = $wpdb->prefix . self::$tableName;
		$sql = $wpdb->prepare("SELECT * FROM $tableName WHERE timeframe = %d", $timeframe->ID);
		$result = $wpdb->get_results($sql);
		return count($result) > 0;
	}
	
}