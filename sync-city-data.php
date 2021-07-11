<?php

$fp = fopen('airtable-data/各縣市資料清單.csv', 'r');
$columns = fgetcsv($fp);
while ($rows = fgetcsv($fp)) {
    $rows = array_merge($rows, array_fill(0, count($columns) - count($rows), ''));
    $values = array_combine($columns, $rows);
    $city = $values['縣市'];
    if ($url = $values['爬蟲結果放置處']) {
        error_log($city . ' ' . $url);
        file_put_contents(__DIR__ . '/city-data/' . $city . '.csv', file_get_contents($url));
    }
}
