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
			StartDateTime DATETIME NOT NULL,
			EndDateTime DATETIME,
			tftype tinyint(1) unsigned NOT NULL,
			PRIMARY KEY (timeframe, location, item, tftype)
		) $charsetCollate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

	public static function insertTimeframe ( \CommonsBooking\Model\Timeframe $timeframe ) {
		global $wpdb;

        $endTimestamp = $timeframe->getEndDate();

        if ($endTimestamp == 0 || empty($endTimestamp)) {
            //$EndDateTime = date('Y-m-d H:i:s', strtotime("+90 days"));
	        $EndDateTime = 'NULL';
        } else {
            $EndDateTime = date('Y-m-d H:i:s', $endTimestamp);
        }


		if (self::hasTimeframe($timeframe)) {
			return self::updateTimeframe($timeframe);
		}
		$tableName = $wpdb->prefix . self::$tableName;
		$locationIDs = $timeframe->getLocationIDs();
		$itemIDs = $timeframe->getItemIDs();
		$StartDateTime = date('Y-m-d H:i:s', $timeframe->getStartDate());
		$type = $timeframe->getType();

		foreach ($locationIDs as $locationID) {
			foreach ($itemIDs as $itemID) {
				$sql = $wpdb->prepare("INSERT INTO $tableName (timeframe, location, item, StartDateTime, EndDateTime, tftype) VALUES (%d, %d, %d, %s, %s, %d)", $timeframe->ID, $locationID, $itemID, $StartDateTime, $EndDateTime, $type);
				$wpdb->query($sql);
				//TODO: Mega dirty aber geht
				$sql = "UPDATE $tableName SET EndDateTime = NULL WHERE EndDateTime = '0000-00-00 00:00:00'";
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
	public static function getRelevantPosts( $items, $locations, $startDate, $endDate, array $types ): array {
		global $wpdb;

        if ($endDate == null) {
            $endDate = $startDate;
        }

        // define types statically
        $types = array(\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID);


		$startDateTime = date( 'Y-m-d H:i:s', $startDate );
        $endDateTime = date( 'Y-m-d H:i:s', $endDate );

		$tableName   = $wpdb->prefix . self::$tableName;
		$querystring = '';
		if ( ! empty ( $locations ) ) {
			$locationString = implode( ',', $locations );
			if ( strpos( $locationString, '39082') ) {
			}
			$querystring .= "location IN ($locationString)";
		}
		if ( ! empty ( $items ) ) {
			$itemString = implode( ',', $items );
			if ( ! empty ( $querystring ) ) {
				$querystring .= ' AND ';
			}
			//$querystring .= "item IN ($itemString)";
		}
		if ( ! empty ( $types ) ) {
			$typeString = implode( ',', $types );
			if ( ! empty ( $querystring ) ) {
				$querystring .= ' AND ';
			}
			$querystring .= "tftype IN ($typeString)";
		}
		if ( $startDate ) {
			if ( ! empty ( $querystring ) ) {
				$querystring .= ' AND ';
			}

			$querystring .= "StartDateTime <= '" . $startDateTime . "' AND (EndDateTime >= '" . $endDateTime . "' OR EndDateTime IS NULL)";
		}
		$sql = "SELECT * FROM $tableName WHERE $querystring";


		//$sql = $wpdb->prepare( $sql );
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