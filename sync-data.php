<?php

include(__DIR__ . '/config.php');
$key = getenv('AIRTABLE_KEY');
$tables = array(
    '施打點清單', '各縣市資料清單', '預約連結清單', '施打對象清單', '疫苗種類清單',
);
$baseId = 'tblgAuX1y8gCdMA0m';
foreach ($tables as $table) {
    $offset = '';
    $columns = array('_id');
    $target_file = __DIR__ . '/airtable-data/' . $table . '.csv';
    $fp = fopen($target_file, 'w');
    while (true) {
        $url = sprintf("https://api.airtable.com/v0/appwPM9XFr1SSNjy4/%s?api_key=%s&offset=%s", urlencode($table), $key, $offset);
        error_log($url);
        $obj = json_decode(file_get_contents($url));
        $records = $obj->records;
        if (!$records) {
            break;
        }
        foreach ($records as $record) {
            foreach ($record->fields as $k => $v) {
                if (!is_scalar($v)) {
                    continue;
                }
                if (!in_array($k, $columns)) {
                    $columns[] = $k;
                }
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
    file_put_contents($target_file, implode(',', $columns) . "\n" . file_get_contents($target_file));
}
