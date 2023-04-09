<?php
if (isset($argv[1])) $csvFile = $argv[1];
else {echo "please indicate csv file name\n"; return;}

include_once realpath('vendor/autoload.php');

use Bank\Service\Operation;

$operations = Operation::getInstance($csvFile);


$myResult = $operations->calculateAllCommissions();
for($i = 0; $i < 13; $i++) echo $myResult[$i] . "\n";



?>