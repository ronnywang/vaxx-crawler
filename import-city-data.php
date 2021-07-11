<?php

include(__DIR__ . '/config.php');

$place_data = new StdClass;
$fp = fopen('airtable-data/施打點清單.csv', 'r');
$airtable_columns = fgetcsv($fp);
while ($rows = fgetcsv($fp)) {
    $rows = array_merge($rows, array_fill(0, count($airtable_columns) - count($rows), ''));
    $values = array_combine($airtable_columns, $rows);
    $name = $values['施打站縣市（自動）'] . ' ' . $values['施打站全稱（自動）'];
    if (property_exists($place_data, $name)) {
        throw new Exception($name);
    }
    $place_data->{$name} = $values;
}
fclose($fp);

$check_phone = function($a, $b) {
    $a = preg_replace("#[^0-9]#", '', $a);
    $b = preg_replace("#[^0-9]#", '', $b);
    if ($a and $a == $b) {
        return true;
    }
    return false;
};


$same = 0;
$add = 0;
$names = array();
$records = array();

$add_record = function($data) use (&$records) {
    if (!is_null($data)) {
        $records[] = array(
            'fields' => $data,
        );
    }
    if (is_null($data) or count($records) >= 10) {
        $curl = curl_init("https://api.airtable.com/v0/appwPM9XFr1SSNjy4/%E6%96%BD%E6%89%93%E9%BB%9E%E6%B8%85%E5%96%AE");
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . getenv('AIRTABLE_KEY'),
        ));
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(array('records' => $records)));
        curl_exec($curl);
        $records = array();
    }
};

foreach (glob("city-data/*.csv") as $csv_file) {
    $fp = fopen($csv_file, 'r');
    preg_match('#city-data/(.*).csv#', $csv_file, $matches);
    $county = $matches[1];
    $columns = fgetcsv($fp);
    if (count($columns) > 100 or !in_array('施打站全稱', $columns)) {
        error_log("{$csv_file} 不是合格的 CSV");
        continue;
    }
    while ($rows = fgetcsv($fp)) {
        $rows = array_merge($rows, array_fill(0, count($columns) - count($rows), ''));
        $values = array_combine($columns, $rows);
        $values['施打站縣市'] = $county;
        $name = $county . ' ' . $values['施打站全稱'];
        if (property_exists($place_data, $name)) {
            $same ++;
            // 名稱相同者跳過
            continue;
            /*$address = $values['施打站地址'];
            $address = preg_replace('#^\d+#', '', $address);
            if ($place_data->{$name}['施打站地址（自動）'] == $address or $check_phone($place_data->{$name}['預約電話（自動）'], $values['預約電話'])) {
                $same ++;
                continue;
            }
            $add ++;
            echo json_encode($place_data->{$name}, JSON_UNESCAPED_UNICODE) . "\n";
            echo json_encode($values, JSON_UNESCAPED_UNICODE) . "\n";
            echo "====\n";*/
        }
        $add ++;
        if (array_key_exists($name, $names)) {
            error_log($name);
            continue;
        }
        $names[$name] = true;

        $data = array();
        foreach ($columns as $c) {
            if (!in_array($c . '（自動）', $airtable_columns)) {
                throw new Exception($c);
            }
            if (in_array($c, array('施打站經度', '施打站緯度'))) {
                $data[$c .'（自動）'] = floatval($values[$c]);
            } else {
                $data[$c .'（自動）'] = $values[$c];
            }
        }
        $data['備註（手動）'] = '各縣市資料';

        $add_record($data);

        //$place_data->{$name} = $values;
    }
    fclose($fp);
}

$add_record(null);

echo "same={$same}, add={$add}\n";
