Initial services process.

Tables:
<?php
$i = 1;
foreach ($data as $tableName => $report) {
    echo $i . '. ' . $tableName . ': ' . $report . PHP_EOL;
    $i++;
}
