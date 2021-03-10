<?php


namespace CommonsBooking\View;


use CommonsBooking\Repository\Timeframe;
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
            <button type="submit" id="timeframe-export" class="button button-primary" name="submit-cmb" value="download-export">
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

    protected static function getTypes() {
        $types = [];
        if(array_key_exists('export-type', $_REQUEST) && $_REQUEST['export-type'] !== 'all') {
            $types = [intval($_REQUEST['export-type'])];
        }
        return $types;
    }

    protected static function getInputFields($inputName) {
        $inputFieldsString = array_key_exists($inputName, $_REQUEST) ? $_REQUEST[$inputName] : '';
        return array_filter(explode(',', $inputFieldsString));
    }

    /**
     * @throws \Exception
     */
    public static function exportCsv()
    {
        // Timerange
        $period = self::getPeriod($_REQUEST['export-timerange-start'], $_REQUEST['export-timerange-end']);

        // Types
        $types = self::getTypes();

        $inputFields = [
           'locations' => self::getInputFields('location-fields'),
           'users' => self::getInputFields('user-fields'),
           'items' => self::getInputFields('item-fields')
        ];

        $timeframes = [];
        foreach ($period as $dt) {
            $dayTimeframes = Timeframe::get(
                [],
                [],
                $types,
                $dt->format("Y-m-d")
            );
            foreach ($dayTimeframes as $timeframe) {
                $timeframes[$timeframe->ID] = $timeframe;
            }
        }

        echo '<pre>';

//        // output headers so that the file is downloaded rather than displayed
//        header('Content-Type: text/csv; charset=utf-8');
//        header('Content-Disposition: attachment; filename=timeframe-export.csv');
//
//        // create a file pointer connected to the output stream
//        $output = fopen('php://output', 'w');

        $headline = false;
        foreach ($timeframes as $timeframe) {
            $timeframe = get_object_vars($timeframe);
            if(!$headline) {
                $headline = true;
                $headColumns = array_keys($timeframe);

                foreach ($inputFields as $type => $fields) {
                    $columnNames = $fields;
                    array_walk($columnNames, function (&$item, $key) use ($type) {
                        $item = $type . ': ' . $item;
                    });
                    $headColumns = array_merge($headColumns, $columnNames);
                }

                // output the column headings
//                fputcsv($output, $headColumns, ";");
                var_dump($headColumns);
            }
            // output the column values
            $valueColumns = array_values($timeframe);
//            fputcsv($output, $valueColumns, ";");
            var_dump($valueColumns);
        }
        die;

//        fclose($output);
        exit();
    }

}
