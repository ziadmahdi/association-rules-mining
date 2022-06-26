<?php
require 'include/association_rules.php';
require 'include/datamining.php';

ini_set('memory_limit', '2506M');
ini_set('max_execution_time', '300');

$path = 'data/Qk.txt';
$minSupport =  0.1;
$confidence = 0.4;
$ts = 0.1;

//algorithms => [Apriori,FPGrowth,Eclate,Hmine,Indirect]

$algo   = new DataMine($path, ' ', $minSupport, $confidence, $ts, 'Apriori');
echo 'duration : ' . $algo->duration . " ms<br>";
echo 'memory : ' . $algo->memory . ' KB<br>---------------------<br>';
echo $algo->printRules() . "<br>";

exit();
