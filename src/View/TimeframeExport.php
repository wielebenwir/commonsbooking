<?php


namespace CommonsBooking\View;


use CommonsBooking\Repository\Timeframe;
use CommonsBooking\Settings\Settings;
use DateInterval;
use DatePeriod;
use DateTime;

class TimeframeExport
{

    /**
     * @param $field_args
     * @param $field
     */
    public static function renderExportForm($field_args, $field)
    {
        ?>
        <div class="cmb-row cmb-type-text ">
            <button type="submit" id="timeframe-export" class="button button-secondary" name="submit-cmb" value="download-export">
                <?php echo esc_html__('Download Export', 'commonsbooking'); ?>
            </button>
        </div>
        <?php
    }

    protected static function getPeriod($start, $end) {
        // Timerange
        $begin = new DateTime($start);
        $end = new DateTime($end);

        $interval = DateInterval::createFromDateString('1 day');
        return new DatePeriod($begin, $interval, $end);
    }

    /**
     * Returns array with selected timeframe types.
     * @return array
     */
    protected static function getTypes() {
        $types = [];

        // Backend download
        if(array_key_exists('export-type', $_REQUEST) && $_REQUEST['export-type'] !== 'all') {
            $types = [intval($_REQUEST['export-type'])];
        } else {
            //cron download
            $type = Settings::getOption('commonsbooking_options_export', 'export-type');
            if( $type && $type !== 'all' ) {
                $types = [intval($type)];
            }
        }
        return $types;
    }

    /**
     * Return user defined export fields.
     * @param $inputName
     * @return false|string[]
     */
    protected static function getInputFields($inputName) {
        $inputFieldsString =
            array_key_exists($inputName, $_REQUEST) ? $_REQUEST[$inputName] :
            Settings::getOption('commonsbooking_options_export', '$inputName');
        return array_filter(explode(',', $inputFieldsString));
    }

    /**
     * Returns data for export.
     * @param false $isCron
     * @return array
     * @throws \Exception
     */
    public static function getExportData($isCron = false) {
        if($isCron) {
            $timerange = Settings::getOption('commonsbooking_options_export', 'export-timerange');
            $start = date('d.m.Y');
            $end = date('d.m.Y', strtotime('+' . $timerange . ' day'));
        } else {
            $start = $_REQUEST['export-timerange-start'];
            $end = $_REQUEST['export-timerange-end'];
        }

        // Timerange
        $period = self::getPeriod($start, $end);

        // Types
        $types = self::getTypes();

        $timeframes = [];
        foreach ($period as $dt) {
            $dayTimeframes = Timeframe::get(
                [],
                [],
                $types,
                $dt->format("Y-m-d"),
                true
            );
            foreach ($dayTimeframes as $timeframe) {
                $timeframes[$timeframe->ID] = $timeframe;
            }
        }

        return $timeframes;
    }

    /**
     * @param string $outputFile
     * @throws \Exception
     */
    public static function exportCsv($outputFile = 'php://output')
    {
        $exportFilename = 'timeframe-export.csv';

        $inputFields = [
            'location' => self::getInputFields('location-fields'),
            'item' => self::getInputFields('item-fields'),
            'user' => self::getInputFields('user-fields')
        ];

        if($outputFile == 'php://output') {
            $timeframes = self::getExportData();

            // output headers so that the file is downloaded rather than displayed
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $exportFilename);
        } else {
            $timeframes = self::getExportData(true);
            $outputFile = $outputFile . $exportFilename;
        }

        // create a file pointer connected to the output stream
        $output = fopen($outputFile, 'w');

        $headline = false;

        /** @var \CommonsBooking\Model\Timeframe $timeframePost */
        foreach ($timeframes as $timeframePost) {
            $timeframeData = self::getTimeframeData($timeframePost);

            if(!$headline) {
                $headline = true;
                $headColumns = array_keys($timeframeData);

                // Iterate through in put fields
                foreach ($inputFields as $type => $fields) {
                    $columnNames = $fields;
                    array_walk($columnNames, function (&$item) use ($type) {
                        $item = $type . ': ' . $item;
                    });
                    $headColumns = array_merge($headColumns, $columnNames);
                }

                // output the column headings
                fputcsv($output, $headColumns, ";");
            }

            // output the column values
            $valueColumns = array_values($timeframeData);

            // Get values for user defined input fields.
            foreach ($inputFields as $type => $fields) {
                // Location fields
                if($type == 'location') {
                    $location = $timeframePost->getLocation();
                    foreach ($fields as $field) {
                        $valueColumns[] = $location->getFieldValue($field);
                    }
                }

                // Item fields
                if($type == 'item') {
                    $item = $timeframePost->getItem();
                    foreach ($fields as $field) {
                        $valueColumns[] = $item->getFieldValue($field);
                    }
                }

                // User fields
                if($type == 'user') {
                    $user = $timeframePost->getUserData();
                    foreach ($fields as $field) {
                        $valueColumns[] = $user->get($field);
                    }
                }
            }

            fputcsv($output, $valueColumns, ";");
        }

        fclose($output);
        exit();
    }

    /**
     * Prepares timeframe data array.
     * @param $timeframePost
     * @return array
     */
    protected static function getTimeframeData(\CommonsBooking\Model\Timeframe $timeframePost) {
        $timeframeData = self::getRelevantTimeframeFields($timeframePost);

        // Timeframe typ
        $timeframeTypeId = $timeframePost->getFieldValue('type');
        $timeframetypes = \CommonsBooking\Wordpress\CustomPostType\Timeframe::getTypes();
        $timeframeData['type'] = array_key_exists($timeframeTypeId, $timeframetypes) ?
            $timeframetypes[$timeframeTypeId] : __('Unknown', 'commonsbooking');

        // Repetition option
        $repetitions = \CommonsBooking\Wordpress\CustomPostType\Timeframe::getTimeFrameRepetitions();
        $repetitionId = $timeframePost->getFieldValue("timeframe-repetition");
        $timeframeData["timeframe-repetition"] = array_key_exists($repetitionId, $repetitions) ?
            $repetitions[$repetitionId] : __('Unknown', 'commonsbooking');

        // Grid option
        $gridOptions = \CommonsBooking\Wordpress\CustomPostType\Timeframe::getGridOptions();
        $gridOptionId = $timeframePost->getGrid();
        $timeframeData["grid"] = array_key_exists($gridOptionId, $gridOptions) ?
            $gridOptions[$gridOptionId] : __('Unknown', 'commonsbooking');

        // simple meta fields
        $timeframeData["timeframe-max-days"] = $timeframePost->getFieldValue("timeframe-max-days");
        $timeframeData["full-day"] = $timeframePost->getFieldValue("full-day");
        $timeframeData["repetition-start"] = $timeframePost->getStartDate() ? date(get_option('date_format'), $timeframePost->getStartDate()) : '';
        $timeframeData["repetition-end"] = $timeframePost->getEndDate() ? date(get_option('date_format'), $timeframePost->getEndDate()) : '';
        $timeframeData["booking-code"] = $timeframePost->getFieldValue("_cb_bookingcode");

        return $timeframeData;
    }

    /**
     * Removes not relevant fields from timeframedata.
     * @param $timeframe
     * @return array
     */
    protected static function getRelevantTimeframeFields($timeframe) {
        $postArray = get_object_vars($timeframe->getPost());
        $relevantTimeframeFields = [
            'ID',
            'post_title',
            "post_author",
            "post_date",
            "post_date_gmt",
            "post_content",
            "post_excerpt",
            "post_status",
            "post_name"
        ];

        $postArray = array_filter($postArray, function ($key) use ($relevantTimeframeFields) {

            return in_array($key, $relevantTimeframeFields);
        }, ARRAY_FILTER_USE_KEY);

        return $postArray;
    }

}
