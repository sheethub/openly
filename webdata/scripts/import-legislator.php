<?php

include(__DIR__ . '/../init.inc.php');
$fp = fopen(__DIR__ . '/legislator.csv', 'r');
$columns = fgetcsv($fp);
$columns[0] = 'term';
while ($rows = fgetcsv($fp)) {
    $values = array_combine($columns, $rows);
    Legislator::insert(array(
        'term' => intval($values['term']),
        'name' => strval($values['name']),
        'data' => json_encode($values),
    ));
    echo $values['term'] . ' ' . $values['picUrl'] . "\n";
}
