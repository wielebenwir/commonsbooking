<?php

namespace CommonsBooking\Service;

use CommonsBooking\Exception\ExportException;
use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Repository\Timeframe;
use CommonsBooking\Settings\Settings;
use DateInterval;
use DatePeriod;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;

/**
 * The TimeframeExport class will export timeframes to a CSV file.
 * This can be done either from the backend settings or via a cron job.
 * The export can contain timeframes of a specific type, location, item and user.
 * The export can also include bookings.
 */
class TimeframeExport {

	/**
	 * The post type to export.
	 * This corresponds to the return of @see \CommonsBooking\Wordpress\CustomPostType\Timeframe::getTypes()
	 * The all option is corresponding to 0.
	 *
	 * @var int
	 */
	private int $exportType;
	/**
	 * The extra meta fields to export for locations.
	 *
	 * @var array|null
	 */
	private ?array $locationFields = null;
	/**
	 * The extra meta fields to export for items.
	 *
	 * @var array|null
	 */
	private ?array $itemFields = null;
	/**
	 * The extra meta fields to export for users.
	 *
	 * @var array|null
	 */
	private ?array $userFields = null;
	/**
	 * Export start date in whatever string format the WP field provides
	 *
	 * @var string
	 */
	private string $exportStartDate;
	/**
	 * Export end date in whatever string format the WP field provides
	 *
	 * @var string
	 */
	private string $exportEndDate;

	/**
	 * The name under which the file will be provided for download.
	 *
	 * @var string
	 */
	private string $exportFilename;

	/**
	 * The name of the transient where the relevantTimeframes are intermediately stored.
	 *
	 * @var string
	 */
	private string $transientName;

	/**
	 * Flag to indicate if the export data is complete.
	 *
	 * @var bool
	 */
	private bool $exportDataComplete = false;
	/**
	 * Flag set to true, when job is run from cron event
	 *
	 * @var bool
	 */
	private bool $isCron = false;

	/**
	 * Page that has been processed last.
	 *
	 * @var int|null
	 */
	private ?int $lastProcessedPage = null;
	/**
	 * Total amount of posts in export
	 *
	 * @var int|null
	 */
	private ?int $totalPosts;
	/**
	 * @var int[] Array of timeframe post IDs that are relevant for the export
	 */
	private array $relevantTimeframes = [];

	/**
	 * Defines how many pages will be processed in one iteration. Higher numbers increases the likelihood for a timeout.
	 */
	const ITERATION_COUNTS = 100;

	/**
	 * @param string      $exportType 0 for all, otherwise the post type ID as defined in @see \CommonsBooking\Wordpress\CustomPostType\Timeframe::getTypes()
	 * @param string      $exportStartDate Start date string of export
	 * @param string      $exportEndDate End date string of export
	 *
	 * @param array|null  $locationFields Metafields of location objects that should be included in the export
	 * @param array|null  $itemFields Metafields of item objects that should be included in the export
	 * @param array|null  $userFields Metafields of user objects that should be included in the export
	 * @param int|null    $lastProcessedPage 0 when starting, otherwise the last processed page from previous run
	 * @param int|null    $totalPosts Set on previous run, total amount of posts in export
	 * @param string|null $transientName Set on previous run, name of transient where intermediate results are stored
	 *
	 * @throws ExportException
	 */
	public function __construct(
		string $exportType,
		string $exportStartDate,
		string $exportEndDate,
		array $locationFields = null,
		array $itemFields = null,
		array $userFields = null,
		int $lastProcessedPage = null,
		int $totalPosts = null,
		string $transientName = null
	) {

		if ( ! array_key_exists( $exportType, \CommonsBooking\Wordpress\CustomPostType\Timeframe::getTypes( true ) ) ) {
			throw new ExportException( 'Post type to export not valid' );
		} elseif ( $exportType === 'all' ) {
				$exportType = 0;
		} else {
			$exportType = intval( $exportType );
		}
		$startDateTimestamp = strtotime( $exportStartDate );
		if ( ! $startDateTimestamp ) {
			throw new ExportException( __( 'Invalid start date', 'commonsbooking' ) );
		}
		$endDateTimestamp = strtotime( $exportEndDate );
		if ( ! $endDateTimestamp ) {
			throw new ExportException( __( 'Invalid end date', 'commonsbooking' ) );
		}
		if ( $startDateTimestamp > $endDateTimestamp ) {
			throw new ExportException( __( 'Start date must not be after the end date.', 'commonsbooking' ) );
		}

		$this->exportFilename    = 'timeframe-export-' . date( 'Y-m-d-H-i-s' ) . '.csv';
		$this->transientName     = $transientName ?? COMMONSBOOKING_PLUGIN_SLUG . '-' . $this->exportFilename;
		$this->exportType        = $exportType;
		$this->exportStartDate   = $exportStartDate;
		$this->exportEndDate     = $exportEndDate;
		$this->locationFields    = $locationFields;
		$this->itemFields        = $itemFields;
		$this->userFields        = $userFields;
		$this->lastProcessedPage = $lastProcessedPage ? intval( $lastProcessedPage ) : null;
		$this->totalPosts        = $totalPosts;

		$this->relevantTimeframes = get_transient( $this->transientName ) ?: [];
	}


	/**
	 * @return void
	 * @throws CacheException
	 * @throws InvalidArgumentException
	 */
	public static function ajaxExportCsv() {
		// verify nonce
		check_ajax_referer( 'cb_export_timeframes', 'nonce' );

		$postData = isset( $_POST['data'] ) ? (array) $_POST['data'] : array();
		$postData = commonsbooking_sanitizeArrayorString( $postData );

		$postSettings = $postData['settings'];

		try {
			$exportObject = new self(
				$postSettings['exportType'],
				$postSettings['exportStartDate'],
				$postSettings['exportEndDate'],
				$postSettings['locationFields'] ? self::convertInputFields( $postSettings['locationFields'] ) : null,
				$postSettings['itemFields'] ? self::convertInputFields( $postSettings['itemFields'] ) : null,
				$postSettings['userFields'] ? self::convertInputFields( $postSettings['userFields'] ) : null,
				$postSettings['lastProcessedPage'] ?? null,
				$postSettings['totalPages'] ?? null,
				$postSettings['transientName'] ?? null
			);
		} catch ( ExportException $e ) {
			wp_send_json(
				array(
					'success' => false,
					'error'   => true,
					'message' => $e->getMessage(),
				)
			);
		}
		$nextPage = $exportObject->lastProcessedPage ? $exportObject->lastProcessedPage + 1 : 1;
		$exportObject->getExportData( $nextPage );
		if ( $exportObject->exportDataComplete ) {
			try {
				$csvString = $exportObject->getCSV();
			} catch ( ExportException $e ) {
				wp_send_json(
					array(
						'success' => false,
						'error'   => true,
						'message' => $e->getMessage(),
					)
				);
			}
			wp_send_json(
				array(
					'success'  => true,
					'error'    => false,
					'message'  => __( 'Export finished', 'commonsbooking' ),
					'csv'      => $csvString,
					'filename' => $exportObject->exportFilename,
				)
			);
		} else {
			// store intermediate result in transient
			set_transient( $exportObject->transientName, $exportObject->relevantTimeframes, HOUR_IN_SECONDS );
			$options = array(
				'exportType'         => $exportObject->exportType == 0 ? 'all' : $exportObject->exportType,
				'exportStartDate'    => $exportObject->exportStartDate,
				'exportEndDate'      => $exportObject->exportEndDate,
				'locationFields'     => $exportObject->locationFields,
				'itemFields'         => $exportObject->itemFields,
				'userFields'         => $exportObject->userFields,
				'lastProcessedPage'  => $exportObject->lastProcessedPage,
				'totalPosts'         => $exportObject->totalPosts,
				'transientName' => $exportObject->transientName,
			);
			wp_send_json(
				array(
					'success'  => false,
					'error'    => false,
					'settings' => $options,
					'progress' => $exportObject->getProgressString(),
				)
			);
		}
	}

	/**
	 * Exports a file to the given directory path.
	 * This functions wraps the actual logic of {@see __construct()}, {@see getExportData} and {@see getCsv}.
	 *
	 * Note: At the moment this is not a helper to be used outside its context.
	 * It's heavily coupled to different values set via {@see Settings} and should therefore not be used outside of it's WordPress context.
	 *
	 * @param string $exportPath writable directory.
	 *
	 * @return void
	 * @throws CacheException From cache layer.
	 * @throws InvalidArgumentException From cache layer.
	 */
	public static function cronExport( $exportPath ) {
		$timerange                = Settings::getOption( 'commonsbooking_options_export', 'export-timerange' );
		$start                    = date( 'd.m.Y' );
		$end                      = date( 'd.m.Y', strtotime( '+' . $timerange . ' day' ) );
		$configuredType           = Settings::getOption( 'commonsbooking_options_export', 'export-type' );
		$configuredLocationFields = Settings::getOption( 'commonsbooking_options_export', \CommonsBooking\View\TimeframeExport::LOCATION_FIELD );
		$configuredItemFields     = Settings::getOption( 'commonsbooking_options_export', \CommonsBooking\View\TimeframeExport::ITEM_FIELD );
		$configuredUserFields     = Settings::getOption( 'commonsbooking_options_export', \CommonsBooking\View\TimeframeExport::USER_FIELD );
		if ( $configuredType && $configuredType != 'all' ) {
			$type = intval( $configuredType );
		} elseif ( $configuredType == 'all' ) {
			$type = 'all';
		} else {
			$type = 0;
		}

		try {
			$exportObject = new self(
				$type,
				$start,
				$end,
				$configuredLocationFields ? self::convertInputFields( $configuredLocationFields ) : null,
				$configuredItemFields ? self::convertInputFields( $configuredItemFields ) : null,
				$configuredUserFields ? self::convertInputFields( $configuredUserFields ) : null,
			);
			$exportObject->setCron();
			$exportObject->getExportData();
			$exportObject->getCSV( $exportPath . $exportObject->exportFilename );
		} catch ( ExportException $e ) {
			$file = fopen( $exportPath, 'w' );
			fwrite( $file, $e->getMessage() );
			fclose( $file );
		}
	}

	/**
	 * Will get the corresponding CSV data for the TimeframeExport object as string.
	 * When cron is set, the export will be saved to the configured export path.
	 *
	 * @param string|null $exportPath
	 *
	 * @return string
	 * @throws ExportException
	 */
	public function getCSV( string $exportPath = null ): string {
		$inputFields = [
			'location' => self::getInputFields( 'location-fields' ),
			'item'     => self::getInputFields( 'item-fields' ),
			'user'     => self::getInputFields( 'user-fields' ),
		];

		if ( ! $this->exportDataComplete ) {
			throw new ExportException( __( 'Export data is not complete. Please complete the process before trying to export.', 'commonsbooking' ) );
		}

		if ( empty( $this->relevantTimeframes ) ) {
			throw new ExportException( __( 'No data was found for the selected time period', 'commonsbooking' ) );
		}

		if ( $this->isCron ) {
			if ( $exportPath === null ) {
				throw new ExportException( __( 'You need to set an export path to execute the export', 'commonsbooking' ) );
			}
			$output = fopen( $exportPath, 'w' );
		} else {
			// create a file pointer to memory so that we can save it as a string and return it
			$output = fopen( 'php://memory', 'r+' );
		}

		$headline = false;

		$timeframeDataRows = self::getTimeframeData( $this->relevantTimeframes );

		foreach ( $timeframeDataRows as $timeframeDataRow ) {
			if ( ! $headline ) {
				$headline    = true;
				$headColumns = array_keys( $timeframeDataRow );

				// Iterate through in put fields
				foreach ( $inputFields as $type => $fields ) {
					$columnNames = $fields;
					array_walk(
						$columnNames,
						function ( &$item ) use ( $type ) {
							$item = $type . ': ' . $item;
						}
					);
					$headColumns = array_merge( $headColumns, $columnNames );
				}

				// output the column headings
				fputcsv( $output, $headColumns, ';' );
			}

			// output the column values
			$valueColumns = array_values( $timeframeDataRow );

			// TODO #507
			$timeframeDataPost = new \CommonsBooking\Model\Timeframe( $timeframeDataRow['ID'] );

			// Get values for user defined input fields.
			foreach ( $inputFields as $type => $fields ) {
				// Location fields
				if ( $type == 'location' ) {
					$location = $timeframeDataPost->getLocation();
					foreach ( $fields as $field ) {
						$valueColumns[] = $location->getFieldValue( $field );
					}
				}

				// Item fields
				if ( $type == 'item' ) {
					$item = $timeframeDataPost->getItem();
					foreach ( $fields as $field ) {
						$valueColumns[] = $item->getFieldValue( $field );
					}
				}

				// User fields
				if ( $type == 'user' ) {
					$user = $timeframeDataPost->getUserData();
					foreach ( $fields as $field ) {
						$valueColumns[] = $user->get( $field );
					}
				}
			}

			fputcsv( $output, $valueColumns, ';' );
		}

		if ( $this->isCron ) {
			fclose( $output );

			return '';
		} else {
			rewind( $output );

			return rtrim( stream_get_contents( $output ) );
		}
	}

	/**
	 * Gets export fields array from the comma separated string in the settings.
	 *
	 * @param string|null $inputString
	 *
	 * @return string[] returns an empty array when non-string or empty-string input
	 */
	private static function convertInputFields( $inputString ): array {
		return array_filter( explode( ',', sanitize_text_field( $inputString ) ) );
	}


	/**
	 * This will get a formatted string to display the pages that have been processed.
	 *
	 * @return string
	 */
	private function getProgressString(): string {
		if ( $this->lastProcessedPage === null ) {
			return '';
		}
		$totalBookings    = $this->totalPosts;
		$progressBookings = $this->lastProcessedPage * self::ITERATION_COUNTS;

		// translators: %1$d actual item number, %2$d total item number
		return sprintf( __( 'Processed %1$d of %2$d bookings', 'commonsbooking' ), $progressBookings, $totalBookings );
	}

	/**
	 * Return user defined export fields.
	 *
	 * @param $inputName
	 *
	 * @return false|string[]
	 */
	protected static function getInputFields( $inputName ) {
		$inputFieldsString =
			array_key_exists( $inputName, $_REQUEST ) ? sanitize_text_field( $_REQUEST[ $inputName ] ) :
				Settings::getOption( 'commonsbooking_options_export', '$inputName' );

		return array_filter( explode( ',', $inputFieldsString ) );
	}

	/**
	 * Returns data for cron export.
	 *
	 * @param int $page - The page in the pagination process, -1 if pagination should not be used
	 *
	 * @return bool - False if all days have been processed, True if there are still days left that have to be processed
	 * @throws CacheException
	 * @throws InvalidArgumentException
	 */
	public function getExportData( int $page = - 1 ): bool {

		$start = $this->exportStartDate;
		$end   = $this->exportEndDate;

		// Timerange
		$period = self::getPeriod( $start, $end );

		if ( $this->exportType == 0 ) {
			$types = array_keys( \CommonsBooking\Wordpress\CustomPostType\Timeframe::getTypes() );
		} else {
			$types = [ $this->exportType ];
		}

		// some custom arg for WP_Query to improve performance
		$customArgs = [
			'fields' => 'ids',
		];

		// when we already know the amount of posts, we can disable the SQL_CALC_FOUND_ROWS flag
		if ( $this->totalPosts !== null ) {
			$customArgs['no_found_rows'] = true;
		}

		if ( $page == - 1 ) {
			foreach ( $period as $dt ) {
				$dayTimeframes = Timeframe::get(
					[],
					[],
					$this->exportType ? [ $this->exportType ] : [],
					$dt->format( 'Y-m-d' ),
					false,
					null,
					[ 'canceled', 'confirmed', 'unconfirmed', 'publish', 'inherit' ]
				);
				foreach ( $dayTimeframes as $timeframe ) {
					if ( ! in_array( $timeframe->ID, $this->relevantTimeframes ) ) {
						$this->relevantTimeframes[] = $timeframe->ID;
					}
				}
			}
			$this->exportDataComplete = true;
		} else {
			$relevantTimeframes = Timeframe::getInRangePaginated(
				$period->getStartDate()->getTimestamp(),
				$period->getEndDate()->getTimestamp(),
				$page,
				self::ITERATION_COUNTS,
				$types,
				[ 'confirmed', 'unconfirmed', 'canceled', 'publish', 'inherit' ],
				false,
				$customArgs
			);
			if ( $this->totalPosts === null ) {
				$this->totalPosts = intval( $relevantTimeframes['totalPosts'] );
			}
			$this->lastProcessedPage  = $page;
			$this->exportDataComplete = $relevantTimeframes['done'];

			if ( ! empty( $relevantTimeframes['posts'] ) ) {
				foreach ( $relevantTimeframes['posts'] as $timeframeID ) {
					if ( ! in_array( $timeframeID, $this->relevantTimeframes ) ) {
						$this->relevantTimeframes[] = $timeframeID;
					}
				}
			}
		}

		return $this->exportDataComplete;
	}

	/**
	 * Will get a DatePeriod object from two datestring
	 *
	 * @param string $start Start date as datestring
	 * @param string $end End date as datestring
	 *
	 * @return DatePeriod
	 */
	protected static function getPeriod( $start, $end ) {
		// Timerange
		$begin = Wordpress::getUTCDateTime( $start );
		$end   = Wordpress::getUTCDateTime( $end );

		$interval = DateInterval::createFromDateString( '1 day' );

		return new DatePeriod( $begin, $interval, $end );
	}

	/**
	 * Returns selected timeframe type id.
	 *
	 * @return int
	 */
	protected static function getType(): int {
		$type = 0;

		// Backend download
		if ( array_key_exists( 'export-type', $_REQUEST ) && $_REQUEST['export-type'] !== 'all' ) {
			$type = intval( $_REQUEST['export-type'] );
		} else {
			// cron download
			$configuredType = Settings::getOption( 'commonsbooking_options_export', 'export-type' );
			if ( $configuredType && $configuredType != 'all' ) {
				$type = intval( $configuredType );
			}
		}

		return $type;
	}

	/**
	 * Takes an array of timeframe IDs and returns an array of timeframe assoc array data for the table export
	 *
	 * @param int[] $timeframeIDs Array of timeframe post IDs
	 *
	 * @return array
	 */
	public static function getTimeframeData( array $timeframeIDs ): array {

		$timeframeDataRows = [];
		foreach ( $timeframeIDs as $timeframeID ) {
			try {
				$timeframePost = new \CommonsBooking\Model\Timeframe( $timeframeID );
			} catch ( \Exception $e ) {
				continue;
			}
			$timeframeData = self::getRelevantTimeframeFields( $timeframePost );
			// Timeframe typ
			$timeframeTypeId       = $timeframePost->getFieldValue( 'type' );
			$timeframeTypes        = \CommonsBooking\Wordpress\CustomPostType\Timeframe::getTypes();
			$timeframeData['type'] = array_key_exists( $timeframeTypeId, $timeframeTypes ) ?
				$timeframeTypes[ $timeframeTypeId ] : __( 'Unknown', 'commonsbooking' );

			if ( $timeframeTypeId == \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID ) {
				$booking = new \CommonsBooking\Model\Booking( $timeframePost->ID );
			}

			// Repetition option
			$repetitions  = \CommonsBooking\Wordpress\CustomPostType\Timeframe::getTimeFrameRepetitions();
			$repetitionId = $timeframePost->getFieldValue( \CommonsBooking\Model\Timeframe::META_REPETITION );
			$timeframeData[ \CommonsBooking\Model\Timeframe::META_REPETITION ] = array_key_exists( $repetitionId, $repetitions ) ?
				$repetitions[ $repetitionId ] : __( 'Unknown', 'commonsbooking' );

			// Grid option
			$gridOptions           = \CommonsBooking\Wordpress\CustomPostType\Timeframe::getGridOptions();
			$gridOptionId          = $timeframePost->getGrid();
			$timeframeData['grid'] = array_key_exists( $gridOptionId, $gridOptions ) ?
				$gridOptions[ $gridOptionId ] : __( 'Unknown', 'commonsbooking' );

			// get corresponding item title(s)
			$items = $timeframePost->getItems();
			if ( $items != null ) {
				$items_title = array_map(
					function ( $item ) {
						return $item->post_title;
					},
					$items
				);
			} else {
				$items_title = __( 'Unknown', 'commonsbooking' );
			}

			// get corresponding location title(s)
			$locations = $timeframePost->getLocations();
			if ( $locations != null ) {
				$locations_title = array_map(
					function ( $location ) {
						return $location->post_title;
					},
					$locations
				);
			} else {
				$locations_title = __( 'Unknown', 'commonsbooking' );
			}
			$timeframeOwner = $timeframePost->getUserData();

			// populate simple meta fields
			$timeframeData[ \CommonsBooking\Model\Timeframe::META_MAX_DAYS ] = $timeframePost->getFieldValue( \CommonsBooking\Model\Timeframe::META_MAX_DAYS );
			$timeframeData['full-day']                                       = $timeframePost->getFieldValue( 'full-day' );
			$timeframeData[ \CommonsBooking\Model\Timeframe::REPETITION_START ] =
				$timeframePost->getStartDate() ?
					date( 'c', $timeframePost->getStartDate() ) : '';
			$timeframeData[ \CommonsBooking\Model\Timeframe::REPETITION_END ]   =
				$timeframePost->getEndDate() ?
					date( 'c', $timeframePost->getEndDate() ) : '';
			$timeframeData['start-time']                                        = $timeframePost->getStartTime();
			$timeframeData['end-time']       = $timeframePost->getEndTime();
			$timeframeData['pickup']         = isset( $booking ) ? $booking->pickupDatetime() : '';
			$timeframeData['return']         = isset( $booking ) ? $booking->returnDatetime() : '';
			$timeframeData['booking-code']   = $timeframePost->getFieldValue( '_cb_bookingcode' );
			$timeframeData['user-firstname'] = $timeframeOwner ? $timeframeOwner->first_name : '';
			$timeframeData['user-lastname']  = $timeframeOwner ? $timeframeOwner->last_name : '';
			$timeframeData['user-login']     = $timeframeOwner ? $timeframeOwner->user_login : '';
			$timeframeData['comment']        = $timeframePost->getFieldValue( 'comment' );

			foreach ( $locations_title as $location_title ) {
				foreach ( $items_title as $item_title ) {
					$timeframeData['location-post_title'] = $location_title;
					$timeframeData['item-post_title']     = $item_title;
					// every item / location combination is a new row
					$timeframeDataRows[] = $timeframeData;
				}
			}
		}

		return $timeframeDataRows;
	}

	/**
	 * Gets the fields from the timeframe object relevant for the export.
	 *
	 * @param \CommonsBooking\Model\Timeframe $timeframe The timeframe object to process
	 *
	 * @return array
	 */
	protected static function getRelevantTimeframeFields( \CommonsBooking\Model\Timeframe $timeframe ): array {
		$postArray               = get_object_vars( $timeframe->getPost() );
		$relevantTimeframeFields = [
			'ID',
			'post_title',
			'post_author',
			'post_date',
			'post_date_gmt',
			'post_content',
			'comment',
			'post_excerpt',
			'post_status',
			'post_name',
		];

		return array_filter(
			$postArray,
			function ( $key ) use ( $relevantTimeframeFields ) {
				return in_array( $key, $relevantTimeframeFields );
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Sets the cron flag. This is used to determine if the export is triggered by a cron job.
	 *
	 * @return void
	 */
	public function setCron(): void {
		$this->isCron = true;
	}
}
