<?php

include(__DIR__ . '/config.php');
$key = getenv('AIRTABLE_KEY');
$tables = array(
    '施打點清單', '各縣市資料清單', '預約連結清單', '施打對象清單', '疫苗種類清單',
);
$baseId = 'tblgAuX1y8gCdMA0m';
foreach ($tables as $table) {
    $offset = '';
    $columns = null;
    $fp = fopen(__DIR__ . '/airtable-data/' . $table . '.csv', 'w');
    while (true) {
        $url = sprintf("https://api.airtable.com/v0/appwPM9XFr1SSNjy4/%s?api_key=%s&offset=%s", urlencode($table), $key, $offset);
        error_log($url);
        $obj = json_decode(file_get_contents($url));
        $records = $obj->records;
        if (!$records) {
            break;
        }
        foreach ($records as $record) {
            if (is_null($columns)) {
                unset($record->fields->{'Last Modified By'});
                $columns = array_merge(array('_id'), array_keys(get_object_vars($record->fields)));
                fputcsv($fp, $columns);
            }

            fputcsv($fp, array_map(function($id) use ($record) {
                if ($id == '_id') {
                    return $record->id;
                }
                if (property_exists($record->fields, $id)) {
                    return $record->fields->{$id};
                }
                return '';
            }, $columns));
        }

        if (!property_exists($obj, 'offset')) {
            break;
        }
        $offset = $obj->offset;
    }
    fclose($fp);
}
