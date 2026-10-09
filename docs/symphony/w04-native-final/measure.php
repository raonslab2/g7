<?php
require __DIR__.'/guard.php';
$name=$argv[1]??'measurement';
$p=w04Pdo();$exclusive=w04fExclusive($p);$inventory=w04Measure($p);
w04Save(w04fEvidence('measure').'/'.$name.'.json',['inventory'=>$inventory,'digest'=>w04fDigest($inventory),'exclusive'=>$exclusive]);
echo json_encode(['tables'=>$inventory['table_count'],'rows'=>$inventory['row_count'],'digest'=>w04fDigest($inventory)])."\n";
