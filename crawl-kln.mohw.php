<?php

header('Content-Type: text/plain');
$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_COOKIEFILE, '');

$doc = new DOMDocument;
$content = file_get_contents('https://netreg.kln.mohw.gov.tw/OINetReg/OINetReg.Reg/Reg_NetReg.aspx');
$content = str_replace('<head id="ctl00_Head1">', '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $content);

$output = fopen('php://output', 'w');
fputcsv($output, array(
    '施打站名稱',
    '疫苗種類',
    '日期',
    '時間',
    '剩餘名額',
    '資料抓取時間',
    '備註',
));

@$doc->loadHTML($content);

foreach ($doc->getElementsByTagName('a') as $a_dom) {
    if (false === strpos($a_dom->nodeValue, '疫苗')) {
        continue;
    }

    $vaccine_type = $a_dom->nodeValue;

    // COVID19 AZ疫苗 ../OINetReg.Reg/Reg_RegTable.aspx?HID=F&Way=Dept&DivDr=0499&Date=&Noon=
    $url = "https://netreg.kln.mohw.gov.tw/OINetReg/" . str_replace('../', '', $a_dom->getAttribute('href'));

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_exec($curl);

    curl_setopt($curl, CURLOPT_URL, 'https://netreg.kln.mohw.gov.tw/OINetReg/OINetReg.Reg/Sub_RegTable.aspx');
    $content = curl_exec($curl);

    $doc2 = new DOMDocument;
    $content = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8"></head><body>' . $content . '</body></html>';

    @$doc2->loadHTML($content);
    $dates = array();
    foreach ($doc2->getElementsByTagName('tr') as $tr_dom) {
        $first_td = $tr_dom->getElementsByTagName('td')->item(1);
        if (preg_match('#(\d+)/(\d+)星期.*#u', preg_replace('/\s+/', '', $first_td->nodeValue), $matches)) {
            $dates = array();
        }
        foreach ($tr_dom->getElementsByTagName('td') as $idx => $td_dom) {
            $text = preg_replace('/\s+/', '', $td_dom->nodeValue);
            if (preg_match('#(\d+)/(\d+)星期.*#u', $text, $matches)) {
                $dates[$idx] = date('Y-m-d', mktime(0, 0, 0, $matches[1], $matches[2]));
                continue;
            }

            if (!$text) {
                continue;
            }
            if ($idx == 0) {
                $timepart = $text;
                continue;
            }

            if (preg_match('#(.*)額滿#', $text, $matches)) {
                $doctor = $matches[1];
                $amount = 0;
            } elseif (preg_match('#(.*)\((\d+)\)#', $text, $matches)) {
                $doctor = $matches[1];
                $amount = $matches[2];;
            } else {
                continue;
            }

            fputcsv($output, array(
                '衛生福利部基隆醫院', // '施打站名稱',
                $vaccine_type, // '疫苗種類',
                $dates[$idx], // '日期',
                $timepart, // '時間',
                $amount, // '剩餘名額',
                date('c'), // '資料抓取時間',
                $doctor, // '備註',
            ));
        }
    }
}
