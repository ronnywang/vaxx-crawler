<?php
// 需要手動從 https://www.google.com/maps/d/viewer?mid=1aA1O4XG96OWSJkITlQfwWcpCxHTIhMqJ&hl=zh-TW&ll=25.047879869869398%2C121.54562709723363&z=12 匯出 KML
$doc = new DOMDocument;
$doc->loadXML(file_get_contents('臺北市衛生局疫苗預約地圖.kml'));

$columns = null;
$output = fopen('php://output', 'w');
foreach ($doc->getElementsByTagName('Placemark') as $dom) {
    $values = array();
    foreach ($dom->getElementsByTagName('Data') as $data_dom) {
        $values[$data_dom->getAttribute('name')] = $data_dom->getElementsByTagName('value')->item(0)->nodeValue;
    }
    if (is_null($columns)) {
        $columns = array_keys($values);
        fputcsv($output, array(
            '施打站全稱', '施打站縣市', '施打站行政區', '施打站地址', '預約電話', '施打站經度', '施打站緯度',
        ));
        //fputcsv($output, $columns);
    }
    //'施打站全稱', '施打站縣市', '施打站行政區', '施打站地址', '預約電話', '施打站經度、施打站緯度',
    fputcsv($output, array(
        $values['接種地點'], // 施打站全稱
        '臺北市', // 施打站縣市
        $values['行政區'], // '施打站行政區'
        $values['地址'], // '施打站地址'
        $values['院所電話'], // '預約電話'
        $values['經度'], //  '施打站經度
        $values['緯度'], //施打站緯度',
    ));
}
